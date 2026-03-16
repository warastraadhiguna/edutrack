<?php

namespace App\Filament\Resources\Tasks\Tables;

use App\Models\Subject;
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

class TasksTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->persistFiltersInSession()
            ->persistSearchInSession()
            ->persistSortInSession()        
            ->recordUrl(fn ($record) => null)
            ->defaultSort('index', 'asc')
            ->columns([
                TextColumn::make('name')->label('Name'),
                TextColumn::make('schedule.subject.name')
                    ->label('Subject / Note')
                    ->wrap()
                    ->description(fn ($record): string => $record->schedule?->note ?: '-', position: 'below'),
                TextInputColumn::make('percentage')
                    ->label('Percentage')
                    ->alignRight()
                    ->rules(['required', 'numeric', 'min:0', 'max:100'])
                    ->extraAttributes(['class' => 'task-inline-input task-inline-input-percentage'])
                    ->extraInputAttributes([
                        'type' => 'number',
                        'step' => '1',
                        'min' => '0',
                        'max' => '100',
                        'style' => 'width: 3.9rem; min-width: 3.9rem;',
                    ])
                    ->disabled(fn () => ! in_array(Auth::user()?->role?->name, ['superadmin', 'admin'], true))
                    ->sortable(),
                TextInputColumn::make('index')
                    ->label('Indeks')
                    ->alignRight()
                    ->rules(['required', 'integer', 'min:0'])
                    ->extraAttributes(['class' => 'task-inline-input task-inline-input-index'])
                    ->extraInputAttributes([
                        'type' => 'number',
                        'step' => '1',
                        'min' => '0',
                        'style' => 'width: 3.6rem; min-width: 3.6rem;',
                    ])
                    ->disabled(fn () => ! in_array(Auth::user()?->role?->name, ['superadmin', 'admin'], true))
                    ->sortable(),
                TextColumn::make('user.name')->label('User'),
            ])
            ->filters([
                SelectFilter::make('subject_id')
                    ->label('Subject')
                    ->options(fn () => Subject::query()->orderBy('name')->pluck('name', 'id')->toArray())
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
                    ->visible(fn () => in_array(Auth::user()?->role?->name, ['superadmin', 'admin'], true)),
            ])
            ->recordActions([
                EditAction::make()
                    ->visible(fn () => in_array(Auth::user()?->role?->name, ['superadmin', 'admin'], true)),
                DeleteAction::make()
                    ->visible(fn () => in_array(Auth::user()?->role?->name, ['superadmin', 'admin'], true)),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn () => in_array(Auth::user()?->role?->name, ['superadmin', 'admin'], true)),
                ]),
            ]);
    }

}
