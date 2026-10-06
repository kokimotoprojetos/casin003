<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BonusCode extends Model
{
    protected $fillable = ['code', 'amount', 'max_uses', 'uses', 'status', 'expires_at'];
    protected $casts = ['expires_at' => 'datetime'];
}
