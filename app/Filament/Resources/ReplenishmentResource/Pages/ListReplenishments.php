<?php

namespace App\Filament\Resources\ReplenishmentResource\Pages;

use App\Filament\Resources\ReplenishmentResource;
use App\Models\RevolvingFund\RevolvingFund;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Auth;

class ListReplenishments extends ListRecords
{
    protected static string $resource = ReplenishmentResource::class;

    protected function getHeaderActions(): array
    {
        $hasFund = RevolvingFund::query()
            ->where('user_id', Auth::id())
            ->exists();

        return [
            Actions\CreateAction::make()
                ->visible($hasFund),

            Actions\Action::make('no_fund_note')
                ->label('You do not have a revolving fund yet. Please request one first.')
                ->disabled()
                ->color('gray')
                ->visible(!$hasFund),
        ];
    }
}
