<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\CreateRecord;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')->label('Nama')->required(),
                TextInput::make('email')->label('Email')->required()->unique(ignoreRecord: true),
                Select::make('role_id')
                    ->label('Role')
                    ->relationship('role', 'name')
                    ->options(fn () => \App\Models\Role::pluck('name', 'id'))
                    ->searchable()
                    ->required(),

                TextInput::make('password')
                    ->label('Password (kosongi jika tidak mengubah)')
                    ->password()
                    ->required(fn ($livewire) => $livewire instanceof CreateRecord)
                    ->dehydrated(fn ($state) => filled($state))
                    ->dehydrateStateUsing(fn ($state) => filled($state) ? Hash::make($state) : null)
                    ->maxLength(255),
                Hidden::make('user_id')
                    ->default(Auth::id()),
            ]);
    }
}
