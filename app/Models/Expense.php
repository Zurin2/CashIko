<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** When category is "Other" the UI shows custom_category. */
class Expense extends Model
{
    public const CATEGORIES = [
        'Restock/Supplies', 'Utilities', 'Rent', 'Salary/Helper',
        'Transport', 'Internet/Load', 'Other',
    ];

    protected $fillable = [
        'business_id', 'branch_id', 'amount', 'category',
        'custom_category', 'date', 'note',
    ];

    protected $casts = ['amount' => 'float', 'date' => 'date:Y-m-d'];
}
