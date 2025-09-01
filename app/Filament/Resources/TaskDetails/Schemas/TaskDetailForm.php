<?php

namespace App\Filament\Resources\TaskDetails\Schemas;

use App\Models\Period;
use App\Models\Registration;
use App\Models\Schedule;
use App\Models\Task;
use App\Models\TaskDetail;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Set;
use Filament\Schemas\Schema;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class TaskDetailForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
            Select::make('schedule_id')
                ->label('Schedule')
                ->options(function () {
                    // Ambil period default (ubah sesuai skema Anda: 'default' = 1/true)
                    $periodId = Period::where('default', 1)->value('id');
                    if (!$periodId) {
                        return [];
                    }

                    $scheduleIds = Registration::where('student_user_id', Auth::id())
                        ->pluck('schedule_id')   // << kuncinya: pluck
                        ->filter()               // buang null
                        ->unique()               // unik
                        ->values()
                        ->all();

                    if (empty($scheduleIds)) {
                        return [];
                    }

                    return Schedule::with('subject')
                        ->where('period_id', $periodId)
                        ->whereIn('id', $scheduleIds)
                        ->get()
                        ->mapWithKeys(fn ($s) => [
                            $s->id => ($s->subject?->code ?? '—') . ' — ' . ($s->subject?->name ?? 'Tanpa Nama'),
                        ])
                        ->toArray();
                })
                ->searchable()
                ->required()
                ->reactive() // <-- penting: supaya komponen lain bisa merespons perubahan ini
                    ->afterStateHydrated(function (callable $set, $record) {
                        // saat Edit, set task berdasarkan task detail yang tersimpan
                        if ($record?->task?->schedule_id) {
                            $set('schedule_id', $record->task->schedule_id);
                        }
                    })
                ->afterStateUpdated(
                    fn (callable $set) =>  // reset task saat schedule berubah
                    $set('task_id', null)
                ),
                Select::make('task_id')
                ->label('Task')
                ->options(function (callable $get) {
                    $scheduleId = $get('schedule_id');
                    if (! $scheduleId) {
                        return [];
                    }

                    return Task::query()
                        ->where('schedule_id', $scheduleId)
                        ->orderBy('index') // urutkan sesuai kebutuhanmu
                        ->get()
                        ->mapWithKeys(fn ($t) => [
                            $t->id => sprintf('%s', $t->name),
                        ])
                        ->toArray();
                })
                ->disabled(fn (callable $get) => blank($get('schedule_id')))
                ->searchable()
                ->required()
                // validasi server-side: task harus milik schedule yang dipilih
                // ->rule(function (callable $get) {
                //     return Rule::exists('tasks', 'id')
                //         ->where('schedule_id', $get('schedule_id'));
                // }),
                ->rules([
                    // 1) Sudah ada: task harus milik schedule yang dipilih
                    fn (callable $get) =>
                        Rule::exists('tasks', 'id')->where('schedule_id', $get('schedule_id')),

                    // 2) Baru: larang duplikat (user_id, task_id)
                    fn (callable $get, ?TaskDetail $record) =>
                        Rule::unique('task_details', 'task_id')
                            ->where(fn ($q) => $q->where('user_id', $get('user_id') ?? $record?->user_id))
                            ->ignore($record?->id), // penting saat Edit agar record sendiri tidak dianggap duplikat
                ])
                ->validationMessages([
                    'exists' => 'Task tidak valid untuk schedule terpilih.',
                    'unique' => 'Data sudah diinput (kombinasi User & Task sama).',
                ]),
                TextInput::make('document_link')->label('Document Link')->required(),
                Hidden::make('user_id')
                    ->default(fn () => Auth::id())
                    ->visibleOn('create')
            ]);
    }
}
