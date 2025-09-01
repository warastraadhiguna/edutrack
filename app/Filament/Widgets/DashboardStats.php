<?php

namespace App\Filament\Widgets;

use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class DashboardStats extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Total User', User::count())
                ->icon('heroicon-o-users')
                // ->url(route('filament.admin.resources.messages.index')),
        ];
    }

}
