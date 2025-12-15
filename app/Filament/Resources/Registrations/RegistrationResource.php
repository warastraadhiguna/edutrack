<?php

namespace App\Filament\Resources\Registrations;

use App\Filament\Resources\Registrations\Pages\CreateRegistration;
use App\Filament\Resources\Registrations\Pages\EditRegistration;
use App\Filament\Resources\Registrations\Pages\ListRegistrations;
use App\Filament\Resources\Registrations\Schemas\RegistrationForm;
use App\Filament\Resources\Registrations\Tables\RegistrationsTable;
use App\Models\Registration;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class RegistrationResource extends Resource
{
    protected static ?string $model = Registration::class;
    protected static ?int $navigationSort = 3;
    protected static string|BackedEnum|null $navigationIcon = Heroicon::PencilSquare;
    protected static \UnitEnum|string|null $navigationGroup = 'Semester Plan';
    protected static ?string $recordTitleAttribute = 'Registration';
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        // Jika punya superadmin & harus bisa lihat semua, lewati filter:
        if (Auth::user()?->role->name === 'superadmin') {
            return $query;
        }

        // Selain superadmin: hanya data milik user aktif
        return $query->where('student_user_id', Auth::id());
    }

    public static function form(Schema $schema): Schema
    {
        return RegistrationForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return RegistrationsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRegistrations::route('/'),
            'create' => CreateRegistration::route('/create'),
            'edit' => EditRegistration::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool
    {
        // semua role boleh melihat
        return true;
    }

    public static function canView($record): bool
    {
        // semua role boleh lihat detail
        return true;
    }

    public static function canCreate(): bool
    {
        // hanya superadmin boleh create
        return Auth::user()?->role?->name === 'superadmin';
    }

    public static function canEdit($record): bool
    {
        // hanya superadmin boleh edit
        return Auth::user()?->role?->name === 'superadmin';
    }

    public static function canDelete($record): bool
    {
        // hanya superadmin boleh delete
        return Auth::user()?->role?->name === 'superadmin';
    }

    public static function canDeleteAny(): bool
    {
        // bulk delete juga hanya superadmin
        return Auth::user()?->role?->name === 'superadmin';
    }
}