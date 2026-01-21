<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $guarded = ['id'];
    protected $casts = [
        'items' => 'array',
        'shipping_address' => 'array',
    ];

    //relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
