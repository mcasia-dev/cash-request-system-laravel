<?php
namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'control_no',
        'first_name',
        'middle_name',
        'last_name',
        'position',
        'contact_number',
        'signature_number',
        'department_id',
        'account_status',
        'status',
        'review_by',
        'review_at',
        'reason_for_rejection',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
        ];
    }

    protected static function booted()
    {
        /**
         * Auto-generate the user's control number and full name before creation.
         *
         * Format: MCA-YYYY-####, based on the latest user ID.
         *
         * @param self $user
         * @return void
         */
        static::creating(function ($user) {
            $last_id     = self::latest()->first()->id ?? 0;
            $year        = Carbon::now()->year;
            $random_char = str_pad($last_id + 1, 4, '0', STR_PAD_LEFT);
            $tracking_no = "MCA-{$year}-{$random_char}";

            $user->control_no = $tracking_no;
            $user->name       = "{$user->first_name} {$user->last_name}";
        });
    }

    public function cashRequests(): HasMany
    {
        return $this->hasMany(CashRequest::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }
}
