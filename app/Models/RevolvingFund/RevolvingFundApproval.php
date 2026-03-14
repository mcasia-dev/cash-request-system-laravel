<?php

namespace App\Models\RevolvingFund;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RevolvingFundApproval extends Model
{
    protected $fillable = [
        'revolving_fund_id',
        'step_order',
        'role_name',
        'approved_by',
        'status',
        'acted_at',
    ];

    protected $casts = [
        'step_order' => 'integer',
        'acted_at' => 'datetime',
    ];

    public function revolvingFund(): BelongsTo
    {
        return $this->belongsTo(RevolvingFund::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
