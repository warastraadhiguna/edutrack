<?php

namespace App\Filament\Resources\Subjects\Pages;

use App\Filament\Resources\Subjects\SubjectResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSubject extends CreateRecord
{
    protected static string $resource = SubjectResource::class;

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
