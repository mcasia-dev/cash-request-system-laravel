<?php

namespace App\Models\CashRequest;

use App\Enums\CashRequest\DisbursementType;
use App\Interface\HasDisbursementType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class ForLiquidation extends Model implements HasMedia, HasDisbursementType
{
    use InteractsWithMedia;

    protected $fillable = [
        'cash_request_id',
        'receipt_amount',
        'remarks',
        'total_user',
        'total_liquidated',
        'total_change',
        'missing_amount',
        'aging',
        'is_override',
        'is_approved_by_treasury_manager'
    ];

    protected $casts = [
        'is_override' => 'boolean',
        'is_approved_by_treasury_manager'
    ];

    public function cashRequest(): BelongsTo
    {
        return $this->belongsTo(CashRequest::class);
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
