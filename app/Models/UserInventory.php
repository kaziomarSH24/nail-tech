<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class UserInventory extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'last_restocked_at' => 'datetime',
        'next_reminder_at' => 'datetime',
    ];

    // Relations
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function linkedProduct()
    {
        return $this->belongsTo(Product::class, 'linked_product_id');
    }

    // Scopes for Filtering
    public function scopeLowStock($query)
    {
        return $query->whereColumn('current_quantity', '<=', 'reorder_threshold');
    }

    //image URL accessor
    public function getImageAttribute($value)
    {
        if (!$value) {
            return null;
        }


        if (str_starts_with($value, 'http://') || str_starts_with($value, 'https://')) {
            return $value;
        }
        return Storage::disk('public')->url($value);
    }
}
