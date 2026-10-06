<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BonusLedger extends Model
{
    protected $table = 'bonus_ledgers';
    protected $guarded = ['id'];

    // BonusController::bonuslist() faz BonusLedger::with(['user', 'bonus']).
    // Sem estas relations a pagina /muitomoney/bonus/uses dava 500
    // (RelationNotFoundException). bonus_ledgers tem user_id e bonus_id.
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function bonus()
    {
        return $this->belongsTo(Bonus::class);
    }
}
