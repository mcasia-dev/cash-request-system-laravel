<?php

namespace App\Models\RevolvingFund;

use App\Enums\RevolvingFund\Status;
use App\Models\RevolvingFundModeOfTransfer;
use App\Models\RevolvingFundPurpose;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
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
        'revolving_fund_mode_of_transfer_id',
        'area_of_assignment',
        'field_work_assignment',
        'other_purpose',
    ];

    protected $casts = [
        'initial_amount' => 'decimal:2',
        'remaining_amount' => 'decimal:2',
        'field_work_assignment' => 'array'
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

    public function purposes(): BelongsToMany
    {
        return $this->belongsToMany(
            RevolvingFundPurpose::class,
            'revolving_fund_revolving_fund_purpose',
            'revolving_fund_id',
        )->withTimestamps();
    }

    public function modeOfTransfer(): BelongsTo
    {
        return $this->belongsTo(RevolvingFundModeOfTransfer::class, 'revolving_fund_mode_of_transfer_id');
    }
}
