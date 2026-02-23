<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Spatie\Activitylog\LogOptions;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class ForFinanceVerification extends Model implements HasMedia
{
    use InteractsWithMedia;
    protected $table = 'cash_requests';

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
        'voucher_no',
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
        'disbursement_type',
        'check_branch_name',
        'check_no',
        'cut_off_date',
        'payroll_date',
        'payroll_credit',
        'disbursement_added_by', 'is_override'
     ];

    protected $casts = [
        'is_override'     => 'boolean',
        'activity_date'   => 'date',
        'due_date'        => 'date',
        'cut_off_date'    => 'date',
        'payroll_date'    => 'date',
        'date_liquidated' => 'datetime',
        'date_released'   => 'datetime',
    ];

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

    public function activityLists(): HasMany
    {
        return $this->hasMany(ActivityList::class, 'cash_request_id');
    }

    public function cashRequestApprovals(): HasMany
    {
        return $this->hasMany(CashRequestApproval::class, 'cash_request_id');
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
