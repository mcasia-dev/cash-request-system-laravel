<?php

namespace App\Models;

use App\Models\Reimbursement\ReimbursementModeApproval;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Department extends Model
{
    protected $fillable = [
        'department_name',
        'added_by',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function reimbursementModeApprovals(): HasMany
    {
        return $this->hasMany(ReimbursementModeApproval::class);
    }
}
