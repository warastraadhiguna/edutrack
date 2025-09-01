<?php

namespace App\Filament\Resources\TaskDetails\Pages;

use App\Filament\Resources\TaskDetails\TaskDetailResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTaskDetails extends ListRecords
{
    protected static string $resource = TaskDetailResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
