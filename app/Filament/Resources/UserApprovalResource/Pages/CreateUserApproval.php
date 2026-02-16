<?php

namespace App\Filament\Resources\UserApprovalResource\Pages;

use App\Filament\Resources\UserApprovalResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateUserApproval extends CreateRecord
{
    protected static string $resource = UserApprovalResource::class;
}
