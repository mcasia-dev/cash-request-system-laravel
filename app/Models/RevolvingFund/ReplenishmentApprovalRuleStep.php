<?php

namespace App\Models\RevolvingFund;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReplenishmentApprovalRuleStep extends Model
{
    protected $fillable = [
        'replenishment_approval_rule_id',
        'role_name',
        'can_approve',
        'can_reject',
        'can_verify',
        'can_replenish',
        'use_item_selection',
        'form_schema',
        'assigned_user_ids',
        'step_order',
    ];

    protected $casts = [
        'step_order' => 'integer',
        'can_approve' => 'boolean',
        'can_reject' => 'boolean',
        'can_verify' => 'boolean',
        'can_replenish' => 'boolean',
        'use_item_selection' => 'boolean',
        'form_schema' => 'array',
        'assigned_user_ids' => 'array',
    ];

    public function rule(): BelongsTo
    {
        return $this->belongsTo(ReplenishmentApprovalRule::class, 'replenishment_approval_rule_id');
    }
}
