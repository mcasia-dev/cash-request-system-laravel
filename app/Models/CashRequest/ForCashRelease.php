<?php

namespace App\Models\CashRequest;

use App\Enums\CashRequest\DisbursementType;
use App\Interface\HasDisbursementType;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class ForCashRelease extends Model implements HasMedia, HasDisbursementType
{
    use InteractsWithMedia;

    protected $fillable = [
        'cash_request_id',
        'released_by',
        'processed_by',
        'remarks',
        'proposed_releasing_date',
        'proposed_releasing_time_from',
        'proposed_releasing_time_to',
        'releasing_date',
        'releasing_time_from',
        'releasing_time_to',
        'date_processed',
        'date_released',
        'date_edited',
        'edited_by',
        'update_releasing_date_attempt',
    ];

    protected $casts = [
        'proposed_releasing_date' => 'date',
        'proposed_releasing_time_from' => 'datetime:H:i:s',
        'proposed_releasing_time_to' => 'datetime:H:i:s',
        'releasing_date' => 'date',
        'releasing_time_from' => 'datetime:H:i:s',
        'releasing_time_to' => 'datetime:H:i:s',
        'date_processed' => 'datetime',
        'date_released' => 'datetime',
        'date_edited' => 'datetime',
        'update_releasing_date_attempt' => 'integer',
    ];

    public function cashRequest(): BelongsTo
    {
        return $this->belongsTo(CashRequest::class);
    }

    public function releasedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'released_by');
    }

    public function processedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public function editedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'edited_by');
    }

    public function isCheckDisbursement()
    {
        return $this->cashRequest->disbursement_type === DisbursementType::CHECK->value;
    }

    public function isPayrollDisbursement()
    {
        return $this->cashRequest->disbursement_type === DisbursementType::PAYROLL->value;
    }
}
