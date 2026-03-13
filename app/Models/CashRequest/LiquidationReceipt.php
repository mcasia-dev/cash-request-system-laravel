<?php

namespace App\Models\CashRequest;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class LiquidationReceipt extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $fillable = [
        'liquidation_id',
        'receipt_amount',
        'receipt_number',
        'remarks'
    ];

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('liquidation-receipts');
    }

    public function liquidation(): BelongsTo
    {
        return $this->belongsTo(ForLiquidation::class, 'liquidation_id');
    }
}
