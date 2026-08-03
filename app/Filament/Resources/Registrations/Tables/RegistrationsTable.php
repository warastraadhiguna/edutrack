<?php

namespace App\Filament\Resources\Registrations\Tables;

use App\Models\Schedule;
use App\Models\Subject;
use App\Models\Task;
use App\Models\TaskDetail;
use App\Models\User;
use App\Services\RegistrationDriveService;
use App\Services\RegistrationExportService;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\TextInputColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Throwable;

class RegistrationsTable
{
    /**
     * @return array<int, string>
     */
    public static function getSubjectOptions(): array
    {
        return Subject::query()
            ->whereIn(
                'id',
                Schedule::query()
                    ->select('subject_id')
                    ->whereHas('period', fn (Builder $query) => $query->where('default', true))
            )
            ->orderBy('name')
            ->pluck('name', 'id')
            ->toArray();
    }

    public static function configure(Table $table): Table
    {
        return $table
            ->persistFiltersInSession()
            ->persistSearchInSession()
            ->persistSortInSession()
            ->defaultSort(fn (Builder $query): Builder => self::orderByStudentEmail($query))
            ->recordUrl(fn ($record) => null)
            ->columns([
                TextColumn::make('student_email')
                    ->label('Student')
                    ->state(fn ($record): array => [
                        $record->studentUser?->name ?? '-',
                        RegistrationExportService::shortEmail($record->studentUser?->email),
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
                        return self::orderByStudentEmail($query, $direction);
                    }),
                TextColumn::make('subject_note')
                    ->label('Subject')
                    ->state(fn ($record): array => [
                        $record->schedule?->subject?->name ?? '-',
                        $record->schedule?->note ?? '-',
                    ])
                    ->listWithLineBreaks()
                    ->wrap(),
                TextColumn::make('drive_location')
                    ->label('Google Drive')
                    ->formatStateUsing(fn (?string $state): string => filled($state) ? 'Open folder' : '-')
                    ->url(fn ($record): ?string => $record->drive_location)
                    ->openUrlInNewTab()
                    ->icon(Heroicon::OutlinedFolderOpen)
                    ->color('primary'),
                TextColumn::make('user.name')->label('User'),
                TextColumn::make('task_progress')
                    ->label('Submited Task')
                    ->state(function ($record) {
                        $studentId = $record->student_user_id;
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
                        $studentId = $record->student_user_id ?? null;
                        $scheduleId = $record->schedule_id ?? null;

                        if (! $studentId || ! $scheduleId) {
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
            ->headerActions([
                Action::make('createDrive')
                    ->label('Create Drive')
                    ->icon(Heroicon::OutlinedFolderPlus)
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Create Student Drive Folders')
                    ->modalDescription('Create one folder for every registration in the selected subject and save each folder link.')
                    ->modalSubmitActionLabel('Create folders')
                    ->action(function ($livewire, Action $action): void {
                        $subjectId = $livewire->getTableFilterState('subject_id')['value'] ?? null;

                        if (blank($subjectId)) {
                            $action->halt();

                            return;
                        }

                        try {
                            $result = app(RegistrationDriveService::class)->createFolders((int) $subjectId);
                        } catch (Throwable $exception) {
                            Notification::make()
                                ->title('Drive folders were not completed')
                                ->body($exception->getMessage())
                                ->danger()
                                ->persistent()
                                ->send();

                            $action->halt();

                            return;
                        }

                        Notification::make()
                            ->title('Drive folders completed')
                            ->body("{$result['created']} created, {$result['linked']} existing folders linked, and {$result['skipped']} registrations skipped.")
                            ->success()
                            ->send();
                    })
                    ->visible(fn ($livewire): bool => Auth::user()?->role?->name === 'superadmin'
                        && filled($livewire->getTableFilterState('subject_id')['value'] ?? null)),
                ActionGroup::make([
                    Action::make('exportPdf')
                        ->label('Export PDF')
                        ->icon(Heroicon::OutlinedDocumentArrowDown)
                        ->action(fn ($livewire) => app(RegistrationExportService::class)
                            ->downloadPdf($livewire->getTableQueryForExport())),
                    Action::make('exportExcel')
                        ->label('Export Excel')
                        ->icon(Heroicon::OutlinedTableCells)
                        ->action(fn ($livewire) => app(RegistrationExportService::class)
                            ->downloadExcel($livewire->getTableQueryForExport())),
                ])
                    ->label('Export')
                    ->icon(Heroicon::OutlinedArrowDownTray)
                    ->visible(fn () => Auth::user()?->role?->name === 'superadmin'),
            ])
            ->filters([
                SelectFilter::make('subject_id')
                    ->label('Subject')
                    ->options(fn () => self::getSubjectOptions())
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
                        'graded' => 'Sudah dinilai',
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

    public static function orderByStudentEmail(Builder $query, string $direction = 'asc'): Builder
    {
        return $query->orderBy(
            User::query()
                ->select('email')
                ->whereColumn('users.id', 'registrations.student_user_id'),
            $direction,
        );
    }
}
