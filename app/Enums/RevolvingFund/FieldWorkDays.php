<?php

namespace App\Enums\RevolvingFund;

use App\Traits\EnumsWithOptions;

enum FieldWorkDays: string
{
    use EnumsWithOptions;

    case MONDAY = 'Monday';
    case TUESDAY = 'Tuesday';
    case WEDNESDAY = 'Wednesday';
    case THURSDAY = 'Thursday';
    case FRIDAY = 'Friday';
}
