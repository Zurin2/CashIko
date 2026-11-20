<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** A payment channel (cash, GCash, Maya, utang, or custom wallet) with optional QR. */
class PaymentMethod extends Model
{
    protected $fillable = [
        'business_id', 'code', 'label', 'account_name',
        'account_number', 'qr_path', 'is_active', 'sort',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort' => 'integer',
    ];

    public function business()
    {
        return $this->belongsTo(Business::class);
    }
}
