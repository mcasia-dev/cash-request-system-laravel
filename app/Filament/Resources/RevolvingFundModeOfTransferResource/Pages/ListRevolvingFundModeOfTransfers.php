<?php

namespace App\Filament\Resources\RevolvingFundModeOfTransferResource\Pages;

use App\Filament\Resources\RevolvingFundModeOfTransferResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListRevolvingFundModeOfTransfers extends ListRecords
{
    protected static string $resource = RevolvingFundModeOfTransferResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
