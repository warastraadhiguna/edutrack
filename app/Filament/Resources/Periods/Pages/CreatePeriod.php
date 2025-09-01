<?php

namespace App\Filament\Resources\Periods\Pages;

use App\Filament\Resources\Periods\PeriodResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePeriod extends CreateRecord
{
    protected static string $resource = PeriodResource::class;

    protected function getFormActions(): array
    {
        return [
          $this->getCreateFormAction()  // Tombol Create default
        ->label('Create')
        ->color('primary'),
          $this->getCancelFormAction()  // Tombol Cancel default
        ->label('Batal')
            ->color('gray'),
      ];
    }

    protected function getRedirectUrl(): string
    {
        // Redirect ke halaman index setelah create
        return $this->getResource()::getUrl('index');
    }
}
