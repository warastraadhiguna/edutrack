<?php

namespace App\Filament\Resources\Periods\Schemas;

use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;

class PeriodForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Hidden::make('user_id')
                    ->default(Auth::id())
                    ->required(),
                TextInput::make('name')->label('Name')->required(),

                Toggle::make('default')
                    ->label('Default Period')
                    ->onColor('success')
                    ->offColor('danger')
                    ->required()
                    ->dehydrateStateUsing(fn (bool $state) => $state ? 1 : 0)
                    ->afterStateHydrated(fn ($state, $set) => $set('default', $state == 1))  ,
            ]);
    }
}
