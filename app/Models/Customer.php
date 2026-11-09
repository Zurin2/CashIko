<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Owner's private credit rating lives in `tag`: trusted | watch | none. */
class Customer extends Model
{
    protected $fillable = ['business_id', 'name', 'phone', 'tag'];
}
