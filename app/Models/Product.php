<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'business_id', 'name', 'category', 'unit', 'quantity',
        'cost_price', 'selling_price', 'low_stock_threshold',
        'branch_id', 'photo_path', 'expiration_date', 'last_sold_at',
    ];

    protected $casts = [
        'quantity' => 'float',
        'cost_price' => 'float',
        'selling_price' => 'float',
        'low_stock_threshold' => 'integer',
        'expiration_date' => 'date:Y-m-d',
        'last_sold_at' => 'datetime',
    ];
}
