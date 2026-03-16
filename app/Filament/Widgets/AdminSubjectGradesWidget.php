<?php

namespace App\Filament\Widgets;

use App\Models\Period;
use App\Models\Registration;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class AdminSubjectGradesWidget extends TableWidget
{
    protected int | string | array $columnSpan = 'full';

    public static function canView(): bool
    {
        return Auth::user()?->role_id !== 3;
    }

    public static function getSubjectGradeSummaryQuery(): Builder
    {
        return Registration::query()
            ->selectRaw('MIN(registrations.id) as id')
            ->selectRaw('subjects.name as subject_name')
            ->selectRaw("SUM(CASE WHEN registrations.grade IS NOT NULL AND registrations.grade <> '' THEN 1 ELSE 0 END) as filled_grade_count")
            ->selectRaw('COUNT(*) as total_students')
            ->join('schedules', 'schedules.id', '=', 'registrations.schedule_id')
            ->join('subjects', 'subjects.id', '=', 'schedules.subject_id')
            ->whereExists(function ($query): void {
                $query
                    ->selectRaw('1')
                    ->from('periods')
                    ->whereColumn('periods.id', 'schedules.period_id')
                    ->where('periods.default', 1);
            })
            ->groupBy('subjects.id', 'subjects.name')
            ->orderBy('subjects.name');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(static::getSubjectGradeSummaryQuery())
            ->heading('Monitoring Nilai Mata Kuliah')
            ->description('Daftar mata kuliah pada period default dan progres pengisian grade.')
            ->paginated(false)
            ->defaultKeySort(false)
            ->recordUrl(null)
            ->columns([
                TextColumn::make('no')
                    ->label('No')
                    ->rowIndex(),
                TextColumn::make('subject_name')
                    ->label('Mata Kuliah Pada Periode Ini'),
                TextColumn::make('grade_progress')
                    ->label('Grade Terisi / Total Mahasiswa')
                    ->state(fn ($record): string => "{$record->filled_grade_count}/{$record->total_students}"),
            ]);
    }

    protected function hasTablePagination(): bool
    {
        return false;
    }
}
