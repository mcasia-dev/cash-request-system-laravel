<?php

namespace App\Models\RevolvingFund;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReplenishmentApprovalRuleStep extends Model
{
    protected $fillable = [
        'replenishment_approval_rule_id',
        'role_name',
        'step_order',
    ];

    protected $casts = [
        'step_order' => 'integer',
    ];

    public function rule(): BelongsTo
    {
        return $this->belongsTo(ReplenishmentApprovalRule::class, 'replenishment_approval_rule_id');
    }
}

