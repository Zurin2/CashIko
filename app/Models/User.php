<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'is_superadmin',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_superadmin' => 'boolean',
    ];

    /** One Business per owner account (v1 tenancy rule). */
    public function business()
    {
        return $this->hasOne(Business::class, 'user_id');
    }

    public function branches()
    {
        return $this->business ? $this->business->branches() : Branch::query()->whereRaw('1 = 0');
    }
}
