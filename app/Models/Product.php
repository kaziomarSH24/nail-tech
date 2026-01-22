<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Product extends Model
{
    protected $guarded = [];

    protected $casts = [
        'shopify_product_id' => 'string',
        'shopify_variant_id' => 'string',
        'price' => 'decimal:2',
        'last_synced_at' => 'datetime',
        'variants' => 'array',
    ];

    protected $appends = ['stock_status', 'earnable_points'];
    /**
     * Scope to get only Low Stock products
     */
    public function scopeLowStock($query)
    {
        return $query->whereColumn('stock_quantity', '<=', 'low_stock_threshold')
                     ->where('stock_quantity', '>', 0);
    }

    /**
     * Scope to get Out of Stock products
     */
    public function scopeOutOfStock($query)
    {
        return $query->where('stock_quantity', '<=', 0);
    }

    /**
     * Get Stock Status Attribute
     */
    public function getStockStatusAttribute()
    {
        if ($this->stock_quantity <= 0) {
            return 'out_of_stock';
        }
        if ($this->stock_quantity <= $this->low_stock_threshold) {
            return 'low_stock';
        }
        return 'in_stock';
    }


    public function getEarnablePointsAttribute()
    {
        $rate = (float) \App\Models\Setting::getValue('reward_points_per_usd', 100);
        // dd($rate);
        return (int) ($this->price * $rate);
    }

}
