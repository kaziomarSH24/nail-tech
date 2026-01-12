<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $guarded = [];

    protected $casts = [
        'shopify_product_id' => 'string',
        'shopify_variant_id' => 'string',
        'price' => 'decimal:2',
        'last_synced_at' => 'datetime',
    ];
}
