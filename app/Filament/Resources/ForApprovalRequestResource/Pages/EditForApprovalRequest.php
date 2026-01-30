<?php

namespace App\Filament\Resources\ForApprovalRequestResource\Pages;

use App\Filament\Resources\ForApprovalRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditForApprovalRequest extends EditRecord
{
    protected static string $resource = ForApprovalRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
