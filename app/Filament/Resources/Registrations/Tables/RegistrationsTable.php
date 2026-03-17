<?php

namespace App\Filament\Resources\Registrations\Tables;

use App\Models\Subject;
use App\Models\Task;
use App\Models\TaskDetail;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\TextInputColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class RegistrationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->persistFiltersInSession()
            ->persistSearchInSession()
            ->persistSortInSession()           
            ->recordUrl(fn ($record) => null)
            ->columns([
                TextColumn::make('student_email')
                    ->label('Student')
                    ->state(fn ($record): array => [
                        $record->studentUser?->name ?? '-',
                        $record->studentUser?->email ?? '-',
                    ])
                    ->listWithLineBreaks()
                    ->wrap()
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->whereHas('studentUser', function (Builder $q) use ($search) {
                            $q->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                        });
                    })
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return $query
                            ->join('users as student_users', 'registrations.student_user_id', '=', 'student_users.id')
                            ->orderBy('student_users.name', $direction)
                            ->select('registrations.*');
                    }),
                TextColumn::make('subject_note')
                    ->label('Subject')
                    ->state(fn ($record): array => [
                        $record->schedule?->subject?->name ?? '-',
                        $record->schedule?->note ?? '-',
                    ])
                    ->listWithLineBreaks()
                    ->wrap(),
                TextColumn::make('user.name')->label('User'),
                TextColumn::make('task_progress')
                    ->label('Submited Task')
                    ->state(function ($record) {
                        $studentId  = $record->student_user_id;
                        $scheduleId = $record->schedule_id;

                        $totalTasks = Task::query()
                            ->where('schedule_id', $scheduleId)
                            ->count();

                        $filled = TaskDetail::query()
                            ->where('user_id', $studentId)
                            ->whereHas('task', fn ($q) => $q->where('schedule_id', $scheduleId))
                            ->count();

                        return "{$filled} / {$totalTasks}";
                    }),                
                TextColumn::make('total_score')
                    ->label('Total Score')
                    ->alignRight()
                    ->state(function ($record) {
                        $studentId  = $record->student_user_id ?? null;
                        $scheduleId = $record->schedule_id ?? null;

                        if (!$studentId || !$scheduleId) {
                            return 0;
                        }

                        return TaskDetail::query()
                            ->where('user_id', $studentId)
                            ->whereHas('task', fn ($q) => $q->where('schedule_id', $scheduleId))
                            ->sum('score') ?? 0;
                    }),           
                TextInputColumn::make('grade')
                    ->label('Grade')
                    ->alignCenter()
                    ->rules([
                        'nullable',
                        'regex:/^(A|AB|B|BC|C|D|E)$/i',
                    ])
                    ->extraInputAttributes([
                        'style' => 'text-transform: uppercase',
                        'oninput' => 'this.value = this.value.toUpperCase().trim()',
                    ])
                    ->disabled(fn () => Auth::user()?->role?->name !== 'superadmin')
                    ->sortable(),        
            ])
            ->filters([
                SelectFilter::make('subject_id')
                    ->label('Subject')
                    ->options(fn () =>
                        Subject::query()
                            ->orderBy('name')
                            ->pluck('name', 'id')
                            ->toArray()
                    )
                    ->searchable()
                    ->preload()
                    ->query(function (Builder $query, array $data): Builder {
                        $subjectId = $data['value'] ?? null;

                        return $query->when($subjectId, function (Builder $q) use ($subjectId) {
                            $q->whereHas('schedule', function (Builder $qq) use ($subjectId) {
                                $qq->where('subject_id', $subjectId);
                            });
                        });
                    })
                    ->visible(fn () => Auth::user()?->role?->name === 'superadmin'),
                    SelectFilter::make('grade_status')
                        ->label('Penilaian')
                        ->options([
                            'not_graded' => 'Belum dinilai',
                            'graded'     => 'Sudah dinilai',
                        ])
                        ->query(function (Builder $query, array $data): Builder {
                            $value = $data['value'] ?? null;

                            return $query
                                ->when($value === 'not_graded', fn (Builder $q) => $q->whereNull('grade'))
                                ->when($value === 'graded', fn (Builder $q) => $q->whereNotNull('grade'));
                        })
                        ->visible(fn () => Auth::user()?->role?->name === 'superadmin'),                    
            ])
            ->recordActions([
                EditAction::make()
                    ->visible(fn () => Auth::user()?->role?->name === 'superadmin'),
                DeleteAction::make()
                    ->visible(fn () => Auth::user()?->role?->name === 'superadmin'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->visible(fn () => Auth::user()?->role?->name === 'superadmin'),
                ]),
            ]);
    }
}
