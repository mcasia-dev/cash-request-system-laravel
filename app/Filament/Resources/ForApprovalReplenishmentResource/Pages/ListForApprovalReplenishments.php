<?php

namespace App\Filament\Resources\ForApprovalReplenishmentResource\Pages;

use App\Filament\Resources\ForApprovalReplenishmentResource;
use Filament\Resources\Pages\ListRecords;

class ListForApprovalReplenishments extends ListRecords
{
    protected static string $resource = ForApprovalReplenishmentResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}

