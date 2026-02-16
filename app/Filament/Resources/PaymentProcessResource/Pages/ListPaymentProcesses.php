<?php

namespace App\Filament\Resources\PaymentProcessResource\Pages;

use App\Filament\Resources\PaymentProcessResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPaymentProcesses extends ListRecords
{
    protected static string $resource = PaymentProcessResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
