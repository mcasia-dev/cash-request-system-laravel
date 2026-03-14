<?php

namespace App\Services\RevolvingFund;

use App\Enums\RevolvingFund\Status;
use App\Enums\RevolvingFund\StatusRemarks;

class RevolvingFundService
{
    public function isVisibleIfPending($record): bool
    {
        return $record->status === Status::PENDING->value && $record->status_remarks === StatusRemarks::FUND_REQUEST_SUBMITTED->value;
    }
}
