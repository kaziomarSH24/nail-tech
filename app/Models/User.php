<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Traits\HasRoles;
use Laravel\Sanctum\HasApiTokens;
use Laravel\Cashier\Billable;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasRoles, HasApiTokens, Billable, LogsActivity;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $guarded = [];
    // protected $fillable = [
    //     'name',
    //     'email',
    //     'password',
    //     'otp',
    //     'otp_expires_at',
    //     'verification_token',
    //     'email_verified_at',
    //     'is_active',
    //     'fcm_token',
    // ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'otp',
        'verification_token',
        'fcm_token',
        'otp_expires_at'
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
            'password' => 'hashed',
        ];
    }

    public function fcmTokens()
    {
        return $this->hasMany(FcmToken::class);
    }

    // Activity Log Configuration, it's also customizable
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'email']) //customize the fields you want to log
            ->logOnlyDirty() //log only the changed fields
            ->setDescriptionForEvent(fn(string $eventName) => "User has been {$eventName}")
            ->useLogName('user_activity');
    }

    //get avatar
    public function getAvatarAttribute($value)
    {
        $fullName = $this->name;

        if (!empty($this->last_name)) {
            $fullName .= '+' . $this->last_name;
        }

        $encodedName = urlencode($fullName);

        return $value
            ?  Storage::disk('public')->url($value)
            : "https://ui-avatars.com/api/?background=random&name={$encodedName}&bold=true";
    }

    public function appointments()
    {
        return $this->hasMany(UserAppointment::class);
    }

    public function financeRecords()
    {
        return $this->hasMany(UserFinanceRecord::class);
    }

    public function pointHistories()
    {
        return $this->hasMany(PointHistory::class);
    }
}
