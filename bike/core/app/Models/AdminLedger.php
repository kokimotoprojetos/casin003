<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdminLedger extends Model
{
    protected $table = 'admin_ledgers';
    protected $guarded = ['id'];
}
