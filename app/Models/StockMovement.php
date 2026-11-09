<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Sign convention: restock +, sale −, void + (stock restored). */
class StockMovement extends Model
{
    protected $fillable = [
        'business_id', 'product_id', 'product_name', 'branch_id',
        'type', 'quantity_change', 'cost_price', 'supplier', 'reason', 'date',
    ];

    protected $casts = [
        'quantity_change' => 'float',
        'cost_price' => 'float',
        'date' => 'date:Y-m-d',
    ];
}
