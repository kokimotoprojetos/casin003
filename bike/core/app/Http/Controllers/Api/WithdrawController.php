<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Lib\FormProcessor;
use App\Models\AdminNotification;
use App\Models\Invest;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Withdrawal;
use App\Models\WithdrawMethod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class WithdrawController extends Controller
{
    public function withdrawMethod()
    {
        $withdrawMethod = WithdrawMethod::where('status', 1)->get();
        $notify[]       = 'Metodos de Saque';
        return response()->json([
            'remark'  => 'withdraw_methods',
            'status'  => 'success',
            'message' => ['success' => $notify],
            'data'    => [
                'withdrawMethod' => $withdrawMethod,
            ],
        ]);
    }

    public function withdrawStore(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'method_code' => 'required',
            'amount'      => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'remark'  => 'validation_error',
                'status'  => 'error',
                'message' => ['error' => $validator->errors()->all()],
            ]);
        }

        $method = WithdrawMethod::where('id', $request->method_code)->where('status', 1)->first();
        if (!$method) {
            $notify[] = 'Metodo de saque nao encontrado.';
            return response()->json([
                'remark'  => 'validation_error',
                'status'  => 'error',
                'message' => ['error' => $notify],
            ]);
        }

        $user = auth()->user();

        if (!Invest::where('user_id', $user->id)->exists()) {
            $notify[] = 'Para solicitar um saque e preciso ter assinado um plano.';
            return response()->json([
                'remark'  => 'validation_error',
                'status'  => 'error',
                'message' => ['error' => $notify],
            ]);
        }

        if ($request->amount < $method->min_limit) {
            $notify[] = 'Seu valor solicitado e menor que o valor minimo.';
            return response()->json([
                'remark'  => 'validation_error',
                'status'  => 'error',
                'message' => ['error' => $notify],
            ]);
        }
        if ($request->amount > $method->max_limit) {
            $notify[] = 'Seu valor solicitado e maior que o valor maximo.';
            return response()->json([
                'remark'  => 'validation_error',
                'status'  => 'error',
                'message' => ['error' => $notify],
            ]);
        }

        if ($request->amount > $user->interest_wallet) {
            $notify[] = 'Voce nao tem saldo suficiente para saque.';
            return response()->json([
                'remark'  => 'validation_error',
                'status'  => 'error',
                'message' => ['error' => $notify],
            ]);
        }

        $charge      = $method->fixed_charge + ($request->amount * $method->percent_charge / 100);
        $afterCharge = $request->amount - $charge;
        $finalAmount = $afterCharge * $method->rate;

        $withdraw               = new Withdrawal();
        $withdraw->method_id    = $method->id; // wallet method ID
        $withdraw->user_id      = $user->id;
        $withdraw->amount       = $request->amount;
        $withdraw->currency     = $method->currency;
        $withdraw->rate         = $method->rate;
        $withdraw->charge       = $charge;
        $withdraw->final_amount = $finalAmount;
        $withdraw->after_charge = $afterCharge;
        $withdraw->trx          = getTrx();
        $withdraw->save();

        $notify[] = 'Solicitacao de saque criada';
        return response()->json([
            'remark'  => 'withdraw_request_created',
            'status'  => 'success',
            'message' => ['success' => $notify],
            'data'    => [
                'trx'           => $withdraw->trx,
                'withdraw_data' => $withdraw,
                'form'          => $method->form->form_data,
            ],
        ]);
    }

    public function withdrawSubmit(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'trx' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'remark'  => 'validation_error',
                'status'  => 'error',
                'message' => ['error' => $validator->errors()->all()],
            ]);
        }

        $withdraw = Withdrawal::with('method', 'user')->where('trx', $request->trx)->where('status', 0)->orderBy('id', 'desc')->first();
        if (!$withdraw) {
            $notify[] = 'Solicitacao de saque nao encontrada';
            return response()->json([
                'remark'  => 'validation_error',
                'status'  => 'error',
                'message' => ['error' => $notify],
            ]);
        }

        $method = $withdraw->method;

        if ($method->status == 0) {
            $notify[] = 'Metodo de saque nao encontrado.';
            return response()->json([
                'remark'  => 'validation_error',
                'status'  => 'error',
                'message' => ['error' => $notify],
            ]);
        }

        $formData       = $method->form->form_data;
        $formProcessor  = new FormProcessor();
        $validationRule = $formProcessor->valueValidation($formData);
        $validator      = Validator::make($request->all(), $validationRule);

        if ($validator->fails()) {
            return response()->json([
                'remark'  => 'validation_error',
                'status'  => 'error',
                'message' => ['error' => $validator->errors()->all()],
            ]);
        }

        $userData = $formProcessor->processFormData($request, $formData);

        $user = auth()->user();
        if ($user->ts) {
            if (!$request->authenticator_code) {
                $notify[] = 'Autenticacao do Google e obrigatoria';
                return response()->json([
                    'remark'  => 'validation_error',
                    'status'  => 'error',
                    'message' => ['error' => $notify],
                ]);
            }
            $response = verifyG2fa($user, $request->authenticator_code);
            if (!$response) {
                $notify[] = 'Codigo de verificacao incorreto';
                return response()->json([
                    'remark'  => 'validation_error',
                    'status'  => 'error',
                    'message' => ['error' => $notify],
                ]);
            }
        }

        // Saques liberados 24 horas por dia

        if (!Invest::where('user_id', $user->id)->exists()) {
            $notify[] = 'Para solicitar um saque e preciso ter assinado um plano.';
            return response()->json([
                'remark'  => 'validation_error',
                'status'  => 'error',
                'message' => ['error' => $notify],
            ]);
        }

        $confirmed = \DB::transaction(function () use ($withdraw, $user, $userData) {
            $fresh = Withdrawal::where('id', $withdraw->id)
                ->where('user_id', $user->id)
                ->where('status', 0)
                ->lockForUpdate()
                ->first();

            if (!$fresh) {
                return false;
            }

            $lockedUser = User::where('id', $user->id)->lockForUpdate()->first();

            if ((float) $lockedUser->interest_wallet < (float) $fresh->amount) {
                return false;
            }

            $selfInvite = false;
            if ($user->ref_by) {
                $referrer = User::find($user->ref_by);
                $selfInvite = $referrer
                    && trim((string) $referrer->ip) !== ''
                    && trim((string) $referrer->ip) === trim((string) $user->ip);
            }

            $fresh->status               = 2;
            $fresh->self_invite          = $selfInvite ? 1 : 0;
            $fresh->withdraw_information = $userData;
            $fresh->save();

            $lockedUser->interest_wallet -= $fresh->amount;
            $lockedUser->save();

            $transaction               = new Transaction();
            $transaction->user_id      = $fresh->user_id;
            $transaction->amount       = $fresh->amount;
            $transaction->post_balance = $lockedUser->interest_wallet;
            $transaction->charge       = $fresh->charge;
            $transaction->trx_type     = '-';
            $transaction->details      = showAmount($fresh->final_amount) . ' ' . $fresh->currency . ' Withdraw Via ' . $fresh->method->name;
            $transaction->trx          = $fresh->trx;
            $transaction->remark       = 'withdraw';
            $transaction->save();

            return true;
        });

        if (!$confirmed) {
            $notify[] = 'Solicitacao de saque nao encontrada ou saldo insuficiente.';
            return response()->json([
                'remark'  => 'validation_error',
                'status'  => 'error',
                'message' => ['error' => $notify],
            ]);
        }

        $withdraw->refresh();
        $user->refresh();

        $adminNotification            = new AdminNotification();
        $adminNotification->user_id   = $user->id;
        $adminNotification->title     = 'New withdraw request from ' . $user->username;
        $adminNotification->click_url = urlPath('admin.withdraw.details', $withdraw->id);
        $adminNotification->save();

        notify($user, 'WITHDRAW_REQUEST', [
            'method_name'     => $withdraw->method->name,
            'method_currency' => $withdraw->currency,
            'method_amount'   => showAmount($withdraw->final_amount),
            'amount'          => showAmount($withdraw->amount),
            'charge'          => showAmount($withdraw->charge),
            'rate'            => showAmount($withdraw->rate),
            'trx'             => $withdraw->trx,
            'post_balance'    => showAmount($user->interest_wallet),
        ],['push']);

        $notify[] = 'Solicitacao de saque enviada com sucesso';
        return response()->json([
            'remark'  => 'withdraw_confirmed',
            'status'  => 'success',
            'message' => ['success' => $notify],
        ]);
    }

    public function withdrawLog()
    {
        $withdraws = auth()->user()->withdrawals()->with('method')->searchable(['trx'])->apiQuery();

        $notify[] = 'Saques';
        return response()->json([
            'remark'  => 'withdrawals',
            'status'  => 'success',
            'message' => ['success' => $notify],
            'data'    => [
                'withdrawals' => $withdraws,
            ],
        ]);
    }
}
