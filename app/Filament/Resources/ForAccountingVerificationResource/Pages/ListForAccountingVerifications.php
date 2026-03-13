<?php

namespace App\Filament\Resources\ForAccountingVerificationResource\Pages;

use App\Filament\Resources\ForAccountingVerificationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListForAccountingVerifications extends ListRecords
{
    protected static string $resource = ForAccountingVerificationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}

