<?php

namespace App\Models\RevolvingFund;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ForApprovalReplenishment extends Model
{
    protected $table = 'replenishments';

    protected $fillable = [
        'revolving_fund_id',
        'initial_amount',
        'remaining_amount',
        'total_amount',
        'amount_to_return',
        'amount_to_deduct',
        'status',
        'status_remarks',
        'reason_for_rejection',
        'reviewed_by',
        'reviewed_at',
        'replenished_by',
        'replenished_at',
    ];

    protected $casts = [
        'initial_amount' => 'decimal:2',
        'remaining_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'amount_to_return' => 'decimal:2',
        'amount_to_deduct' => 'decimal:2',
        'reviewed_at' => 'datetime',
        'replenished_at' => 'datetime',
    ];

    public function revolvingFund(): BelongsTo
    {
        return $this->belongsTo(RevolvingFund::class);
    }

    public function replenishmentItems(): HasMany
    {
        return $this->hasMany(ReplenishmentItem::class, 'replenishment_id');
    }

    public function replenishmentApprovals(): HasMany
    {
        return $this->hasMany(ReplenishmentApproval::class, 'replenishment_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function replenishedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'replenished_by');
    }
}
