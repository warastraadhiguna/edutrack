<?php

namespace App\Filament\Resources\TaskDetails\Pages;

use App\Filament\Resources\TaskDetails\TaskDetailResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditTaskDetail extends EditRecord
{
    protected static string $resource = TaskDetailResource::class;

    protected function getRedirectUrl(): string
    {
        // Redirect ke halaman index setelah create
        return $this->getResource()::getUrl('index');
    }
}
