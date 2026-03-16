<?php

namespace App\Filament\Resources\Profiles\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\CreateRecord;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Hash;

class ProfileForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')->label('Nama')->required(),
                TextInput::make('email')->label('Email')->disabled(),

                TextInput::make('password')
                    ->label('Password (kosongi jika tidak mengubah)')
                    ->password()
                    ->required(fn ($livewire) => $livewire instanceof CreateRecord)
                    ->same('password_confirmation')
                    ->dehydrated(fn ($state) => filled($state))
                    ->dehydrateStateUsing(fn ($state) => filled($state) ? Hash::make($state) : null)
                    ->maxLength(255),

                TextInput::make('password_confirmation')
                    ->label('Retype Password')
                    ->password()
                    ->required(fn (Get $get): bool => filled($get('password')))
                    ->dehydrated(false)
                    ->maxLength(255),
            ]);
    }
}
