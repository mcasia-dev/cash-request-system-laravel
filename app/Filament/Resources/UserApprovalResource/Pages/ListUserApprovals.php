<?php

namespace App\Filament\Resources\UserApprovalResource\Pages;

use App\Filament\Resources\UserApprovalResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListUserApprovals extends ListRecords
{
    protected static string $resource = UserApprovalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
