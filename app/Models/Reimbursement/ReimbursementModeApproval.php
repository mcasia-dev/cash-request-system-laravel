<?php

namespace App\Models\Reimbursement;

use App\Models\Department;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReimbursementModeApproval extends Model
{
    protected $fillable = [
        'reimbursement_mode_id',
        'step_no',
        'department_id',
        'role_name',
        'required',
        'assigned_user_ids',
        'can_approve',
        'can_reject',
        'form_schema',
    ];

    protected $casts = [
        'step_no' => 'integer',
        'required' => 'boolean',
        'assigned_user_ids' => 'array',
        'can_approve' => 'boolean',
        'can_reject' => 'boolean',
        'form_schema' => 'array',
    ];

    public function reimbursementMode(): BelongsTo
    {
        return $this->belongsTo(ModeOfRequest::class, 'reimbursement_mode_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }
}
