<?php

namespace App\Models\RevolvingFund;

use App\Enums\RevolvingFund\Status;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ForApprovalRevolvingFund extends Model
{
    protected $table = 'revolving_funds';

    protected $fillable = [
        'fund_code',
        'initial_amount',
        'remaining_amount',
        'user_id',
        'added_by',
        'status',
        'status_remarks',
    ];

    protected $casts = [
        'initial_amount' => 'decimal:2',
        'remaining_amount' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function addedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'added_by');
    }

    public function revolvingFundApprovals(): HasMany
    {
        return $this->hasMany(RevolvingFundApproval::class, 'revolving_fund_id');
    }

    public function discussions(): HasMany
    {
        return $this->hasMany(RequestDiscussion::class, 'discussable_id')
            ->where('discussable_type', RevolvingFund::class)
            ->latest('id');
    }

    public function scopePendingApproval($query)
    {
        return $query->whereIn('status', [Status::PENDING->value, Status::IN_PROGRESS->value]);
    }
}
