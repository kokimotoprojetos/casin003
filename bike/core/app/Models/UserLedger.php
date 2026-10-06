<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserLedger extends Model
{
    protected $fillable = [
        'user_id', 'reason', 'perticulation', 'amount', 'credit', 'debit', 'status', 'date',
    ];
}
