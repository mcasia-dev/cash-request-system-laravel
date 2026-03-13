<?php

namespace App\Filament\Resources\ForApprovalReimbursementResource\Pages;

use App\Filament\Resources\ForApprovalReimbursementResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditForApprovalReimbursement extends EditRecord
{
    protected static string $resource = ForApprovalReimbursementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
