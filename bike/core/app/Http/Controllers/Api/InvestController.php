<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Controller;
use App\Lib\HyipLab;
use App\Models\GatewayCurrency;
use App\Models\Invest;
use App\Models\Plan;
use App\Models\TimeSetting;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class InvestController extends Controller
{
    public function invest()
    {
        $myInvests      = Invest::with('plan')->where('user_id', auth()->id());
        $notify         = 'Meus Investimentos';
        $modifiedInvest = [];

        if (request()->type == 'active') {
            $myInvests = $myInvests->where('status', 1);
            $notify    = 'Meus Investimentos Ativos';
        } elseif (request()->type == 'closed') {
            $myInvests = $myInvests->where('status', 0);
            $notify    = 'Meus Investimentos Encerrados';
        }

        $myInvests = $myInvests->apiQuery();

        if (!request()->calc) {
            $modifyInvest = [];

            foreach ($myInvests as $invest) {
                
                 if($invest->last_time){
                    $start = $invest->last_time;
                }else{
                    $start = $invest->created_at;
                }
                
                $modifyInvest[] = [
                    'id'              => $invest->id,
                    'user_id'         => $invest->user_id,
                    'plan_id'         => $invest->plan_id,
                    'amount'          => $invest->amount,
                    'interest'        => $invest->interest,
                    'should_pay'      => $invest->should_pay,
                    'paid'            => $invest->paid,
                    'period'          => $invest->period,
                    'hours'           => $invest->hours,
                    'time_name'       => $invest->time_name,
                    'return_rec_time' => $invest->return_rec_time,
                    'next_time'       => $invest->next_time,
                    'next_time_percent' => getAmount(diffDatePercent($start, $invest->next_time)),
                    'status'          => $invest->status,
                    'capital_status'  => $invest->capital_status,
                    'wallet_type'     => $invest->wallet_type,
                    'plan'            => $invest->plan,
                ];
            }

            if (request()->take) {
                $modifiedInvest = [
                    'data' => $modifyInvest,
                ];
            } else {
                $modifiedInvest = [
                    'data'      => $modifyInvest,
                    'next_page' => $myInvests->nextPageUrl(),
                ];
            }

        } else {
            $modifiedInvest = $myInvests;
        }

        return response()->json([
            'remark'  => 'my_invest',
            'status'  => 'success',
            'message' => ['success' => $notify],
            'data'    => [
                'invests' => $modifiedInvest,
            ],
        ]);
    }

    public function storeInvest(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'plan_id' => 'required|integer',
            'amount'  => 'required|numeric|gt:0',
            'wallet'  => 'required',
        ]);

        if ($validator->fails()) {
            return getResponse('validation_error', 'error', $validator->errors()->all());
        }

        $amount = $request->amount;
        $wallet = $request->wallet;
        $user   = auth()->user();
        $plan   = Plan::where('status', 1)->find($request->plan_id);

        if (!$plan) {
            return getResponse('not_found', 'error', ['Plano nao encontrado']);
        }

        if ($plan->fixed_amount > 0) {
            if ($amount != $plan->fixed_amount) {
                return getResponse('limit_error', 'error', 'Verifique o limite de investimento');
            }
        } else {
            if ($amount < $plan->minimum || $amount > $plan->maximum) {
                return getResponse('limit_error', 'error', 'Verifique o limite de investimento');
            }
        }

        // Limite de compras por plano (max_compras: 0 = ilimitado)
        if (!empty($plan->max_compras) && Invest::where('user_id', $user->id)->where('plan_id', $plan->id)->count() >= (int) $plan->max_compras) {
            return getResponse('validation_error', 'error', ['Você já atingiu o limite de compras deste plano.']);
        }

        if ($wallet != 'deposit_wallet' && $wallet != 'interest_wallet') {
            $gate = GatewayCurrency::whereHas('method', function ($gate) {
                $gate->where('status', 1);
            })->where('method_code', $wallet)->first();

            if (!$gate) {
                return getResponse('not_found', 'error', ['Gateway nao encontrado']);
            }

            if ($gate->min_amount > $amount || $gate->max_amount < $amount) {
                return getResponse('limit_error', 'error', ['Respeite o limite de deposito']);
            }

            $deposit = PaymentController::insertDeposit($gate, $amount, $plan);
            $data    = [
                'redirect_url' => route('deposit.app.confirm', encrypt($deposit->id)),
            ];

            return getResponse('deposit_success', 'success', 'Deposito de investimento realizado com sucesso', $data);
        }

        // Lock na linha do usuario + re-checagem dentro da transacao:
        // impede compra dupla em requests paralelos (race condition).
        $invested = DB::transaction(function () use ($user, $amount, $wallet, $plan) {
            $lockedUser = User::whereKey($user->id)->lockForUpdate()->first();

            if (!$lockedUser || $lockedUser->$wallet < $amount) {
                return false;
            }

            $hyip = new HyipLab($lockedUser, $plan);
            $hyip->invest($amount, $wallet);

            return true;
        });

        if ($invested !== true) {
            return getResponse('insufficient_balance', 'error', 'Saldo insuficiente');
        }

        return getResponse('invested', 'success', 'Investimento realizado com sucesso');
    }

    public function allPlans()
    {
        $plans         = Plan::where('status', 1)->orderBy('sort_order')->orderBy('id')->get()
            ->reject(function ($p) {
                $u = auth()->user();
                if (!$u || empty($p->max_compras)) return false;
                $compras = Invest::where('user_id', $u->id)->where('plan_id', $p->id)->count();
                return $compras >= (int) $p->max_compras;
            })->values();
        $modifiedPlans = [];
        $general       = gs();

        foreach ($plans as $plan) {
            $timeName = TimeSetting::where('time', $plan->time)->first();

            if ($plan->lifetime == 0) {
                $totalReturn = 'Total ' . $plan->interest * $plan->repeat_time . ' ' . ($plan->interest_type == 1 ? '%' : $general->cur_text);
                $totalReturn = $plan->capital_back == 1 ? $totalReturn . ' + Capital' : $totalReturn;

                $repeatTime       = 'For ' . $plan->repeat_time . ' ' . $timeName->name;
                $interestValidity = 'Per ' . $timeName->time . ' hours, ' . ' Per ' . $plan->repeat_time . ' ' . $timeName->name;
            } else {
                $totalReturn      = 'Lifetime Earning';
                $repeatTime       = 'For Lifetime';
                $interestValidity = 'Per ' . $timeName->time . ' hours, lifetime';
            }

            $modifiedPlans[] = [
                'id'                => $plan->id,
                'name'              => $plan->name,
                'minimum'           => $plan->minimum,
                'maximum'           => $plan->maximum,
                'fixed_amount'      => $plan->fixed_amount,
                'return'            => showAmount($plan->interest) . ' ' . ($plan->interest_type == 1 ? '%' : $general->cur_text),
                'interest_duration' => 'Every ' . $timeName->name,
                'repeat_time'       => $repeatTime,
                'total_return'      => $totalReturn,
                'interest_validity' => $interestValidity,
            ];
        }

        $notify[] = 'Todos os Planos';

        return response()->json([
            'remark'  => 'plan_data',
            'status'  => 'success',
            'message' => ['success' => $notify],
            'data'    => [
                'plans' => $modifiedPlans,
            ],
        ]);
    }

}
