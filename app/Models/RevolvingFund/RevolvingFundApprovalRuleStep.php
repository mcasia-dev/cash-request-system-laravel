<?php

namespace App\Models\RevolvingFund;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RevolvingFundApprovalRuleStep extends Model
{
    protected $fillable = [
        'revolving_fund_approval_rule_id',
        'role_name',
        'step_order',
        'assigned_user_ids',
        'can_approve',
        'can_reject',
        'form_schema',
    ];

    protected $casts = [
        'step_order' => 'integer',
        'assigned_user_ids' => 'array',
        'can_approve' => 'boolean',
        'can_reject' => 'boolean',
        'form_schema' => 'array',
    ];

    public function rule(): BelongsTo
    {
        return $this->belongsTo(RevolvingFundApprovalRule::class, 'revolving_fund_approval_rule_id');
    }
}
