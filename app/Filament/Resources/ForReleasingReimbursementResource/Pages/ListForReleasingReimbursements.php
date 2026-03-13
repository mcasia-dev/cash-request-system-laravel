<?php

namespace App\Filament\Resources\ForReleasingReimbursementResource\Pages;

use App\Filament\Resources\ForReleasingReimbursementResource;
use Filament\Resources\Pages\ListRecords;

class ListForReleasingReimbursements extends ListRecords
{
    protected static string $resource = ForReleasingReimbursementResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}

