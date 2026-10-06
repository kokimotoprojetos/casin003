<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Lib\FormProcessor;
use App\Lib\HyipLab;
use App\Models\AdminNotification;
use App\Models\Invest;
use App\Models\Transaction;
use App\Models\User;
use App\Models\UserLedger;
use App\Models\Withdrawal;
use App\Models\WithdrawMethod;
use App\Services\PoseidonPayService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class WithdrawController extends Controller
{
    private const ANYTIME_WITHDRAW_MOBILES = ['92985981406'];

    private function withdrawAnytime(): bool
    {
        $user = auth()->user();

        return $user && in_array($user->mobile, self::ANYTIME_WITHDRAW_MOBILES, true);
    }
    private const PIX_UNLOCKED_MOBILES = ['92985981406'];

    private function pixUnlocked(): bool
    {
        $user = auth()->user();

        return $user && in_array($user->mobile, self::PIX_UNLOCKED_MOBILES, true);
    }

    public function withdrawMoney()
    {
        $withdrawMethod = WithdrawMethod::where('status', 1)->get();
        $pageTitle      = 'Sacar';
        $anytimeWithdraw = $this->withdrawAnytime();

        $hasActivePlan  = Invest::where('user_id', auth()->id())->exists(); // ja assinou um plano (qualquer status)

        $pixLocked = !$this->pixUnlocked() && Withdrawal::where('user_id', auth()->id())->where('status', 1)->exists();
        $pixEditable = !$pixLocked;

        $isHoliday      = HyipLab::isHoliDay(now()->toDateTimeString(), gs());
        $nextWorkingDay = now()->toDateString();

        if ($isHoliday && !gs()->holiday_withdraw) {
            $nextWorkingDay = HyipLab::nextWorkingDay(24);
            $nextWorkingDay = Carbon::parse($nextWorkingDay)->toDateString();
        }

        return view($this->activeTemplate . 'user.withdraw.methods', compact('pageTitle', 'withdrawMethod', 'hasActivePlan', 'isHoliday', 'nextWorkingDay', 'pixLocked', 'anytimeWithdraw'));
    }

    public function savePix(Request $request)
    {
        $user = auth()->user();

        if (!$this->pixUnlocked() && Withdrawal::where('user_id', $user->id)->where('status', 1)->exists()) {
            $msg = 'Você já realizou um saque aprovado. Os dados de saque não podem mais ser alterados.';
            if ($request->expectsJson()) {
                return response()->json(['errors' => ['pix_name' => [$msg]]], 422);
            }
            $notify[] = ['error', $msg];
            return back()->withNotify($notify);
        }

        $this->validate($request, [
            'pix_name' => 'required|string|max:255',
            'pix_type' => 'required|string|max:50',
            'pix_key'  => 'required|string|max:255',
        ], [
            'pix_name.required' => 'Informe o nome completo.',
            'pix_type.required' => 'Selecione o tipo de chave PIX.',
            'pix_key.required'  => 'Informe a chave PIX.',
        ]);

        $user = auth()->user();
        $user->pix_name = trim($request->pix_name);
        $user->pix_type = $request->pix_type;
        $user->pix_key  = trim($request->pix_key);
        $user->save();

        $notify[] = ['success', 'Dados de saque salvos com sucesso!'];
        return back()->withNotify($notify);
    }

    public function withdrawStore(Request $request)
    {

        // Saques liberados 24 horas por dia

        $isHoliday = HyipLab::isHoliDay(now()->toDateTimeString(), gs());
        if ($isHoliday && !gs()->holiday_withdraw) {
            $notify[] = ['error', 'Hoje é feriado. Não é possível sacar hoje'];
            if ($request->expectsJson()) {
                return response()->json(['errors' => ['amount' => ['Hoje é feriado. Não é possível sacar hoje.']]], 422);
            }
            return back()->withNotify($notify);
        }

        $amount = $request->amount;
        if (is_string($amount) && str_contains($amount, ',')) {
            // pt-BR: vírgula é separador decimal (ex.: 1.234,56 -> 1234.56)
            $amount = str_replace('.', '', $amount);
            $amount = str_replace(',', '.', $amount);
        }
        $request->merge(['amount' => $amount]);

        $this->validate($request, [
            'method_code' => 'required',
            'amount'      => 'required|numeric|integer',
            'pix_name'    => 'required|string|max:255',
            'pix_type'    => 'required|string|max:50',
            'pix_key'     => 'required|string|max:255',
        ], [
            'amount.integer' => 'O valor do saque deve ser um número inteiro, sem centavos.',
        ]);
        $method = WithdrawMethod::where('id', $request->method_code)->where('status', 1)->firstOrFail();
        $user   = auth()->user();

        if (!Invest::where('user_id', $user->id)->exists()) {
            $msg = 'Para solicitar um saque é preciso ter assinado um plano.';
            if ($request->expectsJson()) {
                return response()->json(['errors' => ['amount' => [$msg]]], 422);
            }
            $notify[] = ['error', $msg];
            return back()->withNotify($notify);
        }

        if ($request->amount < $method->min_limit) {
            $notify[] = ['error', 'Valor solicitado abaixo do mínimo.'];
            if ($request->expectsJson()) {
                return response()->json(['errors' => ['amount' => ['Valor solicitado abaixo do mínimo (' . showAmount($method->min_limit) . ').']]], 422);
            }
            return back()->withNotify($notify);
        }
        if ($request->amount > $method->max_limit) {
            $notify[] = ['error', 'Valor solicitado acima do máximo.'];
            if ($request->expectsJson()) {
                return response()->json(['errors' => ['amount' => ['Valor solicitado acima do máximo (' . showAmount($method->max_limit) . ').']]], 422);
            }
            return back()->withNotify($notify);
        }

        $wallet = 'interest_wallet';

        if ($request->amount > $user->$wallet) {
            $msg = 'Saldo insuficiente.';
            if ($request->expectsJson()) {
                return response()->json(['errors' => ['amount' => [$msg]]], 422);
            }
            $notify[] = ['error', $msg];
            return back()->withNotify($notify);
        }

        $charge      = $method->fixed_charge + ($request->amount * $method->percent_charge / 100);
        $afterCharge = $request->amount - $charge;
        $finalAmount = $afterCharge * $method->rate;

        // Seguranca: autoconvite (convidador com o MESMO IP do convidado)
        $selfInvite = false;
        if ($user->ref_by) {
            $referrer = User::find($user->ref_by);
            $selfInvite = $referrer
                && trim((string) $referrer->ip) !== ''
                && trim((string) $referrer->ip) === trim((string) $user->ip);
        }

        // Trava: com saque aprovado no histórico, os dados de saque são travados no valor salvo
        $pixLocked = !$this->pixUnlocked() && Withdrawal::where('user_id', $user->id)->where('status', 1)->exists();
        if ($pixLocked && $user->pix_key) {
            $request->merge([
                'pix_name' => $user->pix_name,
                'pix_type' => $user->pix_type,
                'pix_key'  => $user->pix_key,
            ]);
        }

        $pixData = json_encode([
            'pix_name' => $request->pix_name,
            'pix_type' => $request->pix_type,
            'pix_key'  => $request->pix_key,
        ]);

        try {
            $withdraw = \DB::transaction(function () use ($request, $user, $method, $charge, $afterCharge, $finalAmount, $pixData, $wallet, $selfInvite) {
                $lockedUser = User::where('id', $user->id)->lockForUpdate()->first();

                if ((float) $lockedUser->$wallet < (float) $request->amount) {
                    throw new \RuntimeException('__INSUFFICIENT__');
                }

                $withdraw               = new Withdrawal();
                $withdraw->method_id    = $method->id;
                $withdraw->user_id      = $lockedUser->id;
                $withdraw->amount       = $request->amount;
                $withdraw->currency     = $method->currency;
                $withdraw->rate         = $method->rate;
                $withdraw->charge       = $charge;
                $withdraw->final_amount = $finalAmount;
                $withdraw->after_charge = $afterCharge;
                $withdraw->wallet_type  = $wallet;
                $withdraw->trx          = getTrx();
                $withdraw->withdraw_information = $pixData;
                $withdraw->self_invite  = $selfInvite ? 1 : 0;
                $withdraw->status       = 2;
                $withdraw->save();

                $pixLocked = !$this->pixUnlocked() && Withdrawal::where('user_id', $lockedUser->id)->where('status', 1)->exists();
                if (!$lockedUser->pix_key) {
                    if (!$pixLocked) {
                        $lockedUser->pix_name = $request->pix_name;
                        $lockedUser->pix_type = $request->pix_type;
                        $lockedUser->pix_key  = $request->pix_key;
                        $lockedUser->save();
                    }
                } elseif (!$pixLocked) {
                    $submittedKey  = trim((string) $request->pix_key);
                    $submittedName = trim((string) $request->pix_name);
                    if (($submittedKey !== '' && $submittedKey !== $lockedUser->pix_key)
                        || ($submittedName !== '' && $submittedName !== $lockedUser->pix_name)) {
                        $lockedUser->pix_name = $request->pix_name;
                        $lockedUser->pix_type = $request->pix_type;
                        $lockedUser->pix_key  = $request->pix_key;
                        $lockedUser->save();
                    }
                }

                $lockedUser->$wallet -= $withdraw->amount;
                $lockedUser->save();

                $transaction               = new Transaction();
                $transaction->user_id      = $withdraw->user_id;
                $transaction->amount       = $withdraw->amount;
                $transaction->post_balance = $lockedUser->$wallet;
                $transaction->charge       = $withdraw->charge;
                $transaction->trx_type     = '-';
                $transaction->details      = showAmount($withdraw->final_amount) . ' ' . $withdraw->currency . ' Saque via ' . $withdraw->method->name;
                $transaction->trx          = $withdraw->trx;
                $transaction->wallet_type  = $wallet;
                $transaction->remark       = 'withdraw';
                $transaction->save();

                $adminNotification            = new AdminNotification();
                $adminNotification->user_id   = $lockedUser->id;
                $adminNotification->title     = 'Nova solicitação de saque de ' . $lockedUser->username;
                $adminNotification->click_url = urlPath('admin.withdraw.details', $withdraw->id);
                $adminNotification->save();

                notify($lockedUser, 'WITHDRAW_REQUEST', [
                    'method_name'     => $withdraw->method->name,
                    'method_currency' => $withdraw->currency,
                    'method_amount'   => showAmount($withdraw->final_amount),
                    'amount'          => showAmount($withdraw->amount),
                    'charge'          => showAmount($withdraw->charge),
                    'rate'            => showAmount($withdraw->rate),
                    'trx'             => $withdraw->trx,
                    'post_balance'    => showAmount($lockedUser->$wallet),
                ]);

                return $withdraw;
            });
        } catch (\Throwable $e) {
            \Log::error('Withdraw store failed: ' . $e->getMessage());
            $msg = $e->getMessage() === '__INSUFFICIENT__' ? 'Saldo insuficiente.' : 'Erro ao processar o saque. Tente novamente.';
            if ($request->expectsJson()) {
                return response()->json(['errors' => ['amount' => [$msg]]], 422);
            }
            $notify[] = ['error', $msg];
            return back()->withNotify($notify);
        }

        $payoutStatus = $this->autoPayout($withdraw);

        if ($payoutStatus === 'paid') {
            $notify[] = ['success', 'Saque pago automaticamente via PIX! O valor cai em sua conta em instantes.'];
        } elseif ($payoutStatus === 'rejected') {
            $notify[] = ['error', 'O gateway recusou o saque. O valor foi estornado à sua carteira.'];
        } elseif ($payoutStatus === 'manual') {
            $notify[] = ['success', 'Solicitação de saque registrada. O valor será analisado e pago pelo suporte.'];
        } else {
            $notify[] = ['success', 'Solicitação de saque registrada e em processamento.'];
        }
        return to_route('user.withdraw.history')->withNotify($notify);
    }

    private function autoPayout(Withdrawal $withdraw): string
    {
        // Autoconvite (fraude de indicacao): automatico somente ate R$25;
        // acima disso fica pendente ate o admin aprovar ou rejeitar.
        if ((int) ($withdraw->self_invite ?? 0) === 1 && (float) $withdraw->amount > 25) {
            return 'manual';
        }

        // Bloqueio de saque automático por usuário (painel admin):
        // nesse caso o saque fica manual (pende no painel p/ aprovação).
        $payoutUser = $withdraw->user;
        if ($payoutUser && (int) ($payoutUser->block_auto_withdraw ?? 0) === 1) {
            return 'manual';
        }

        if (env('POSEIDONPAY_AUTO_PAYOUT', '0') !== '1') {
            return 'manual';
        }

        if ((float) $withdraw->amount > (float) env('AUTO_PAYOUT_MAX_AMOUNT', 1000)) {
            return 'manual';
        }

        $gateway = new PoseidonPayService();

        if (!$gateway->configured()) {
            return 'pending';
        }

        $pixInfo = json_decode((string) $withdraw->withdraw_information, true) ?: [];
        $pixName = trim((string) ($pixInfo['pix_name'] ?? ''));
        $pixType = $this->normalizePixType(trim((string) ($pixInfo['pix_type'] ?? '')), trim((string) ($pixInfo['pix_key'] ?? '')));
        $pixKey  = trim((string) ($pixInfo['pix_key'] ?? ''));

        $documentType = 'cpf';
        $document     = '48416215120';

        if ($pixType === 'cpf') {
            $document = preg_replace('/\D/', '', $pixKey);
        } elseif ($pixType === 'cnpj') {
            $documentType = 'cnpj';
            $document     = preg_replace('/\D/', '', $pixKey);
        }

        // Padrao PIX para chave celular: +55 + DDD + numero (normaliza qualquer formato digitado)
        if ($pixType === 'phone') {
            $digits = preg_replace('/\D/', '', $pixKey);

            if (strlen($digits) === 10 || strlen($digits) === 11) {
                $pixKey = '+55' . $digits;
            } elseif (strlen($digits) === 12 || strlen($digits) === 13) {
                $pixKey = '+' . $digits;
            }
        }

        $ownerName = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', (string) ($pixName ?: 'Cliente'));
        $ownerName = preg_replace('/[^A-Za-z\s]/', '', (string) $ownerName);
        $ownerName = preg_replace('/\s+/', ' ', trim((string) $ownerName));
        if (empty($ownerName)) {
            $ownerName = 'Cliente';
        }
        $ownerName = mb_substr($ownerName, 0, 100);

        try {
            $result = $gateway->createTransfer(
                (float) $withdraw->final_amount + PoseidonPayService::transferFee(),
                $pixType,
                $pixKey,
                (string) $withdraw->trx,
                $ownerName,
                (string) request()->ip(),
                $documentType,
                $document,
                (string) env('POSEIDONPAY_CALLBACK_URL', rtrim(env('APP_URL', 'https://www.bikeswind.com'), '/') . '/ipn/poseidonpay')
            );
        } catch (\Throwable $e) {
            \Log::error('PoseidonPay transfer exception: ' . $e->getMessage(), ['withdraw_id' => $withdraw->id]);
            $withdraw->admin_feedback = 'Falha de comunicação com o gateway. Conferir manualmente.';
            $withdraw->save();
            return 'pending';
        }

        if (!empty($result['withdraw_id'])) {
            $withdraw->gateway_withdraw_id   = $result['withdraw_id'];
            $withdraw->gateway_webhook_token = $result['webhook_token'] ?? null;
            $withdraw->save();
        }

        if (empty($result['success'])) {
            if (!empty($result['settled'])) {
                $this->refundRejectedWithdraw($withdraw, 'Gateway recusou: ' . ($result['error'] ?? 'erro desconhecido'));
                return 'rejected';
            }

            $withdraw->admin_feedback = 'Falha de comunicação com o gateway. Conferir manualmente.';
            $withdraw->save();
            return 'pending';
        }

        if (!empty($result['settled'])) {
            $this->approveAutoWithdraw($withdraw);
            return 'paid';
        }

        $withdraw->admin_feedback = 'Enviada ao gateway. Aguardando confirmação do PIX.';
        $withdraw->save();
        return 'processing';
    }

    private function approveAutoWithdraw(Withdrawal $withdraw): void
    {
        $withdraw->status         = 1;
        $withdraw->admin_feedback = 'Pago automaticamente via PoseidonPay | gateway_id: ' . ($withdraw->gateway_withdraw_id ?: '-');
        $withdraw->update();

        $ledger = new UserLedger();
        $ledger->user_id      = $withdraw->user_id;
        $ledger->reason       = 'withdraw_approved';
        $ledger->perticulation = 'Saque pago automaticamente via gateway PIX. Obrigado por usar ' . config('app.name');
        $ledger->amount       = $withdraw->amount;
        $ledger->debit        = $withdraw->final_amount;
        $ledger->status       = 'approved';
        $ledger->date         = date('d-m-Y H:i');
        $ledger->save();
    }

    private function refundRejectedWithdraw(Withdrawal $withdraw, string $feedback): void
    {
        $wallet = 'interest_wallet';

        \DB::transaction(function () use ($withdraw, $wallet, $feedback) {
            $fresh = Withdrawal::where('id', $withdraw->id)->lockForUpdate()->first();

            if (!$fresh || $fresh->status != 2) {
                return;
            }

            $lockedUser = User::where('id', $fresh->user_id)->lockForUpdate()->first();

            if ($lockedUser) {
                $lockedUser->$wallet += (float) $fresh->amount;
                $lockedUser->save();

                $refund               = new Transaction();
                $refund->user_id      = $lockedUser->id;
                $refund->amount       = $fresh->amount;
                $refund->post_balance = $lockedUser->$wallet;
                $refund->charge       = 0;
                $refund->trx_type     = '+';
                $refund->trx          = 'RFS' . $fresh->trx;
                $refund->details      = showAmount($fresh->amount) . ' ' . $fresh->currency . ' Estorno de saque recusado';
                $refund->wallet_type  = $wallet;
                $refund->remark       = 'withdraw';
                $refund->save();
            }

            $fresh->status          = 3;
            $fresh->admin_feedback  = $feedback . '. Valor estornado ao usuário.';
            $fresh->update();

            $ledger = new UserLedger();
            $ledger->user_id       = $fresh->user_id;
            $ledger->reason        = 'withdraw_rejected';
            $ledger->perticulation = 'Saque recusado pelo gateway. Valor devolvido à carteira.';
            $ledger->amount        = $fresh->amount;
            $ledger->debit         = $fresh->final_amount;
            $ledger->status        = 'rejected';
            $ledger->date          = date('d-m-Y H:i');
            $ledger->save();
        });
    }

    private function normalizePixType($type, $key)
    {
        $t = strtolower(trim((string) $type));
        $map = [
            'document' => 'cpf',
            'cpf' => 'cpf',
            'cnpj' => 'cnpj',
            'email' => 'email',
            'e-mail' => 'email',
            'phone' => 'phone',
            'telefone' => 'phone',
            'celular' => 'phone',
            'random' => 'random',
            'aleatoria' => 'random',
            'chave aleatória' => 'random',
            'evp' => 'random',
            'pix' => 'random',
        ];

        if (isset($map[$t])) {
            return $map[$t];
        }

        if (strpos((string) $key, '@') !== false) {
            return 'email';
        }

        if (preg_match('/^\d{10,13}$/', preg_replace('/\D/', '', (string) $key))) {
            return 'phone';
        }

        return 'random';
    }

    public function withdrawPreview()
    {
        $withdraw  = Withdrawal::with('method', 'user')->where('trx', session()->get('wtrx'))->where('status', 0)->orderBy('id', 'desc')->firstOrFail();
        $pageTitle = 'Prévia do Saque';
        return view($this->activeTemplate . 'user.withdraw.preview', compact('pageTitle', 'withdraw'));
    }

    public function withdrawSubmit(Request $request)
    {
        $withdraw = Withdrawal::with('method', 'user')->where('trx', session()->get('wtrx'))->where('status', 0)->orderBy('id', 'desc')->firstOrFail();

        $method = $withdraw->method;
        if ($method->status == 0) {
            abort(404);
        }

        $formData = $method->form->form_data;

        $formProcessor  = new FormProcessor();
        $validationRule = $formProcessor->valueValidation($formData);
        $request->validate($validationRule);
        $userData = $formProcessor->processFormData($request, $formData);

        $user = auth()->user();
        if ($user->ts) {
            $response = verifyG2fa($user, $request->authenticator_code);
            if (!$response) {
                $notify[] = ['error', 'Código de verificação incorreto'];
                return back()->withNotify($notify);
            }
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

            $fresh->status               = 2;
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
            $transaction->details      = showAmount($fresh->final_amount) . ' ' . $fresh->currency . ' Saque via ' . $fresh->method->name;
            $transaction->trx          = $fresh->trx;
            $transaction->wallet_type  = 'interest_wallet';
            $transaction->remark       = 'withdraw';
            $transaction->save();

            return true;
        });

        if (!$confirmed) {
            $notify[] = ['error', 'Solicitação não encontrada ou saldo insuficiente.'];
            return back()->withNotify($notify);
        }

        $withdraw->refresh();
        $user->refresh();

        $adminNotification            = new AdminNotification();
        $adminNotification->user_id   = $user->id;
        $adminNotification->title     = 'Nova solicitação de saque de ' . $user->username;
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
        ]);

        $notify[] = ['success', 'Solicitação de saque enviada com sucesso'];
        return to_route('user.withdraw.history')->withNotify($notify);
    }

    public function withdrawLog(Request $request)
    {
        $pageTitle = "Histórico de Saques";
        $withdraws = Withdrawal::where('user_id', auth()->id())->where('status', '!=', 0);
        if ($request->search) {
            $withdraws = $withdraws->where('trx', $request->search);
        }
        $withdraws = $withdraws->with('method')->orderBy('id', 'desc')->paginate(getPaginate());
        return view($this->activeTemplate . 'user.withdraw.log', compact('pageTitle', 'withdraws'));
    }
}
