<?php

namespace Tests\Feature;

use App\Filament\Resources\Registrations\Pages\ListRegistrations;
use App\Filament\Resources\Registrations\RegistrationResource;
use App\Models\Period;
use App\Models\Registration;
use App\Models\Schedule;
use App\Models\Subject;
use App\Models\Task;
use App\Models\TaskDetail;
use App\Models\User;
use App\Services\RegistrationExportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;
use ZipArchive;

class RegistrationResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_superadmin_only_sees_registrations_from_the_default_period(): void
    {
        $superadmin = User::query()->findOrFail(1);
        $student = User::factory()->create(['role_id' => 3]);

        $this->actingAs($superadmin);

        [$defaultRegistration, $nonDefaultRegistration] = $this->createRegistrationsForDifferentPeriods($student, $superadmin);

        $visibleIds = RegistrationResource::getEloquentQuery()
            ->pluck('id')
            ->all();

        $this->assertContains($defaultRegistration->id, $visibleIds);
        $this->assertNotContains($nonDefaultRegistration->id, $visibleIds);
    }

    public function test_regular_user_only_sees_their_own_registrations_from_the_default_period(): void
    {
        $user = User::factory()->create(['role_id' => 3]);
        $otherUser = User::factory()->create(['role_id' => 3]);
        $superadmin = User::query()->findOrFail(1);

        $this->actingAs($user);

        $defaultPeriod = Period::query()->create([
            'name' => 'Global Default',
            'default' => 1,
        ]);

        $nonDefaultPeriod = Period::query()->create([
            'name' => 'Archived Period',
            'default' => 0,
        ]);

        $ownDefaultRegistration = $this->createRegistration($user, $defaultPeriod, $superadmin);
        $ownNonDefaultRegistration = $this->createRegistration($user, $nonDefaultPeriod, $superadmin);
        $otherUsersDefaultRegistration = $this->createRegistration($otherUser, $defaultPeriod, $superadmin);

        $visibleIds = RegistrationResource::getEloquentQuery()
            ->pluck('id')
            ->all();

        $this->assertContains($ownDefaultRegistration->id, $visibleIds);
        $this->assertNotContains($ownNonDefaultRegistration->id, $visibleIds);
        $this->assertNotContains($otherUsersDefaultRegistration->id, $visibleIds);
    }

    public function test_registration_exports_are_sorted_by_shortened_student_email(): void
    {
        $superadmin = User::query()->findOrFail(1);
        $studentZeta = User::factory()->create([
            'role_id' => 3,
            'name' => 'Student Zeta',
            'email' => 'zeta@student.uksw.edu',
        ]);
        $studentAlpha = User::factory()->create([
            'role_id' => 3,
            'name' => 'Student Alpha',
            'email' => 'alpha@student.uksw.edu',
        ]);

        $period = Period::query()->create([
            'name' => 'Current Period',
            'default' => 1,
        ]);
        $subject = Subject::query()->create([
            'user_id' => $superadmin->id,
            'code' => 'EXPORT',
            'name' => 'Export Subject',
        ]);
        $schedule = Schedule::query()->create([
            'user_id' => $superadmin->id,
            'period_id' => $period->id,
            'subject_id' => $subject->id,
            'note' => 'Monday',
        ]);

        $taskOne = Task::query()->create([
            'user_id' => $superadmin->id,
            'schedule_id' => $schedule->id,
            'name' => 'Task One',
            'percentage' => 50,
            'index' => 1,
        ]);
        Task::query()->create([
            'user_id' => $superadmin->id,
            'schedule_id' => $schedule->id,
            'name' => 'Task Two',
            'percentage' => 50,
            'index' => 2,
        ]);

        foreach ([$studentZeta, $studentAlpha] as $student) {
            Registration::query()->create([
                'user_id' => $superadmin->id,
                'student_user_id' => $student->id,
                'schedule_id' => $schedule->id,
                'grade' => 'A',
            ]);
        }

        TaskDetail::query()->create([
            'user_id' => $studentAlpha->id,
            'task_id' => $taskOne->id,
            'document_link' => 'https://example.com/submission',
            'score' => 88,
        ]);

        $service = app(RegistrationExportService::class);
        $query = Registration::query();
        $rows = $service->rows($query);

        $this->assertSame(['alpha', 'zeta'], $rows->pluck('email')->all());
        $this->assertSame('1 / 2', $rows->first()['submitted_tasks']);
        $this->assertSame(88.0, $rows->first()['total_score']);

        $pdfResponse = $service->downloadPdf($query);
        $this->assertSame('application/pdf', $pdfResponse->headers->get('content-type'));

        ob_start();
        $pdfResponse->sendContent();
        $pdfContent = ob_get_clean();

        $this->assertStringStartsWith('%PDF-', $pdfContent);

        $excelResponse = $service->downloadExcel($query);
        $excelPath = $excelResponse->getFile()->getPathname();
        $zip = new ZipArchive;

        $this->assertTrue($zip->open($excelPath) === true);
        $this->assertNotFalse($zip->locateName('xl/workbook.xml'));

        $zip->close();
        unlink($excelPath);
    }

    public function test_pdf_export_action_uses_a_livewire_safe_file_download(): void
    {
        $superadmin = User::query()->findOrFail(1);
        $this->actingAs($superadmin);

        Period::query()->create([
            'name' => 'Current Period',
            'default' => 1,
        ]);

        Livewire::test(ListRegistrations::class)
            ->callTableAction('exportPdf')
            ->assertFileDownloaded(contentType: 'application/pdf');
    }

    /**
     * @return array{0: \App\Models\Registration, 1: \App\Models\Registration}
     */
    private function createRegistrationsForDifferentPeriods(User $student, User $creator): array
    {
        $defaultPeriod = Period::query()->create([
            'name' => "Default {$student->id}",
            'default' => 1,
        ]);

        $nonDefaultPeriod = Period::query()->create([
            'name' => "Non Default {$student->id}",
            'default' => 0,
        ]);

        $defaultRegistration = $this->createRegistration($student, $defaultPeriod, $creator);
        $nonDefaultRegistration = $this->createRegistration($student, $nonDefaultPeriod, $creator);

        return [$defaultRegistration, $nonDefaultRegistration];
    }

    private function createRegistration(User $student, Period $period, User $creator): Registration
    {
        $schedule = Schedule::query()->create([
            'user_id' => $creator->id,
            'period_id' => $period->id,
        ]);

        return Registration::query()->create([
            'user_id' => $creator->id,
            'student_user_id' => $student->id,
            'schedule_id' => $schedule->id,
            'grade' => 'A',
        ]);
    }
}
