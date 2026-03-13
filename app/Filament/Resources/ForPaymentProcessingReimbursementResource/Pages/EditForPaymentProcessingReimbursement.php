<?php

namespace App\Filament\Resources\ForPaymentProcessingReimbursementResource\Pages;

use App\Filament\Resources\ForPaymentProcessingReimbursementResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditForPaymentProcessingReimbursement extends EditRecord
{
    protected static string $resource = ForPaymentProcessingReimbursementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
