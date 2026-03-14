<?php

namespace App\Enums\RevolvingFund;

use App\Traits\EnumsWithOptions;

enum Status: string
{
    use EnumsWithOptions;

    case PENDING = 'pending';
    case IN_PROGRESS = 'in progress';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
    case REPLENISHED = 'replenished';
}
