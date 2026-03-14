<?php

namespace App\Models\RevolvingFund;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RevolvingFundApprovalRule extends Model
{
    protected $fillable = [
        'min_amount',
        'max_amount',
        'is_active',
    ];

    public function steps(): HasMany
    {
        return $this->hasMany(RevolvingFundApprovalRuleStep::class)
            ->orderBy('step_order')
            ->orderBy('id');
    }
}
