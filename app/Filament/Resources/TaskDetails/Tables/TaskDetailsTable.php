<?php

namespace App\Filament\Resources\TaskDetails\Tables;

use App\Models\Task;
use App\Models\Period;
use App\Models\Schedule;
use App\Models\Subject;
use Filament\Forms\Components\Select as FormsSelect;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\TextInputColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class TaskDetailsTable
{
    public static function configure(Table $table): Table
    {
        $defaultPeriodId = Period::query()->where('default', true)->value('id');

        return $table
            ->persistFiltersInSession()
            ->persistSearchInSession()
            ->persistSortInSession()        
            ->recordUrl(fn ($record) => null)
            ->columns([
                TextColumn::make('user.name')
                ->label('Student')
                ->formatStateUsing(function ($state, $record) {
                    $email = $record->user?->email;
                    $prefix = $email ? Str::before($email, '@') : '—';
                    $name = $record->user?->name ?? '—';
                    return "{$prefix}-{$name}";
                })
                ->searchable(
                    query: function (Builder $query, string $search): Builder {
                        return $query->whereHas('user', function (Builder $q) use ($search) {
                            $q->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                        });
                    }
                )   // cari pakai nama & email
                ->tooltip(fn ($record) => $record->user?->email)
                ->sortable(),
                TextColumn::make('subject_note')
                    ->label('Subject')
                    ->state(fn ($record): array => [
                        $record->task?->schedule?->subject?->name ?? '-',
                        $record->task?->schedule?->note ?? '-',
                    ])
                    ->listWithLineBreaks()
                    ->wrap(),
                TextColumn::make('task.name')->label('Name')->sortable(),
                TextColumn::make('task.percentage')->label('Percentage'),

                TextInputColumn::make('score') // atau 'score' kalau kolommu itu
                    ->label('Score')
                    ->alignRight()
                    ->rules(['nullable','numeric','min:0','max:100'])
                    ->extraInputAttributes(['type' => 'number','step' => '1','min' => '0','max' => '100'])
                    ->sortable()
                    // non-superadmin: bisa melihat nilainya, tapi tidak bisa mengedit
                    ->disabled(fn () => Auth::user()?->role?->name !== 'superadmin')
                    ->sortable(),
                TextColumn::make('document_link')
                    ->label('Link')
                    // tampilkan teks pendek saja
                    ->formatStateUsing(fn ($state) => filled($state) ? 'Link' : null)
                    // aman saat $record null
                    ->url(function ($record) {
                        $url = $record?->document_link;
                        if (blank($url)) {
                            return null;
                        }
                        return Str::startsWith($url, ['http://', 'https://'])
                            ? $url
                            : "https://{$url}";
                    })
                    ->openUrlInNewTab()
                    // tooltip pakai state (nilainya = document_link)
                    ->tooltip(fn ($state) => $state),

                    TextColumn::make('created_at')
                        ->label('Submit Time')
                        ->dateTime('d-m-Y H:i:s')
                        ->timezone('Asia/Jakarta')
                        ->sortable()

            ])
            ->filters([
                SelectFilter::make('subject_id')
                    ->label('Subject')
                    ->options(fn () => Subject::query()
                        ->when(
                            $defaultPeriodId,
                            fn (Builder $query) => $query->whereIn(
                                'id',
                                Schedule::query()
                                    ->select('subject_id')
                                    ->where('period_id', $defaultPeriodId)
                            ),
                        )
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->toArray())
                    ->modifyFormFieldUsing(
                        fn (FormsSelect $field) => $field
                            ->live()
                            ->afterStateUpdated(fn (Set $set) => $set('/task_id.value', null))
                    )
                    ->searchable()
                    ->preload()
                    ->query(function (Builder $query, array $data): Builder {
                        $subjectId = $data['value'] ?? null;

                        return $query->when($subjectId, function (Builder $q) use ($subjectId) {
                            $q->whereHas('task.schedule', function (Builder $qq) use ($subjectId) {
                                $qq->where('subject_id', $subjectId);
                            });
                        });
                    })
                    ->visible(fn () => Auth::user()?->role?->name === 'superadmin'),

                SelectFilter::make('task_id')
                    ->label('Task')
                    ->modifyFormFieldUsing(
                        fn (FormsSelect $field) => $field
                            ->options(
                                fn (Get $get) => Task::query()
                                    ->when(
                                        $defaultPeriodId,
                                        fn (Builder $query) => $query->whereHas(
                                            'schedule',
                                            fn (Builder $scheduleQuery) => $scheduleQuery->where('period_id', $defaultPeriodId)
                                        ),
                                    )
                                    ->when(
                                        $get('/subject_id.value'),
                                        fn (Builder $query, $subjectId) => $query->whereHas(
                                            'schedule',
                                            fn (Builder $scheduleQuery) => $scheduleQuery->where('subject_id', $subjectId)
                                        ),
                                    )
                                    ->orderBy('index')
                                    ->orderBy('name')
                                    ->pluck('name', 'id')
                                    ->toArray()
                            )
                    )
                    ->searchable()
                    ->preload()
                    ->visible(fn () => Auth::user()?->role?->name === 'superadmin'),
                    SelectFilter::make('score_state')
                        ->label('Score')
                        ->options([
                            'zero' => 'Score = 0',
                            'gt0'  => 'Score > 0',
                        ])
                        ->query(function (Builder $query, array $data): Builder {
                            $value = $data['value'] ?? null;

                            return $query
                                ->when($value === 'zero', fn (Builder $q) => $q->where('score', 0))
                                ->when($value === 'gt0', fn (Builder $q) => $q->where('score', '>', 0));
                        })
                        ->visible(fn () => Auth::user()?->role?->name === 'superadmin'),                    
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->authorizeIndividualRecords(), // ⬅️ ini kuncinya
                ]),
            ]);
    }
}
