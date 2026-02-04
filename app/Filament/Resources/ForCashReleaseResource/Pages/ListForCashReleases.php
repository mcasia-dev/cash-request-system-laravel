<?php

namespace App\Filament\Resources\ForCashReleaseResource\Pages;

use App\Filament\Resources\ForCashReleaseResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListForCashReleases extends ListRecords
{
    protected static string $resource = ForCashReleaseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
