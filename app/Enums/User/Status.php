<?php

namespace App\Enums\User;

use App\Traits\EnumsWithOptions;

enum Status: string
{
    use EnumsWithOptions;

    case PENDING = "pending";
    case APPROVED = "approved";
    case DISAPPROVED = "disapproved";
}
