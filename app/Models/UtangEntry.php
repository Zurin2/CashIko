<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Double-entry style credit ledger: type utang raises the balance,
 * type payment lowers it. `sale_id` links a credit sale back to its Sale.
 */
class UtangEntry extends Model
{
    protected $fillable = [
        'business_id', 'branch_id', 'customer_id', 'customer_name',
        'sale_id', 'type', 'amount', 'date', 'due_date', 'note', 'recorded_by_id',
    ];

    protected $casts = [
        'amount' => 'float',
        'date' => 'date:Y-m-d',
        'due_date' => 'date:Y-m-d',
    ];
}
