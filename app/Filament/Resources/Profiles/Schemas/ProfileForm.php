<?php

namespace App\Filament\Resources\Profiles\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\CreateRecord;
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
                    ->dehydrated(fn ($state) => filled($state))
                    ->dehydrateStateUsing(fn ($state) => filled($state) ? Hash::make($state) : null)
                    ->maxLength(255)
            ]);
    }
}
