<?php

namespace App\Enums\CashRequest;

use App\Traits\EnumsWithOptions;

enum Status: string
{
    use EnumsWithOptions;

    case PENDING = 'pending';
    case IN_PROGRESS = 'in progress';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
    case CANCELLED = 'cancelled';
    case LIQUIDATED = 'liquidated';
    case RELEASED = 'released';
}
