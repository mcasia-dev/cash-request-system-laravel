<?php

namespace App\Filament\Resources\ForLiquidationResource\Pages;

use App\Filament\Resources\ForLiquidationResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditForLiquidation extends EditRecord
{
    protected static string $resource = ForLiquidationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
