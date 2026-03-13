<?php

namespace App\Enums\CashRequest;

use App\Traits\EnumsWithOptions;

enum ModeOfTransfer: string
{
    use EnumsWithOptions;

    case FOR_DEPOSIT = "for deposit";
    case FOR_PICKUP = "for pickup";
}
