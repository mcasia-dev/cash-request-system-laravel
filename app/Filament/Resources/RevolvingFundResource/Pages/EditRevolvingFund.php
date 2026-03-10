<?php

namespace App\Filament\Resources\RevolvingFundResource\Pages;

use App\Filament\Resources\RevolvingFundResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditRevolvingFund extends EditRecord
{
    protected static string $resource = RevolvingFundResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}

