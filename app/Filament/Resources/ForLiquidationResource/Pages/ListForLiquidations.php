<?php

namespace App\Filament\Resources\ForLiquidationResource\Pages;

use App\Filament\Resources\ForLiquidationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListForLiquidations extends ListRecords
{
    protected static string $resource = ForLiquidationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
