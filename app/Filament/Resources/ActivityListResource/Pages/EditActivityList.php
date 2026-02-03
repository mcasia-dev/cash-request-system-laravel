<?php

namespace App\Filament\Resources\ActivityListResource\Pages;

use App\Filament\Resources\ActivityListResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditActivityList extends EditRecord
{
    protected static string $resource = ActivityListResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
