<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Sale. Items are denormalized JSON so historical profit never shifts.
 * Only `status === "active"` sales count toward every metric.
 */
class Sale extends Model
{
    protected $fillable = [
        'business_id', 'branch_id', 'customer_id', 'customer_name',
        'date_time', 'payment_type', 'total', 'total_cost',
        'status', 'items', 'note',
    ];

    protected $casts = [
        'date_time' => 'datetime',
        'total' => 'float',
        'total_cost' => 'float',
        'items' => 'array',
    ];

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
