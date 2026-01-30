<?php

namespace App\Filament\Resources\CashRequestResource\Pages;

use App\Filament\Resources\CashRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCashRequest extends EditRecord
{
    protected static string $resource = CashRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
