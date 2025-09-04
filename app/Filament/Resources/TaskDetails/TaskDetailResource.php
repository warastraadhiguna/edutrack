<?php

namespace App\Filament\Resources\TaskDetails;

use App\Filament\Resources\TaskDetails\Pages\CreateTaskDetail;
use App\Filament\Resources\TaskDetails\Pages\EditTaskDetail;
use App\Filament\Resources\TaskDetails\Pages\ListTaskDetails;
use App\Filament\Resources\TaskDetails\Schemas\TaskDetailForm;
use App\Filament\Resources\TaskDetails\Tables\TaskDetailsTable;
use App\Models\Period;
use App\Models\TaskDetail;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class TaskDetailResource extends Resource
{
    protected static ?string $model = TaskDetail::class;
    protected static ?int $navigationSort = 5;
    protected static \UnitEnum|string|null $navigationGroup = 'Semester Plan';
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        // Jika punya superadmin & harus bisa lihat semua, lewati filter:
        if (Auth::user()?->role->name === 'superadmin') {
            return $query;
        }

        // Selain superadmin: hanya data milik user aktif
        return $query->where('user_id', Auth::id());
    }
    protected static ?string $recordTitleAttribute = 'TaskDetail';
    // public static function getEloquentQuery(): Builder
    // {
    //     return parent::getEloquentQuery()
    //         ->with(['task.schedule']) // biar hemat query (eager load)
    //         ->where('user_id', 1)     // atau ->where('user_id', Auth::id())
    //         ->whereHas('task.schedule', function ($q) {
    //             $q->where('period_id', Period::where('default', '1')->first()->id); // ganti sesuai cara ambil "period ini"
    //         });
    // }
    public static function form(Schema $schema): Schema
    {
        return TaskDetailForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TaskDetailsTable::configure($table);
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
            'index' => ListTaskDetails::route('/'),
            'create' => CreateTaskDetail::route('/create'),
            'edit' => EditTaskDetail::route('/{record}/edit'),
        ];
    }
}
