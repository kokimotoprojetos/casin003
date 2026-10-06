<?php

namespace App\Lib;

use App\Models\AdminNotification;
use App\Models\Holiday;
use App\Models\Invest;
use App\Models\Referral;
use App\Models\TimeSetting;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class HyipLab
{
    /**
    * Instance of investor user
    *
    * @var object
    */
    private $user;

    /**
    * Plan which is purchasing
    *
    * @var object
    */
    private $plan;

    /**
    * General setting
    *
    * @var object
    */
    private $setting;

    /**
    * Set some properties
    *
    * @param object $user
    * @param object $plan
    * @return void
    */
    public function __construct($user, $plan)
    {
        $this->user = $user;
        $this->plan = $plan;
        $this->setting = gs();
    }

    /**
    * Invest process
    *
    * @param float $amount
    * @param string $wallet
    * @return void
    */
    public function invest($amount, $wallet){
        DB::transaction(function () use ($amount, $wallet) {
        $plan = $this->plan;
        $user = $this->user;

        // Trava extra: saldo nunca pode ficar negativo (o banco tambem
        // possui CHECK constraints nos wallets como garantia final).
        if ((float) $user->{$wallet} < (float) $amount) {
            throw new \RuntimeException('__INSUFFICIENT_BALANCE__');
        }

        $user->increment($wallet, -$amount);

        $trx                        = getTrx();
        $transaction                = new Transaction();
        $transaction->user_id       = $user->id;
        $transaction->amount        = $amount;
        $transaction->post_balance  = $user->$wallet;
        $transaction->charge        = 0;
        $transaction->trx_type      = '-';
        $transaction->details       = 'Invested on ' . $plan->name;
        $transaction->trx           = $trx;
        $transaction->wallet_type   = $wallet;
        $transaction->remark        = 'invest';
        $transaction->save();

        $timeName = TimeSetting::where('time', $plan->time)->first();
        if (!$timeName) {
            $timeName = new \stdClass();
            $timeName->name = $plan->time . 'h';
        }

        //start
        if ($plan->interest_type == 1) {
            $interestAmount = ($amount * $plan->interest) / 100;
        } else {
            $interestAmount = $plan->interest;
        }

        $period = ($plan->lifetime == 1) ? -1 : $plan->repeat_time;

        $next = self::nextWorkingDay($plan->time);

        $shouldPay = -1;
        if ($period > 0) {
            $shouldPay = $interestAmount * $period;
        }

        $invest                 = new Invest();
        $invest->user_id        = $user->id;
        $invest->plan_id        = $plan->id;
        $invest->amount         = $amount;
        $invest->interest       = $interestAmount;
        $invest->period         = $period;
        $invest->time_name      = $timeName->name;
        $invest->hours          = $plan->time;
        $invest->next_time      = $next;
        $invest->should_pay     = $shouldPay;
        $invest->status         = 1;
        $invest->wallet_type    = $wallet;
        $invest->capital_status = $plan->capital_back;
        $invest->trx            = $trx;
        $invest->save();

        $firstPurchase = !Invest::where('user_id', $user->id)->where('plan_id', $plan->id)->where('id', '!=', $invest->id)->exists();

        // Seguranca antifraude: comissao somente quando a compra foi custeada
        // por DEPOSITO real do usuario (saldo adicionado pelo admin nao gera comissao).
        $depositFunded = self::purchaseFundedByDeposit($user, $amount, $invest->id);

        if ($firstPurchase && $depositFunded && $this->setting->invest_commission == 1) {
            $commissionType = 'invest_commission';
            self::levelCommission($user, $amount, $commissionType, $trx, $this->setting);
        }

        notify($user, 'INVESTMENT', [
            'trx'          => $invest->trx,
            'amount'       => showAmount($amount),
            'plan_name'    => $plan->name,
            'interest_amount' => showAmount($interestAmount),
            'time' => $plan->lifetime == 1 ? 'lifetime' : $plan->repeat_time.' times',
            'time_name' => $plan->time_name,
            'wallet_type'  => keyToTitle($wallet), 
            'post_balance' => showAmount($user->$wallet),
        ]);


        $adminNotification = new AdminNotification();
        $adminNotification->user_id = $user->id;
        $adminNotification->title = $this->setting->cur_sym.showAmount($amount).' invested to '.$plan->name;
        $adminNotification->click_url = '#';
        $adminNotification->save();
        }); // DB::transaction
    }

    /**
    * Get the next working day of the system
    *
    * @param integer $hours
    * @return string
    */
    public static function nextWorkingDay($hours, $fromTime = null)
    {
        $now = $fromTime ? Carbon::parse($fromTime) : now();
        $setting = gs();
        while(0==0){
            $nextPossible = Carbon::parse($now)->addHours($hours)->toDateTimeString();

            if(!self::isHoliDay($nextPossible,$setting)){
                $next = $nextPossible;
                break;
            }
            $now = $now->addDay();
        }
        return $next;
    }


    /**
    * Check the date is holiday or not
    *
    * @param string $date
    * @param object $setting
    * @return string
    */
    public static function isHoliDay($date,$setting){
        $isHoliday = true;
        $dayName = strtolower(date('D',strtotime($date)));
        $holiday = Holiday::where('date',date('Y-m-d',strtotime($date)))->count();
        $offDay = (array)$setting->off_day;

        if(!array_key_exists($dayName, $offDay)){
            if($holiday == 0){
                $isHoliday = false;
            }
        }

        return $isHoliday;

    }

    /**
    * Verifica se a compra foi custeada por deposito real.
    * Pool = total de depositos pagos; cada compra anterior (em ordem)
    * consome do pool o que couber. A compra atual precisa de pool >= valor.
    */
    public static function purchaseFundedByDeposit($user, $amount, $excludeInvestId = null)
    {
        // Linha do tempo: depositos somam no pool; compras anteriores consomem.
        // Compra que nao cabe no pool usou dinheiro de fora (ex.: saldo do admin)
        // e consome o pool existente (regra rigorosa).
        $deposits = Transaction::where('user_id', $user->id)
            ->where('remark', 'deposit')->where('trx_type', '+')
            ->get(['amount', 'created_at']);

        $events = [];
        foreach ($deposits as $d) {
            $events[] = ['type' => 'dep', 'amount' => (float) $d->amount, 'at' => $d->created_at, 'id' => 0];
        }
        $q = Invest::where('user_id', $user->id)->orderBy('created_at')->orderBy('id');
        if ($excludeInvestId) {
            $q->where('id', '!=', $excludeInvestId);
        }
        foreach ($q->get(['id', 'amount', 'created_at']) as $inv) {
            $events[] = ['type' => 'inv', 'amount' => (float) $inv->amount, 'at' => $inv->created_at, 'id' => $inv->id];
        }
        usort($events, function ($a, $b) {
            return [$a['at'], $a['type'] === 'dep' ? 0 : 1, $a['id']] <=> [$b['at'], $b['type'] === 'dep' ? 0 : 1, $b['id']];
        });

        $pool = 0;
        foreach ($events as $e) {
            if ($e['type'] === 'dep') {
                $pool += $e['amount'];
            } elseif ($pool >= $e['amount']) {
                $pool -= $e['amount'];
            } else {
                $pool = 0;
            }
        }

        return $pool >= (float) $amount;
    }

    /**
    * Give referral commission
    *
    * @param object $user
    * @param float $amount
    * @param string $commissionType
    * @param string $trx
    * @param object $setting
    * @return void
    */
    public static function levelCommission($user, $amount, $commissionType, $trx, $setting){
        $meUser = $user;
        $i = 1;
        $level = Referral::where('commission_type',$commissionType)->count();
        $transactions = [];
        while ($i <= $level) {
            $me = $meUser;
            $refer = $me->referrer;
            if (!$refer) {
                break;
            }

            $commission = Referral::where('commission_type',$commissionType)->where('level', $i)->first();
            if (!$commission) {
                break;
            }

            $com = ($amount * $commission->percent) / 100;
            $refer->interest_wallet += $com;
            $refer->save();

            $transactions[] = [
                'user_id' => $refer->id,
                'amount' => $com,
                'post_balance' => $refer->interest_wallet,
                'charge' => 0,
                'trx_type' => '+',
                'details' => 'level '.$i.' Referral Commission From ' . $user->username,
                'trx' => $trx,
                'wallet_type' =>  'interest_wallet',
                'remark'=>'referral_commission',
                'created_at'=>now()
            ];

            if($commissionType == 'deposit_commission'){
                $comType = 'Deposit';
            }elseif($commissionType == 'invest_return_commission'){
                $comType = 'Investment Return';
            }elseif($commissionType == 'interest_commission'){
                $comType = 'Interest';
            }else{
                $comType = 'Invest';
            }

            notify($refer, 'REFERRAL_COMMISSION', [
                'amount' => showAmount($com),
                'post_balance' => showAmount($refer->interest_wallet),
                'trx' => $trx,
                'level' => ordinal($i),
                'type' => $comType
            ]);

            $meUser = $refer;
            $i++;
        }

        if (!empty($transactions)) {
            Transaction::insert($transactions);
        }
    }
}
