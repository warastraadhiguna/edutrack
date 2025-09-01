<?php

namespace App\Filament\Resources\Periods\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PeriodsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->recordUrl(fn ($record) => null)
            ->columns([
                TextColumn::make('name')->label('Nama')->searchable(['name']),
                TextColumn::make('default')
                    ->label('Default')
                    ->formatStateUsing(fn ($state) => $state === 1 ? 'Iya' : 'Tidak')
                    ->badge()
                    ->color(fn ($state) => $state === 1 ? 'success' : 'gray'),
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
