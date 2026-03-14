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
    ];

    protected $casts = [
        'step_order' => 'integer',
        'acted_at' => 'datetime',
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

