<?php

namespace App\Filament\Resources\Schedules\Tables;

use App\Models\Schedule;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SchedulesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->recordUrl(fn ($record) => null)
            ->columns([
                TextColumn::make('period.name')->label('Period'),
                TextColumn::make('subject.code')->label('Code')->searchable(),
                TextColumn::make('subject.name')->label('Subject')->searchable(),
                TextColumn::make('note')->label('Note'),
                TextColumn::make('drive_location')
                    ->label('Google Drive')
                    ->formatStateUsing(fn (?string $state): string => filled($state) ? 'Open folder' : '-')
                    ->url(fn (Schedule $record): ?string => $record->drive_location)
                    ->openUrlInNewTab()
                    ->icon(Heroicon::OutlinedFolderOpen)
                    ->color('primary'),
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
