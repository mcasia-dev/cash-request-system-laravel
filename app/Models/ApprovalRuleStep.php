<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApprovalRuleStep extends Model
{
    protected $fillable = [
        'approval_rule_id',
        'role_name',
        'step_order',
    ];

    protected $casts = [
        'step_order' => 'integer',
    ];

    public function approvalRule(): BelongsTo
    {
        return $this->belongsTo(ApprovalRule::class);
    }
}
