<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityList extends Model
{
    protected $fillable = [
        'user_id',
        'control_no',
        'activity_name',
        'activity_date',
        'activity_venue',
        'purpose',
        'nature_of_request',
        'requesting_amount',
    ];

    protected $casts = [
        'activity_date' => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);   
    }
}
