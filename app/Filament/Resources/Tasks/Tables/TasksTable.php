<?php

namespace App\Filament\Resources\Tasks\Tables;

use App\Models\Subject;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class TasksTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->recordUrl(fn ($record) => null)
            ->defaultSort('index', 'asc')
            ->columns([
                TextColumn::make('name')->label('Name'),
                TextColumn::make('schedule.subject.name')->label('Subject'),
                TextColumn::make('schedule.note')->label('Note'),
                TextColumn::make('percentage')->label('Percentage'),
                TextColumn::make('index')->label('Indeks'),
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
                    ->visible(fn () => Auth::user()?->role?->name === 'superadmin'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

}