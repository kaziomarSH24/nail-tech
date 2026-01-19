<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserFinanceRecord extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'record_date' => 'date',
        'amount' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
