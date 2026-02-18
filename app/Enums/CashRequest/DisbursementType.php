<?php

namespace App\Enums\CashRequest;

use App\Traits\EnumsWithOptions;

enum DisbursementType: string
{
    use EnumsWithOptions;

    case CHECK   = "check";
    case PAYROLL = "payroll";
}
