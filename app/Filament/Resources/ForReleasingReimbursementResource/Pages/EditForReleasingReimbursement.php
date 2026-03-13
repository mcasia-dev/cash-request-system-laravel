<?php

namespace App\Filament\Resources\ForReleasingReimbursementResource\Pages;

use App\Filament\Resources\ForReleasingReimbursementResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditForReleasingReimbursement extends EditRecord
{
    protected static string $resource = ForReleasingReimbursementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}

