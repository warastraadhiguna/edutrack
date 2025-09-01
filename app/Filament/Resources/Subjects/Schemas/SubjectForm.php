<?php

namespace App\Filament\Resources\Subjects\Schemas;

use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\TextInput;

class SubjectForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Hidden::make('user_id')
                    ->default(Auth::id())
                    ->required(),
                TextInput::make('code')->label('Code')->required(),
                TextInput::make('name')->label('Name')->required(),
            ]);
    }
}
