<?php

namespace App\Filament\Resources\ForApprovalRevolvingFundResource\Pages;

use App\Filament\Resources\ForApprovalRevolvingFundResource;
use Filament\Resources\Pages\ListRecords;

class ListForApprovalRevolvingFunds extends ListRecords
{
    protected static string $resource = ForApprovalRevolvingFundResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
