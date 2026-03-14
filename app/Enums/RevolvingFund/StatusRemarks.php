<?php

namespace App\Enums\RevolvingFund;

use App\Traits\EnumsWithOptions;

enum StatusRemarks: string
{
    use EnumsWithOptions;

    case FUND_REQUEST_SUBMITTED = "Fund Request Submitted";
    case APPROVED_BY_HR = "Approved By HR";
    case REJECTED_BY_HR = "Rejected By HR";
    case APPROVED_BY_THE_PRESIDENT = "Approved by the President";
    case REJECTED_BY_THE_PRESIDENT = "Rejected by the President";
    case FOR_REPLENISHMENT = "For Replenishment";
    case REPLENISHED = "Replenished";
}
