<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
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

    // public function scopeDateBetween(Builder $query, $start, $end)
    // {
    //     return $query->whereBetween('record_date', [$start, $end]);
    // }

    public function scopeDateBetween($query, ...$dates)
    {

        if (isset($dates[0]) && is_string($dates[0]) && str_contains($dates[0], ',')) {
            $range = explode(',', $dates[0]);
            $start = trim($range[0]);
            $end = trim($range[1]);
            return $query->whereBetween('record_date', [$start, $end]);
        }
        if (isset($dates[0]) && is_array($dates[0])) {
            return $query->whereBetween('record_date', $dates[0]);
        }

        if (count($dates) >= 2) {
            return $query->whereBetween('record_date', [$dates[0], $dates[1]]);
        }

        return $query;
    }
}
