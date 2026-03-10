<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ForApprovalUser extends Model
{
    protected $table = 'for_approval_users_view';

    protected $fillable = [
        'id',
        'control_no',
        'name',
        'first_name',
        'middle_name',
        'last_name',
        'position',
        'email',
        'email_verified_at',
        'contact_number',
        'signature_number',
        'department_id',
        'account_status',
        'status',
        'review_by',
        'review_at',
        'reason_for_rejection',
        'remember_token',
        'created_at',
        'updated_at',
    ];

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }
}
