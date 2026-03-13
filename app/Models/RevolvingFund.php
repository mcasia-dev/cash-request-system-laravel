<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RevolvingFund extends Model
{
    protected $fillable = [
        'amount',
        'position',
        'user_id',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'user_id' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

