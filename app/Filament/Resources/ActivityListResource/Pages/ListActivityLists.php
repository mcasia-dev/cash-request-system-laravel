<?php

namespace App\Filament\Resources\ActivityListResource\Pages;

use App\Filament\Resources\ActivityListResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListActivityLists extends ListRecords
{
    protected static string $resource = ActivityListResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
