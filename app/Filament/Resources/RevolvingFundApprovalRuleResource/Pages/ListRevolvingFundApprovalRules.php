<?php

namespace App\Filament\Resources\RevolvingFundApprovalRuleResource\Pages;

use App\Filament\Resources\RevolvingFundApprovalRuleResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListRevolvingFundApprovalRules extends ListRecords
{
    protected static string $resource = RevolvingFundApprovalRuleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
