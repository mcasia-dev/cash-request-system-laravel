<?php

namespace App\Models\RevolvingFund;

use App\Models\RevolvingFundModeOfTransfer;
use App\Models\RevolvingFundPurpose;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
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
        'revolving_fund_mode_of_transfer_id',
        'disbursement_type',
        'disbursement_added_by',
        'check_branch_name',
        'check_no',
        'dv_number',
        'voucher_no',
        'cut_off_date',
        'is_override',
        'is_approved_by_treasury_manager',
        'remarks',
        'releasing_date',
        'releasing_time_from',
        'releasing_time_to',
        'released_by',
        'released_at',
        'area_of_assignment',
        'field_work_assignment',
        'other_purpose',
    ];

    protected $casts = [
        'initial_amount' => 'decimal:2',
        'remaining_amount' => 'decimal:2',
        'field_work_assignment' => 'array',
        'cut_off_date' => 'date',
        'is_override' => 'boolean',
        'is_approved_by_treasury_manager' => 'boolean',
        'releasing_date' => 'date',
        'released_at' => 'datetime',
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

    public function disbursementAddedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'disbursement_added_by');
    }

    public function releasedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'released_by');
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

    public function purposes(): BelongsToMany
    {
        return $this->belongsToMany(
            RevolvingFundPurpose::class,
            'revolving_fund_revolving_fund_purpose',
            'revolving_fund_id',
            'revolving_fund_purpose_id',
        )->withTimestamps();
    }

    public function modeOfTransfer(): BelongsTo
    {
        return $this->belongsTo(RevolvingFundModeOfTransfer::class, 'revolving_fund_mode_of_transfer_id');
    }

    public function isCheckDisbursement(): bool
    {
        return $this->disbursement_type === 'check';
    }

    public function isPayrollDisbursement(): bool
    {
        return $this->disbursement_type === 'payroll';
    }
}
