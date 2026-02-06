<?php

namespace App\Models;

use App\Models\ForLiquidation;
use Spatie\MediaLibrary\HasMedia;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\InteractsWithMedia;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LiquidationReceipt extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $fillable = [
        'liquidation_id',
        'receipt_amount',
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
