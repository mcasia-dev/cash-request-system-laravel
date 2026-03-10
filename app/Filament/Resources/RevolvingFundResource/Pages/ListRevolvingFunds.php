<?php

namespace App\Filament\Resources\RevolvingFundResource\Pages;

use App\Filament\Resources\RevolvingFundResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListRevolvingFunds extends ListRecords
{
    protected static string $resource = RevolvingFundResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}

