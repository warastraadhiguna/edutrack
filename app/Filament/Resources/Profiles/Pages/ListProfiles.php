<?php

namespace App\Filament\Resources\Profiles\Pages;

use App\Filament\Resources\Profiles\ProfileResource;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Auth;

class ListProfiles extends ListRecords
{
    protected static string $resource = ProfileResource::class;
    public function mount(): void
    {
        $this->redirect(static::getResource()::getUrl('edit', ['record' => Auth::id()]));
    }
}
