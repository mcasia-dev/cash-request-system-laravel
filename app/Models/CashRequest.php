<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Spatie\Activitylog\LogOptions;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class CashRequest extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $fillable = [
        'request_no',
        'user_id',
        'activity_name',
        'activity_date',
        'activity_venue',
        'purpose',
        'nature_of_request',
        'requesting_amount',
        'nature_of_payment',
        'reason_for_rejection',
        'reason_for_cancelling',
        'payee',
        'payment_to',
        'bank_account_no',
        'bank_name',
        'account_type',
        'cc_holder_name',
        'cc_number',
        'cc_type',
        'cc_expiration',
        'date_liquidated',
        'date_released',
        'due_date',
        'status',
        'status_remarks',
    ];

    protected $casts = [
        'activity_date'   => 'date',
        'due_date'        => 'date',
        'date_liquidated' => 'datetime',
        'date_released'   => 'datetime',
    ];

    protected static function booted()
    {
        static::creating(function ($cashRequest) {
            $year = now()->year;

            $last = static::whereYear('created_at', $year)
                ->orderByDesc('id')
                ->lockForUpdate()
                ->first();

            $lastNumber = $last
                ? (int) substr($last->request_no, -4)
                : 0;

            $next = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);

            $cashRequest->request_no = "REQ-{$year}-{$next}";
        });
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('attachments');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function forCashRelease(): HasOne
    {
        return $this->hasOne(ForCashRelease::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'request_no',
                'user_id',
                'activity_name',
                'activity_date',
                'activity_venue',
                'purpose',
                'nature_of_request',
                'requesting_amount',
                'nature_of_payment',
                'payee',
                'payment_to',
                'bank_account_no',
                'bank_name',
                'account_type',
                'status',
                'due_date',
                'date_liquidated',
                'date_released',
                'reason_for_rejection',
            ])
            ->logOnlyDirty()
            ->useLogName('cash_request');
    }
}
