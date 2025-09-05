<?php

namespace App\Filament\Resources\TaskDetails\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\TextInputColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class TaskDetailsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->recordUrl(fn ($record) => null)
            ->columns([
                TextColumn::make('user.name')->label('Student'),
                TextColumn::make('task.schedule.subject.name')->label('Subject'),
                TextColumn::make('task.schedule.note')->label('Note'),
                TextColumn::make('task.name')->label('Name'),
                TextColumn::make('task.percentage')->label('Percentage'),

                TextInputColumn::make('score') // atau 'score' kalau kolommu itu
                    ->label('Score')
                    ->alignRight()
                    ->rules(['nullable','numeric','min:0','max:100'])
                    ->extraInputAttributes(['type' => 'number','step' => '1','min' => '0','max' => '100'])
                    ->sortable()
                    // non-superadmin: bisa melihat nilainya, tapi tidak bisa mengedit
                    ->disabled(fn () => Auth::user()?->role?->name !== 'superadmin'),
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
                    DeleteBulkAction::make()->authorizeIndividualRecords(), // ⬅️ ini kuncinya
                ]),
            ]);
    }
}
