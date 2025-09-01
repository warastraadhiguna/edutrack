<?php

namespace App\Filament\Resources\Tasks\Schemas;

use App\Models\Period;
use App\Models\Schedule;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;

class TaskForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')->label('Nama')->required(),
                Select::make('schedule_id')
                    ->label('Schedule')
                    ->options(
                        fn () =>
                        Schedule::query()
                            ->where('period_id', Period::where('default', '1')->first()->id)          // << hanya schedule dengan period_id = 1
                            ->with('subject:id,name')        // eager load biar anti N+1
                            ->get()
                            ->mapWithKeys(fn ($s) => [
                                $s->id => $s->subject?->name ?? '—',
                            ])
                    )
                    ->searchable()
                    ->preload()   // opsional, kalau datanya kecil
                    ->required(),
                TextInput::make('percentage')->label('Percentage')->numeric()->required(),
                TextInput::make('index')->label('Index')->minValue(0)->required(),
                Hidden::make('user_id')
                    ->default(Auth::id()),
            ]);
    }
}
