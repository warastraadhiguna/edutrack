<?php

namespace App\Filament\Resources\Periods;

use App\Filament\Resources\Periods\Pages\CreatePeriod;
use App\Filament\Resources\Periods\Pages\EditPeriod;
use App\Filament\Resources\Periods\Pages\ListPeriods;
use App\Filament\Resources\Periods\Schemas\PeriodForm;
use App\Filament\Resources\Periods\Tables\PeriodsTable;
use App\Models\Period;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class PeriodResource extends Resource
{
    protected static ?string $model = Period::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;
    protected static ?int $navigationSort = 2;
    protected static ?string $recordTitleAttribute = 'Period';

    public static function form(Schema $schema): Schema
    {
        return PeriodForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PeriodsTable::configure($table);
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
            'index' => ListPeriods::route('/'),
            'create' => CreatePeriod::route('/create'),
            'edit' => EditPeriod::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool
    {
        return Auth::user()->role->name === 'superadmin'; // ✅ Hanya admin yang bisa melihat
    }
}
