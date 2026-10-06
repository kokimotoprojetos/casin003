<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\User;
use App\Models\UserLedger;
use App\Models\Withdrawal;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class WithdrawSettler
{
    public static function settlePending(int $limit = 5): int
    {
        if (env('POSEIDONPAY_AUTO_PAYOUT', '0') !== '1') {
            return 0;
        }

        $gateway = new PoseidonPayService();

        if (!$gateway->configured()) {
            return 0;
        }

        $withdraws = Withdrawal::where('status', 2)
            ->whereNotNull('gateway_withdraw_id')
            ->where('created_at', '<', Carbon::now()->subSeconds(20))
            ->orderBy('id', 'asc')
            ->limit($limit)
            ->get();

        $settled = 0;

        foreach ($withdraws as $withdraw) {
            $result = $gateway->consultTransfer((string) $withdraw->gateway_withdraw_id);

            if (empty($result['found']) || empty($result['settled'])) {
                continue;
            }

            self::settleOne($withdraw, !empty($result['paid']));
            $settled++;
        }

        return $settled;
    }

    public static function settleOne(Withdrawal $withdraw, bool $paid): bool
    {
        return DB::transaction(function () use ($withdraw, $paid) {
            $fresh = Withdrawal::where('id', $withdraw->id)->lockForUpdate()->first();

            if (!$fresh || $fresh->status != 2) {
                return false;
            }

            if ($paid) {
                $fresh->status          = 1;
                $fresh->admin_feedback  = 'Pago automaticamente via PoseidonPay | gateway_id: ' . ($fresh->gateway_withdraw_id ?: '-');
                $fresh->update();

                $ledger = new UserLedger();
                $ledger->user_id       = $fresh->user_id;
                $ledger->reason        = 'withdraw_approved';
                $ledger->perticulation = 'Saque pago automaticamente via gateway PIX. Obrigado por usar ' . config('app.name');
                $ledger->amount        = $fresh->amount;
                $ledger->debit         = $fresh->final_amount;
                $ledger->status        = 'approved';
                $ledger->date          = date('d-m-Y H:i');
                $ledger->save();

                return true;
            }

            $user = User::where('id', $fresh->user_id)->lockForUpdate()->first();

            if ($user) {
                $user->interest_wallet += (float) $fresh->amount;
                $user->save();

                $refund               = new Transaction();
                $refund->user_id      = $user->id;
                $refund->amount       = $fresh->amount;
                $refund->post_balance = $user->interest_wallet;
                $refund->charge       = 0;
                $refund->trx_type     = '+';
                $refund->trx          = 'RFS' . $fresh->trx;
                $refund->details      = showAmount($fresh->amount) . ' ' . $fresh->currency . ' Estorno de saque recusado';
                $refund->wallet_type  = 'interest_wallet';
                $refund->remark       = 'withdraw';
                $refund->save();
            }

            $fresh->status          = 3;
            $fresh->admin_feedback  = 'Gateway recusou. Valor estornado ao usuario.';
            $fresh->update();

            $ledger = new UserLedger();
            $ledger->user_id       = $fresh->user_id;
            $ledger->reason        = 'withdraw_rejected';
            $ledger->perticulation = 'Saque recusado pelo gateway. Valor devolvido a carteira.';
            $ledger->amount        = $fresh->amount;
            $ledger->debit         = $fresh->final_amount;
            $ledger->status        = 'rejected';
            $ledger->date          = date('d-m-Y H:i');
            $ledger->save();

            return true;
        });
    }
}
