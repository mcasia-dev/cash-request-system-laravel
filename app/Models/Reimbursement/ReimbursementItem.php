<?php

namespace App\Models\Reimbursement;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class ReimbursementItem extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $fillable = [
        'reimbursement_id',
        'item_name',
        'amount',
        'description',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function reimbursement(): BelongsTo
    {
        return $this->belongsTo(Reimbursement::class);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('attachments');
    }
}
