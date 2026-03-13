<?php

namespace App\Enums\CashRequest;

use App\Traits\EnumsWithOptions;

enum NatureOfRequestEnum: string
{
    use EnumsWithOptions;

    case PETTY_CASH = "petty cash";
    case CASH_ADVANCE = "cash advance";
}
