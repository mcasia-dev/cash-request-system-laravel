<?php

namespace App\Filament\Resources\PaymentProcessResource\Pages;

use App\Filament\Resources\PaymentProcessResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPaymentProcess extends EditRecord
{
    protected static string $resource = PaymentProcessResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
