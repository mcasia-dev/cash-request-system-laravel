<?php

namespace App\Enums\User;

use App\Traits\EnumsWithOptions;

enum AccountStatus: string
{
    use EnumsWithOptions;

    case ACTIVE = "active";
    case BLOCKED = "blocked";
    case SUSPENDED = "suspended";
}
