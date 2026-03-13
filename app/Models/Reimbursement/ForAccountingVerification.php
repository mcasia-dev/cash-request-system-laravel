<?php

namespace App\Models\Reimbursement;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ForAccountingVerification extends Model
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

    public function reimbursementItems(): HasMany
    {
        return $this->hasMany(ReimbursementItem::class, 'reimbursement_id', 'id');
    }
}

