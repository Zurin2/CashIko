<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * CashIko domain model: {@link Business} owns {@link Branch}es and scopes every tenant record.
 * All date fields stay ISO strings on the wire so the Inertia/Vue client keeps the
 * format helpers from the spec (todayISO, friendlyDate, daysFromNow, ...).
 */
class Business extends Model
{
    protected $fillable = [
        'user_id', 'name', 'type', 'location', 'plan', 'trial_ends_at',
        'default_due_days', 'default_reminder_tone', 'auto_remind',
        'low_stock_threshold', 'language',
    ];

    protected $casts = [
        'trial_ends_at' => 'date:Y-m-d',
        'auto_remind' => 'boolean',
        'default_due_days' => 'integer',
        'low_stock_threshold' => 'integer',
    ];

    public function owner()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function branches()
    {
        return $this->hasMany(Branch::class)->orderBy('created_at');
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function sales()
    {
        return $this->hasMany(Sale::class);
    }

    public function customers()
    {
        return $this->hasMany(Customer::class)->orderBy('name');
    }

    public function utangEntries()
    {
        return $this->hasMany(UtangEntry::class);
    }

    public function expenses()
    {
        return $this->hasMany(Expense::class);
    }

    public function stockMovements()
    {
        return $this->hasMany(StockMovement::class);
    }

    public function reminders()
    {
        return $this->hasMany(Reminder::class);
    }

    public function chatMessages()
    {
        return $this->hasMany(ChatMessage::class);
    }
}
