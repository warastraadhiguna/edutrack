<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;

class DashboardLogoutWidget extends Widget
{
    protected string $view = 'filament.widgets.dashboard-logout-widget';

    protected int | string | array $columnSpan = 1;
}
