<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class ForLiquidation extends Model implements HasMedia
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
    ];

    public function cashRequest(): BelongsTo
    {
        return $this->belongsTo(CashRequest::class);
    }
}
