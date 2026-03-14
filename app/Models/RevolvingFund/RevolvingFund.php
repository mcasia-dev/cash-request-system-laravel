<?php

namespace App\Models\RevolvingFund;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class RevolvingFund extends Model
{
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

    protected static function booted()
    {
        /**
         * Auto-generate a unique sequential fund number before creation.
         *
         * Format: MCA-RF-####, where the sequence resets each year.
         *
         * @param self $revolvingFund
         * @return void
         */
        static::creating(function ($revolvingFund) {
            $last = static::orderByDesc('id')
                ->lockForUpdate()
                ->first();

            $lastNumber = $last
                ? (int)substr($last->fund_code, -4)
                : 0;

            $next = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);

            $revolvingFund->fund_code = "MCA-RF-{$next}";
        });
    }

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

    public function discussions(): MorphMany
    {
        return $this->morphMany(RequestDiscussion::class, 'discussable')->latest('id');
    }

    public function replenishments(): HasMany
    {
        return $this->hasMany(Replenishment::class);
    }
}
