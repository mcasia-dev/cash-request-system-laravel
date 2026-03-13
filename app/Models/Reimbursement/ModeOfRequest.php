<?php

namespace App\Models\Reimbursement;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ModeOfRequest extends Model
{
    protected $fillable = [
        'name',
        'code',
        'description',
        'is_active',
    ];

    public function reimbursementModeApprovals(): HasMany
    {
        return $this->hasMany(ReimbursementModeApproval::class, 'reimbursement_mode_id')
            ->orderBy('step_no');
    }

    public function reimbursements(): HasMany
    {
        return $this->hasMany(Reimbursement::class, 'reimbursement_mode_id');
    }
}
