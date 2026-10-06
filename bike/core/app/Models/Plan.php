<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    protected $fillable = [
        'name',
        'image',
        'minimum',
        'maximum',
        'fixed_amount',
        'interest',
        'interest_type',
        'time',
        'time_name',
        'status',
        'featured',
        'capital_back',
        'lifetime',
        'repeat_time',
        'sort_order',
        'once_per_user',
        'max_compras',
    ];

    public function invests()
    {
        return $this->hasMany(Invest::class);
    }
}
