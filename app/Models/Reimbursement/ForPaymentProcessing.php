<?php

namespace App\Models\Reimbursement;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ForPaymentProcessing extends Model
{
    protected $table = 'reimbursements';

    protected $fillable = [
        'reimbursement_no',
        'reimbursement_date',
        'payee_id',
        'reimbursement_mode_id',
        'purpose',
        'total_amount',
        'mode_of_transfer',
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
        'status',
        'status_remarks',
        'reason_for_rejection',
        'approved_by',
        'approved_at',
        'checked_by',
        'checked_at',
        'released_by',
        'released_at',
        'cash_received_at',
    ];

    protected $casts = [
        'cut_off_date' => 'date',
        'is_override' => 'boolean',
        'is_approved_by_treasury_manager' => 'boolean',
        'releasing_date' => 'date',
        'approved_at' => 'datetime',
        'checked_at' => 'datetime',
        'released_at' => 'datetime',
        'cash_received_at' => 'datetime',
    ];

    public function payee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'payee_id');
    }

    public function reimbursementMode(): BelongsTo
    {
        return $this->belongsTo(ModeOfRequest::class, 'reimbursement_mode_id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function checkedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checked_by');
    }

    public function releasedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'released_by');
    }

    public function disbursementAddedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'disbursement_added_by');
    }

    public function reimbursementItems(): HasMany
    {
        return $this->hasMany(ReimbursementItem::class, 'reimbursement_id', 'id');
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
