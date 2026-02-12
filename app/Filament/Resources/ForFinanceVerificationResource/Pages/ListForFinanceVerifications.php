<?php

namespace App\Filament\Resources\ForFinanceVerificationResource\Pages;

use App\Filament\Resources\ForFinanceVerificationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListForFinanceVerifications extends ListRecords
{
    protected static string $resource = ForFinanceVerificationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
