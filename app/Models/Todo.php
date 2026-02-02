<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Todo extends Model
{
    protected $guarded = ['id'];
    protected $casts = [
        'due_date' => 'datetime',
        'is_completed' => 'boolean',
    ];

    public function scopeFilterDate($query, $date)
    {
        return $query->whereDate('due_date', $date);
    }
}
