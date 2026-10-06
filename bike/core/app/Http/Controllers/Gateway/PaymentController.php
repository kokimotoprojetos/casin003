<?php
namespace App\Http\Controllers\Gateway;

use App\Http\Controllers\Controller;
use App\Lib\FormProcessor;
use App\Lib\HyipLab;
use App\Models\AdminNotification;
use App\Models\Deposit;
use App\Models\GatewayCurrency;
use App\Models\GeneralSetting;
use App\Models\Plan;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{

    public function deposit()
    {
        $gatewayCurrency = GatewayCurrency::whereHas('method', function ($gate) {
            $gate->where('status', 1);
        })->with('method')->orderby('name')->get();
        $pageTitle = 'Metodos de Deposito';
        return view($this->activeTemplate . 'user.payment.deposit', compact('gatewayCurrency', 'pageTitle'));
    }

    public function depositInsert(Request $request)
    {
        $request->validate([
            'amount'      => 'required|numeric|gt:0',
            'method_code' => 'required',
            'currency'    => 'required',
        ]);

        $gate = GatewayCurrency::whereHas('method', function ($gate) {
            $gate->where('status', 1);
        })->where('method_code', $request->method_code)->where('currency', $request->currency)->first();
        if (!$gate) {
            $notify[] = ['error', 'Gateway invalido'];
            return back()->withNotify($notify);
        }

        if ($gate->min_amount > $request->amount || $gate->max_amount < $request->amount) {
            $notify[] = ['error', 'Respeite o limite de deposito'];
            return back()->withNotify($notify);
        }
        $data = self::insertDeposit($gate, $request->amount);
        session()->put('Track', $data->trx);
        return to_route('user.deposit.confirm');
    }

    public static function insertDeposit($gateway, $amount, $investPlan = null)
    {
        $user      = auth()->user();
        $charge    = $gateway->fixed_charge + ($amount * $gateway->percent_charge / 100);
        $payable   = $amount + $charge;
        $final_amo = $payable * $gateway->rate;

        $data = new Deposit();
        if ($investPlan) {
            $data->plan_id = $investPlan->id;
        }
        $data->user_id         = $user->id;
        $data->method_code     = $gateway->method_code;
        $data->method_currency = strtoupper($gateway->currency);
        $data->amount          = $amount;
        $data->charge          = $charge;
        $data->rate            = $gateway->rate;
        $data->final_amo       = $final_amo;
        $data->btc_amo         = 0;
        $data->btc_wallet      = "";
        $data->trx             = getTrx();
        $data->save();

        return $data;
    }

    public function appDepositConfirm($hash)
    {
        try {
            $id = decrypt($hash);
        } catch (\Exception$ex) {
            return "URL invalida.";
        }
        $data = Deposit::where('id', $id)->where('status', 0)->orderBy('id', 'DESC')->firstOrFail();
        $user = User::findOrFail($data->user_id);
        auth()->login($user);
        session()->put('Track', $data->trx);
        return to_route('user.deposit.confirm');
    }

    public function depositConfirm()
    {
        $track   = session()->get('Track');
        $deposit = Deposit::where('trx', $track)->where('status', 0)->orderBy('id', 'DESC')->with('gateway')->firstOrFail();

        if ($deposit->method_code >= 1000) {
            return to_route('user.deposit.manual.confirm');
        }

        $dirName = $deposit->gateway->alias;
        $new     = __NAMESPACE__ . '\\' . $dirName . '\\ProcessController';

        $data = $new::process($deposit);
        $data = json_decode($data);

        if (isset($data->error)) {
            $notify[] = ['error', $data->message];
            return to_route(gatewayRedirectUrl())->withNotify($notify);
        }
        if (isset($data->redirect)) {
            return redirect($data->redirect_url);
        }

        // for Stripe V3
        if (@$data->session) {
            $deposit->btc_wallet = $data->session->id;
            $deposit->save();
        }

        $pageTitle = 'Confirmacao de Pagamento';
        return view($this->activeTemplate . $data->view, compact('data', 'pageTitle', 'deposit'));
    }

    public static function userDataUpdate($deposit, $isManual = null)
    {
        // Transacao + lock no deposito e no usuario: confirmaoes paralelas
        // (callback do gateway + pagina de confirmacao) creditam apenas UMA vez.
        return DB::transaction(function () use ($deposit, $isManual) {
            $fresh = Deposit::where('id', $deposit->id)->lockForUpdate()->first();
            if (!$fresh || ($fresh->status != 0 && $fresh->status != 2)) {
                return false;
            }

            $fresh->status = 1;
            $fresh->save();

            $user = User::where('id', $fresh->user_id)->lockForUpdate()->first();
            if (!$user) {
                return false;
            }
            $user->deposit_wallet += $fresh->amount;
            $user->save();

            $transaction               = new Transaction();
            $transaction->user_id      = $fresh->user_id;
            $transaction->amount       = $fresh->amount;
            $transaction->post_balance = $user->deposit_wallet;
            $transaction->charge       = $fresh->charge;
            $transaction->trx_type     = '+';
            $transaction->details      = 'Deposito via ' . ($fresh->gateway->name ?? 'Gateway');
            $transaction->trx          = $fresh->trx;
            $transaction->wallet_type  = 'deposit_wallet';
            $transaction->remark       = 'deposit';
            $transaction->save();

            if (!$isManual) {
                $adminNotification            = new AdminNotification();
                $adminNotification->user_id   = $user->id;
                $adminNotification->title     = 'Deposito realizado via ' . ($fresh->gateway->name ?? 'Gateway');
                $adminNotification->click_url = urlPath('admin.deposit.successful');
                $adminNotification->save();
            }

            notify($user, $isManual ? 'DEPOSIT_APPROVE' : 'DEPOSIT_COMPLETE', [
                'method_name'     => ($fresh->gateway->name ?? 'Gateway'),
                'method_currency' => $fresh->method_currency,
                'method_amount'   => showAmount($fresh->final_amo),
                'amount'          => showAmount($fresh->amount),
                'charge'          => showAmount($fresh->charge),
                'rate'            => showAmount($fresh->rate),
                'trx'             => $fresh->trx,
                'post_balance'    => showAmount($user->deposit_wallet),
            ]);

            if ($fresh->plan_id) {
                $plan = Plan::where('status', 1)->find($fresh->plan_id);
                $limiteAtingido = $plan && !empty($plan->max_compras)
                    && Invest::where('user_id', $user->id)->where('plan_id', $plan->id)->count() >= (int) $plan->max_compras;
                if ($plan && !$limiteAtingido) {
                    $hyip = new HyipLab($user, $plan);
                    $hyip->invest($fresh->amount, 'deposit_wallet');
                }
            }

            return true;
        });
    }

    public function manualDepositConfirm()
    {
        $track = session()->get('Track');
        $data  = Deposit::with('gateway')->where('status', 0)->where('trx', $track)->first();
        if (!$data) {
            return to_route(gatewayRedirectUrl());
        }
        if ($data->method_code > 999) {

            $pageTitle = 'Confirmacao de Deposito';
            $method    = $data->gatewayCurrency();
            $gateway   = $method->method;
            return view($this->activeTemplate . 'user.payment.manual', compact('data', 'pageTitle', 'method', 'gateway'));
        }
        abort(404);
    }

    public function manualDepositUpdate(Request $request)
    {
        $track = session()->get('Track');
        $data  = Deposit::with('gateway')->where('status', 0)->where('trx', $track)->first();
        if (!$data) {
            return to_route(gatewayRedirectUrl());
        }
        $gatewayCurrency = $data->gatewayCurrency();
        $gateway         = $gatewayCurrency->method;
        $formData        = $gateway->form?->form_data ?? [];
        $formProcessor  = new FormProcessor();
        $validationRule = $formProcessor->valueValidation($formData);
        $request->validate($validationRule);
        $userData = $formProcessor->processFormData($request, $formData);

        $data->detail = $userData;
        $data->status = 2; // pending
        $data->save();

        $adminNotification            = new AdminNotification();
        $adminNotification->user_id   = $data->user->id;
        $adminNotification->title     = 'Solicitacao de deposito de ' . $data->user->username;
        $adminNotification->click_url = urlPath('admin.deposit.details', $data->id);
        $adminNotification->save();

        notify($data->user, 'DEPOSIT_REQUEST', [
            'method_name'     => ($data->gateway->name ?? 'Gateway'),
            'method_currency' => $data->method_currency,
            'method_amount'   => showAmount($data->final_amo),
            'amount'          => showAmount($data->amount),
            'charge'          => showAmount($data->charge),
            'rate'            => showAmount($data->rate),
            'trx'             => $data->trx,
        ]);

        $notify[] = ['success', 'Sua solicitacao de deposito foi registrada'];
        return to_route('user.deposit.history')->withNotify($notify);
    }

}
