<?php

namespace App\Models\RevolvingFund;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class ReplenishmentItem extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $fillable = [
        'replenishment_id',
        'expense_name',
        'amount',
        'is_approved',
        'approval_remarks',
        'reviewed_by',
        'reviewed_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'is_approved' => 'boolean',
        'reviewed_at' => 'datetime',
    ];

    public function replenishment(): BelongsTo
    {
        return $this->belongsTo(Replenishment::class);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('attachments');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
