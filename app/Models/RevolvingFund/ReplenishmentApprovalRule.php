<?php

namespace App\Models\RevolvingFund;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReplenishmentApprovalRule extends Model
{
    protected $fillable = [
        'min_amount',
        'max_amount',
        'is_active',
    ];

    public function steps(): HasMany
    {
        return $this->hasMany(ReplenishmentApprovalRuleStep::class)
            ->orderBy('step_order')
            ->orderBy('id');
    }
}

