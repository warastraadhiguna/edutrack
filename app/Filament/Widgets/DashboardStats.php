<?php

namespace App\Filament\Widgets;

use App\Models\Registration;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class DashboardStats extends BaseWidget
{
    protected int | string | array $columnSpan = 1;

    protected int | array | null $columns = 1;

    protected function getStats(): array
    {
        return [
            Stat::make('User Aktif', static::getActiveUsersSummary())
                ->icon('heroicon-o-users')
                ->description('Registrasi period default / total user mahasiswa'),
        ];
    }

    public static function getActiveUsersSummary(): string
    {
        return static::getRegisteredUsersCount() . '/' . static::getTotalStudentUsersCount();
    }

    public static function getRegisteredUsersCount(): int
    {
        return Registration::query()
            ->whereHas('schedule.period', fn ($query) => $query->where('default', 1))
            ->distinct('student_user_id')
            ->count('student_user_id');
    }

    public static function getTotalStudentUsersCount(): int
    {
        return User::query()
            ->where('role_id', 3)
            ->count();
    }
}
