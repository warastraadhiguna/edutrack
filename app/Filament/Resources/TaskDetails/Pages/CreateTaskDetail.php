<?php

namespace App\Filament\Resources\TaskDetails\Pages;

use App\Filament\Resources\TaskDetails\TaskDetailResource;
use Filament\Resources\Pages\CreateRecord;

class CreateTaskDetail extends CreateRecord
{
    protected static string $resource = TaskDetailResource::class;
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
