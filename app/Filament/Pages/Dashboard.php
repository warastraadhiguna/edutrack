<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\DashboardStats;
use App\Filament\Widgets\DashboardLogoutWidget;
use App\Filament\Widgets\StudentSubjectsWidget;
use App\Filament\Widgets\AdminSubjectGradesWidget;
use Filament\Pages\Page;

class Dashboard extends Page
{
    protected static ?int $navigationSort = -100;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-home';

    protected string $view = 'filament.pages.dashboard';

    protected function getHeaderWidgets(): array
    {
        return [
            DashboardStats::class,
            DashboardLogoutWidget::class,
        ];
    }

    public function getHeaderWidgetsColumns(): int | array
    {
        return [
            'md' => 2,
        ];
    }

    protected function getFooterWidgets(): array
    {
        return [
            StudentSubjectsWidget::class,
            AdminSubjectGradesWidget::class,
        ];
    }
}
