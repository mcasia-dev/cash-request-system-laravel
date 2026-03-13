<?php

namespace App\Filament\Resources\ModeOfRequestResource\Pages;

use App\Filament\Resources\ModeOfRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditModeOfRequest extends EditRecord
{
    protected static string $resource = ModeOfRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
