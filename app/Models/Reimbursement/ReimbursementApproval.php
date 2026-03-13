<?php

namespace App\Models\Reimbursement;

use App\Models\Department;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReimbursementApproval extends Model
{
    protected $fillable = [
        'reimbursement_id',
        'step_no',
        'department_id',
        'role_name',
        'required',
        'status',
        'approved_by',
        'acted_at',
    ];

    protected $casts = [
        'step_no' => 'integer',
        'required' => 'boolean',
        'acted_at' => 'datetime',
    ];

    public function reimbursement(): BelongsTo
    {
        return $this->belongsTo(Reimbursement::class, 'reimbursement_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}

