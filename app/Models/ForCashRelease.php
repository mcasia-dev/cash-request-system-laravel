<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class ForCashRelease extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $fillable = [
        'cash_request_id',
        'released_by',
        'processed_by',
        'remarks',
        'releasing_date',
        'releasing_time_from',
        'releasing_time_to',
        'date_processed',
        'date_released',
        'date_edited',
        'edited_by',
    ];

    protected $casts = [
        'releasing_date'      => 'date',
        'releasing_time_from' => 'datetime:H:i:s',
        'releasing_time_to'   => 'datetime:H:i:s',
        'date_processed'      => 'datetime',
        'date_released'       => 'datetime',
        'date_edited'         => 'datetime',
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
}
