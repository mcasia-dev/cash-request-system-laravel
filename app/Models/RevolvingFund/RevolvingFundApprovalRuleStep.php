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
    ];

    protected $casts = [
        'step_order' => 'integer',
    ];

    public function rule(): BelongsTo
    {
        return $this->belongsTo(RevolvingFundApprovalRule::class, 'revolving_fund_approval_rule_id');
    }
}
