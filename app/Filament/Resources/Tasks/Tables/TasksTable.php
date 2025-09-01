<?php

namespace App\Filament\Resources\Tasks\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

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
                //
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
