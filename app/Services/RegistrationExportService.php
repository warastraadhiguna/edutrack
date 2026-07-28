<?php

namespace App\Services;

use App\Models\Registration;
use App\Models\Task;
use App\Models\TaskDetail;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Options;
use OpenSpout\Writer\XLSX\Writer;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RegistrationExportService
{
    private const STUDENT_EMAIL_DOMAIN = '@student.uksw.edu';

    /**
     * @return Collection<int, array{
     *     student_name: string,
     *     email: string,
     *     subject: string,
     *     note: string,
     *     submitted_tasks: string,
     *     total_score: float,
     *     grade: string,
     *     created_by: string
     * }>
     */
    public function rows(Builder $query): Collection
    {
        return (clone $query)
            ->select('registrations.*')
            ->addSelect([
                'export_total_tasks' => Task::query()
                    ->selectRaw('count(*)')
                    ->whereColumn('tasks.schedule_id', 'registrations.schedule_id'),
                'export_submitted_tasks' => TaskDetail::query()
                    ->selectRaw('count(*)')
                    ->join('tasks as export_submitted_tasks', 'task_details.task_id', '=', 'export_submitted_tasks.id')
                    ->whereColumn('task_details.user_id', 'registrations.student_user_id')
                    ->whereColumn('export_submitted_tasks.schedule_id', 'registrations.schedule_id'),
                'export_total_score' => TaskDetail::query()
                    ->selectRaw('coalesce(sum(task_details.score), 0)')
                    ->join('tasks as export_scored_tasks', 'task_details.task_id', '=', 'export_scored_tasks.id')
                    ->whereColumn('task_details.user_id', 'registrations.student_user_id')
                    ->whereColumn('export_scored_tasks.schedule_id', 'registrations.schedule_id'),
            ])
            ->with(['studentUser', 'schedule.subject', 'user'])
            ->reorder()
            ->orderBy(
                User::query()
                    ->select('email')
                    ->whereColumn('users.id', 'registrations.student_user_id')
            )
            ->orderBy('registrations.id')
            ->get()
            ->map(fn (Registration $registration): array => [
                'student_name' => $registration->studentUser?->name ?? '-',
                'email' => self::shortEmail($registration->studentUser?->email),
                'subject' => $registration->schedule?->subject?->name ?? '-',
                'note' => $registration->schedule?->note ?? '-',
                'submitted_tasks' => sprintf(
                    '%d / %d',
                    (int) $registration->getAttribute('export_submitted_tasks'),
                    (int) $registration->getAttribute('export_total_tasks'),
                ),
                'total_score' => (float) $registration->getAttribute('export_total_score'),
                'grade' => $registration->grade ?: '-',
                'created_by' => $registration->user?->name ?? '-',
            ]);
    }

    public static function shortEmail(?string $email): string
    {
        if (blank($email)) {
            return '-';
        }

        return preg_replace(
            '/'.preg_quote(self::STUDENT_EMAIL_DOMAIN, '/').'$/i',
            '',
            $email,
        ) ?? $email;
    }

    public function downloadExcel(Builder $query): BinaryFileResponse
    {
        $rows = $this->rows($query);
        $path = tempnam(sys_get_temp_dir(), 'registration-export-');

        $options = new Options;
        $options->setColumnWidth(6, 1);
        $options->setColumnWidth(20, 2);
        $options->setColumnWidth(28, 3);
        $options->setColumnWidth(24, 4);
        $options->setColumnWidth(28, 5);
        $options->setColumnWidth(18, 6);
        $options->setColumnWidth(14, 7, 8);
        $options->setColumnWidth(20, 9);

        $writer = new Writer($options);
        $writer->openToFile($path);
        $writer->getCurrentSheet()->setName('Registrations');

        $headerStyle = (new Style)
            ->setFontBold()
            ->setBackgroundColor('E5E7EB');

        $writer->addRow(Row::fromValues([
            'No.',
            'Email',
            'Student',
            'Subject',
            'Note',
            'Submitted Task',
            'Total Score',
            'Grade',
            'User',
        ], $headerStyle));

        foreach ($rows as $index => $row) {
            $writer->addRow(Row::fromValues([
                $index + 1,
                $row['email'],
                $row['student_name'],
                $row['subject'],
                $row['note'],
                $row['submitted_tasks'],
                $row['total_score'],
                $row['grade'],
                $row['created_by'],
            ]));
        }

        $writer->close();

        return response()
            ->download($path, $this->fileName('xlsx'))
            ->deleteFileAfterSend();
    }

    public function downloadPdf(Builder $query): StreamedResponse
    {
        $content = Pdf::loadView('exports.registrations-pdf', [
            'rows' => $this->rows($query),
            'generatedAt' => now('Asia/Jakarta'),
        ])
            ->setPaper('a4', 'landscape')
            ->output();

        return response()->streamDownload(
            static function () use ($content): void {
                echo $content;
            },
            $this->fileName('pdf'),
            ['Content-Type' => 'application/pdf'],
        );
    }

    private function fileName(string $extension): string
    {
        return 'registrations-'.now('Asia/Jakarta')->format('Ymd-His').".{$extension}";
    }
}
