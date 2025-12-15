<?php

namespace App\Filament\Resources\TaskDetails\Tables;

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
use Illuminate\Support\Str;

class TaskDetailsTable
{
    public static function configure(Table $table): Table
    {
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
                TextColumn::make('task.schedule.subject.name')->label('Subject')->sortable(),
                TextColumn::make('task.schedule.note')->label('Note'),
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
                    ->options(fn () => Subject::query()->orderBy('name')->pluck('name', 'id')->toArray())
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
                    ->relationship('task', 'name') // karena task_details punya relasi task()
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