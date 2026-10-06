<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BonusRedemption extends Model
{
    protected $fillable = ['user_id', 'code_id', 'amount'];
}
