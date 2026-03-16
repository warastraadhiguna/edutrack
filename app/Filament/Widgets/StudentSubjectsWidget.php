<?php

namespace App\Filament\Widgets;

use App\Models\Registration;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class StudentSubjectsWidget extends TableWidget
{
    protected int | string | array $columnSpan = 'full';

    public static function canView(): bool
    {
        return Auth::user()?->role_id === 3;
    }

    public static function getStudentSubjectsQuery(): Builder
    {
        return Registration::query()
            ->with(['schedule.subject', 'schedule.period'])
            ->where('student_user_id', Auth::id())
            ->latest('id');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(static::getStudentSubjectsQuery())
            ->heading('Matakuliah Diambil')
            ->description('Daftar matakuliah yang Anda ambil.')
            ->paginated(false)
            ->recordUrl(null)
            ->columns([
                TextColumn::make('no')
                    ->label('No')
                    ->rowIndex(),
                TextColumn::make('schedule.subject.name')
                    ->label('Nama Subject')
                    ->formatStateUsing(fn (?string $state): string => $state ?: '-'),
                TextColumn::make('schedule.period.name')
                    ->label('Periode')
                    ->formatStateUsing(fn (?string $state): string => $state ?: '-'),
                TextColumn::make('grade')
                    ->label('Grade')
                    ->formatStateUsing(fn (?string $state): string => filled($state) ? strtoupper($state) : '-'),
            ]);
    }
}
