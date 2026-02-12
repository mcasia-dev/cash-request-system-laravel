<?php

namespace App\Filament\Resources\ForFinanceVerificationResource\Pages;

use App\Filament\Resources\ForFinanceVerificationResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditForFinanceVerification extends EditRecord
{
    protected static string $resource = ForFinanceVerificationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
