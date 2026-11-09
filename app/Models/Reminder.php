<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** No real SMS in v1: Send writes a `sent` row; UI copies text / opens sms: handler. */
class Reminder extends Model
{
    protected $fillable = [
        'business_id', 'customer_id', 'customer_name',
        'message', 'tone', 'status', 'date',
    ];

    protected $casts = ['date' => 'date:Y-m-d'];
}
