<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RevolvingFundModeOfTransfer extends Model
{
    protected $fillable = [
        'name',
        'is_published',
    ];

    protected $casts = [
        'is_published' => 'boolean',
    ];

    public function revolvingFunds(): HasMany
    {
        return $this->hasMany(RevolvingFund::class);
    }
}
