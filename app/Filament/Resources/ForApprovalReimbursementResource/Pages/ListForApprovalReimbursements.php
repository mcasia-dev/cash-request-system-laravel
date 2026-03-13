<?php

namespace App\Filament\Resources\ForApprovalReimbursementResource\Pages;

use App\Filament\Resources\ForApprovalReimbursementResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListForApprovalReimbursements extends ListRecords
{
    protected static string $resource = ForApprovalReimbursementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
