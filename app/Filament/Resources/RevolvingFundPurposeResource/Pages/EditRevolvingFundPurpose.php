<?php

namespace App\Filament\Resources\RevolvingFundPurposeResource\Pages;

use App\Filament\Resources\RevolvingFundPurposeResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditRevolvingFundPurpose extends EditRecord
{
    protected static string $resource = RevolvingFundPurposeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
