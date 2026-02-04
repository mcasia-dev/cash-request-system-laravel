<?php

namespace App\Filament\Resources\ForCashReleaseResource\Pages;

use App\Filament\Resources\ForCashReleaseResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditForCashRelease extends EditRecord
{
    protected static string $resource = ForCashReleaseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
