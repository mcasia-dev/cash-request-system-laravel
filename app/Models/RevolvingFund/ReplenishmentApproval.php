<?php

namespace App\Models\RevolvingFund;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReplenishmentApproval extends Model
{
    protected $fillable = [
        'replenishment_id',
        'step_order',
        'role_name',
        'approved_by',
        'status',
        'acted_at',
        'step_form_data',
        'assigned_user_ids',
        'can_approve',
        'can_reject',
        'can_verify',
        'can_replenish',
        'use_item_selection',
    ];

    protected $casts = [
        'step_order' => 'integer',
        'acted_at' => 'datetime',
        'step_form_data' => 'array',
        'assigned_user_ids' => 'array',
        'can_approve' => 'boolean',
        'can_reject' => 'boolean',
        'can_verify' => 'boolean',
        'can_replenish' => 'boolean',
        'use_item_selection' => 'boolean',
    ];

    public function replenishment(): BelongsTo
    {
        return $this->belongsTo(Replenishment::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
