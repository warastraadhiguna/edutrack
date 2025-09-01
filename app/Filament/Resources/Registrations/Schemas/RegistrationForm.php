<?php

namespace App\Filament\Resources\Registrations\Schemas;

use App\Models\Period;
use App\Models\Schedule;
use App\Models\User;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;

class RegistrationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
            Select::make('student_user_id')
                ->label('Student')
                ->options(
                    fn () =>
                    User::orderBy('name')
                        ->get()
                        ->mapWithKeys(fn ($u) => [
                            $u->id => "{$u->name}  — {$u->email}",
                        ])
                )
                ->searchable()
                ->required(),
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
                Hidden::make('user_id')
                    ->default(Auth::id()),
            ]);
    }
}
