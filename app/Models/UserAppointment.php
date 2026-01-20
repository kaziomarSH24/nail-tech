<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class UserAppointment extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'appointment_at' => 'datetime',
        'is_reminder_on' => 'boolean',
        'is_reminder_sent' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    //scope
    public function scopeDate(Builder $query, $date)
    {
        return $query->whereDate('appointment_at', $date);
    }
}
