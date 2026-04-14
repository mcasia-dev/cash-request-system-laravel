<?php

namespace App\Models;

use App\Models\RevolvingFund\RevolvingFund;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class RevolvingFundPurpose extends Model
{
    protected $fillable = [
        'purpose',
        'is_published'
    ];

    protected $casts = [
        'is_published' => 'boolean',
    ];

    public function revolvingFunds(): BelongsToMany
    {
        return $this->belongsToMany(
            RevolvingFund::class,
            'revolving_fund_revolving_fund_purpose'
        )->withTimestamps();
    }
}
