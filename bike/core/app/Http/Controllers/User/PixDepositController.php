<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Deposit;
use App\Services\PixDepositSweeper;
use App\Services\PoseidonPayService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PixDepositController extends Controller
{
    public function __construct()
    {
        $this->activeTemplate = activeTemplate() ?? 'templates/invester/';
    }

    private function pixClientData(): array
    {
        $user = Auth::user();
        $name = trim(($user->firstname ?? '') . ' ' . ($user->lastname ?? ''));
        if (!$name) {
            $name = $user->name ?: 'Cliente';
        }
        if (!$name) {
            $name = 'Cliente';
        }

        $document = null;
        if (($user->pix_type ?? '') === 'cpf' && $user->pix_key) {
            $document = preg_replace('/\D/', '', $user->pix_key);
        }
        if (!$document) {
            $document = '48416215120';
        }

        return [
            'name'     => $name,
            'document' => $document,
            'email'    => $user->email ?: 'cliente@bikeswind.com',
            'phone'    => $user->mobile ?: '11999999999',
        ];
    }

    public function pixDeposit($amount)
    {
        PixDepositSweeper::sweepForUser(Auth::id());

        $gateway = new PoseidonPayService();

        if (!$gateway->configured()) {
            $notify[] = ['error', 'Serviço temporariamente indisponível (Credenciais)'];
            return back()->withNotify($notify);
        }

        $amount = (float) $amount;

        if (!$amount || $amount < 15) {
            $notify[] = ['error', 'Valor inválido. Permitido a partir de R$ 15,00'];
            return back()->withNotify($notify);
        }

        if ($amount > 5000) {
            $notify[] = ['error', 'Valor inválido. Máximo permitido R$ 5.000,00'];
            return back()->withNotify($notify);
        }

        $user = Auth::user();
        $deposit = Deposit::where('user_id', $user->id)
            ->where('amount', $amount)
            ->where('status', 0)
            ->where('created_at', '>', now()->subMinutes(10))
            ->latest('id')->first();

        if (!$deposit) {
            $deposit = $this->createPixDeposit($amount);
        }

        if (!$deposit) {
            $notify[] = ['error', 'Erro ao criar o pagamento PIX'];
            return back()->withNotify($notify);
        }

        $pageTitle = 'Pagamento PIX';
        return view($this->activeTemplate . 'user.payment.pix', compact('deposit', 'pageTitle'));
    }

    public function pixDepositStatus(Request $request)
    {
        $deposit = Deposit::where('id', $request->input('deposit_id'))
            ->where('user_id', Auth::id())
            ->first();

        if (!$deposit) {
            return response()->json(['error' => 'Depósito não encontrado'], 404);
        }

        if ($deposit->status == 1) {
            return response()->json(['status' => 'approved', 'amount' => (float) $deposit->amount]);
        }

        if ($deposit->status != 0) {
            return response()->json(['status' => $deposit->status]);
        }

        $gateway = new PoseidonPayService();

        if (!$gateway->configured()) {
            return response()->json(['status' => 'pending']);
        }

        $cacheKey = 'pix_consult_' . $deposit->id;
        $cached = cache()->get($cacheKey);

        if (is_array($cached) && isset($cached['paid']) && $cached['paid']) {
            PixDepositSweeper::credit($deposit);
            return response()->json(['status' => 'approved', 'amount' => (float) $deposit->amount]);
        }

        $approved = PixDepositSweeper::checkAndCredit($gateway, $deposit);

        if ($approved) {
            return response()->json(['status' => 'approved', 'amount' => (float) $deposit->amount]);
        }

        return response()->json(['status' => 'pending']);
    }

    public function pixDepositAjax(Request $request)
    {
        try {
            return $this->doPixDepositAjax($request);
        } catch (\Throwable $e) {
            \Log::error('pixDepositAjax error: ' . $e->getMessage(), ['trace' => substr($e->getTraceAsString(), 0, 500)]);
            return response()->json(['success' => false, 'error' => 'Erro interno ao gerar o PIX. Tente novamente em instantes.']);
        }
    }

    private function doPixDepositAjax(Request $request)
    {
        $gateway = new PoseidonPayService();

        if (!$gateway->configured()) {
            return response()->json(['success' => false, 'error' => 'Serviço temporariamente indisponível']);
        }

        $amount = (float) $request->input('amount');

        if (!$amount || $amount < 15) {
            return response()->json(['success' => false, 'error' => 'Valor inválido. Mínimo R$ 15,00']);
        }

        if ($amount > 5000) {
            return response()->json(['success' => false, 'error' => 'Valor inválido. Máximo R$ 5.000,00']);
        }

        $user = Auth::user();
        $deposit = Deposit::where('user_id', $user->id)
            ->where('amount', $amount)
            ->where('status', 0)
            ->where('created_at', '>', now()->subMinutes(10))
            ->latest('id')->first();

        if (!$deposit) {
            $deposit = $this->createPixDeposit($amount);
        }

        if (!$deposit) {
            return response()->json(['success' => false, 'error' => 'Erro ao criar o pagamento PIX. Tente novamente.']);
        }

        return response()->json([
            'success' => true,
            'deposit_id' => $deposit->id,
            'amount' => number_format($amount, 2, ',', '.'),
            'pix_code' => $deposit->pix_code,
            'qr_url' => $deposit->qr_code_url,
        ]);
    }

    private function createPixDeposit($amount): ?Deposit
    {
        $gateway = new PoseidonPayService();

        if (!$gateway->configured()) {
            return null;
        }

        $user = Auth::user();
        $identifier = 'hb_' . time() . '_' . rand(1000, 9999);
        $result = $gateway->createPix($amount, $identifier, $this->pixClientData());

        if (!$result['success']) {
            return null;
        }

        $deposit = new Deposit();
        $deposit->user_id = $user->id;
        $deposit->method_code = 1001;
        $deposit->method_currency = 'BRL';
        $deposit->amount = $amount;
        $deposit->charge = 0;
        $deposit->rate = 1;
        $deposit->final_amo = $amount;
        $deposit->btc_amo = 0;
        $deposit->btc_wallet = '';
        $deposit->trx = getTrx();
        $deposit->gateway_identifier = $identifier;
        $deposit->pix_code = $result['pix_code'];
        $deposit->qr_code_url = $result['qr_url'];
        $deposit->gateway_txid = $result['gateway_txid'];
        $deposit->webhook_token = $result['webhook_token'] ?: null;
        $deposit->status = 0;
        $deposit->save();

        return $deposit;
    }
}
