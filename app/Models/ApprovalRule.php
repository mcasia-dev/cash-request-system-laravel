<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ApprovalRule extends Model
{
    protected $fillable = [
        'nature',
        'min_amount',
        'max_amount',
        'is_active'
    ];

    public function approvalRuleSteps(): HasMany
    {
        return $this->hasMany(ApprovalRuleStep::class);
    }
}
