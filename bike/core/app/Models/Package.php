<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Package extends Model
{
    protected $guarded = ['id'];

    public function invests()
    {
        return $this->hasMany(Invest::class);
    }
}
