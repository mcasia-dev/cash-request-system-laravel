<?php

namespace App\Filament\Resources\ForPaymentProcessingReimbursementResource\Pages;

use App\Filament\Resources\ForPaymentProcessingReimbursementResource;
use Filament\Resources\Pages\ListRecords;

class ListForPaymentProcessingReimbursements extends ListRecords
{
    protected static string $resource = ForPaymentProcessingReimbursementResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
