<?php

namespace App\Filament\Resources\ModeOfRequestResource\Pages;

use App\Filament\Resources\ModeOfRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListModeOfRequests extends ListRecords
{
    protected static string $resource = ModeOfRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
