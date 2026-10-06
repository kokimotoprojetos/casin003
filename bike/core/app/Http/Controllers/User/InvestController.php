<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Gateway\PaymentController;
use App\Lib\HyipLab;
use App\Models\GatewayCurrency;
use App\Models\Invest;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InvestController extends Controller
{
    public function invest(Request $request)
    {
        $notify = [];
        $request->validate([
            'amount'        => 'required|numeric|gt:0',
            'plan_id'       => 'required',
            'wallet_type'   => 'required',
        ]);
        $user   = auth()->user();
        $plan   = Plan::where('status',1)->findOrFail($request->plan_id);
        $amount = $request->amount;

        // Carteira única: todos os saldos vivem no interest_wallet
        $request->merge(['wallet_type' => 'interest_wallet']);

        //Check limit
        if($plan->fixed_amount > 0){
            if ($amount != $plan->fixed_amount) {
                $notify[] = ['error','Verifique o limite de investimento'];
                return back()->withNotify($notify);
            }
        }else{
            if ($request->amount < $plan->minimum || $request->amount > $plan->maximum) {
                $notify[] = ['error','Verifique o limite de investimento'];
                return back()->withNotify($notify);
            }
        }

        // Limite de compras por plano (max_compras: 0 = ilimitado)
        if (!empty($plan->max_compras) && Invest::where('user_id', $user->id)->where('plan_id', $plan->id)->count() >= (int) $plan->max_compras) {
            $notify[] = ['error', 'Você já atingiu o limite de compras deste plano.'];
            return back()->withNotify($notify);
        }

        $wallet = $request->wallet_type;

        //Direct checkout
        if ($wallet != 'deposit_wallet' && $wallet != 'interest_wallet') {

            $gate = GatewayCurrency::whereHas('method', function ($gate) {
                $gate->where('status', 1);
            })->find($request->wallet_type);

            if (!$gate) {
                $notify[] = ['error', 'Gateway inválido'];
                return back()->withNotify($notify);
            }

            if ($gate->min_amount > $request->amount || $gate->max_amount < $request->amount) {
                $notify[] = ['error', 'Respeite o limite de depósito'];
                return back()->withNotify($notify);
            }

            $data = PaymentController::insertDeposit($gate,$request->amount,$plan);
            session()->put('Track', $data->trx);
            return to_route('user.deposit.confirm');
        }

        if ($request->amount > $user->$wallet) {
            $notify[] = ['error', 'Saldo insuficiente'];
            return back()->withNotify($notify);
        }

        // Debug diária corrida: lock na linha do usuário + re-checagem dentro da
        // mesma transação para impedir double-invest (dois envios paralelos).
        $invested = DB::transaction(function () use ($user, $amount, $wallet, $plan) {
            $lockedUser = User::whereKey($user->id)->lockForUpdate()->first();

            if (!$lockedUser || $amount > $lockedUser->{$wallet}) {
                return false;
            }

            $hyip = new HyipLab($lockedUser, $plan);
            $hyip->invest($amount, $wallet);

            return true;
        });

        if ($invested !== true) {
            $notify[] = ['error', 'Saldo insuficiente'];
            return back()->withNotify($notify);
        }

        $notify[] = ['success','Investimento realizado com sucesso'];
        return to_route('user.invest.log')->withNotify($notify);
    }

    public function statistics()
    {
        $pageTitle = 'Estatísticas de Investimento';
        $invests    = Invest::where('user_id',auth()->id())->orderBy('id','desc')->with('plan')->where('status',1)->paginate(getPaginate(10));
        $activePlan = Invest::where('user_id', auth()->id())->where('status', 1)->count();

        $investChart = Invest::where('user_id',auth()->id())->with('plan')->groupBy('plan_id')->select('plan_id')->selectRaw("SUM(amount) as investAmount")->orderBy('investAmount', 'desc')->get();
        return view($this->activeTemplate.'user.invest_statistics',compact('pageTitle','invests','investChart', 'activePlan'));
    }

    public function log()
    {
        $pageTitle = 'Histórico de Investimentos';
        $invests = Invest::where('user_id',auth()->id())->orderBy('id','desc')->with('plan')->paginate(getPaginate());
        return view($this->activeTemplate.'user.invests',compact('pageTitle','invests'));
    }
}
