<?php

namespace App\Filament\Resources\Periods\Pages;

use App\Filament\Resources\Periods\PeriodResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPeriod extends EditRecord
{
    protected static string $resource = PeriodResource::class;

    protected function getRedirectUrl(): string
    {
        // Redirect ke halaman index setelah create
        return $this->getResource()::getUrl('index');
    }
}
