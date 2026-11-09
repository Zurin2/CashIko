<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Persists the AI assistant conversation so it survives reloads. */
class ChatMessage extends Model
{
    protected $fillable = ['business_id', 'role', 'content'];
}
