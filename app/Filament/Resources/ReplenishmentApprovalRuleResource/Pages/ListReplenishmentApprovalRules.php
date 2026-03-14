<?php

namespace App\Filament\Resources\ReplenishmentApprovalRuleResource\Pages;

use App\Filament\Resources\ReplenishmentApprovalRuleResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListReplenishmentApprovalRules extends ListRecords
{
    protected static string $resource = ReplenishmentApprovalRuleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}

