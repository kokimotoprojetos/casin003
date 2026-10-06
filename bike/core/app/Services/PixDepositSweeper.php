<?php

namespace App\Services;

use App\Models\Deposit;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class PixDepositSweeper
{
    public static function sweepForUser(int $userId, int $limit = 3): int
    {
        $gateway = new PoseidonPayService();

        if (!$gateway->configured()) {
            return 0;
        }

        $deposits = Deposit::where('user_id', $userId)
            ->where('status', 0)
            ->where('method_code', 1001)
            ->whereNotNull('gateway_txid')
            ->where('created_at', '>', now()->subDays(3))
            ->orderBy('id', 'desc')
            ->limit($limit)
            ->get();

        $credited = 0;

        foreach ($deposits as $deposit) {
            if (self::checkAndCredit($gateway, $deposit)) {
                $credited++;
            }
        }

        return $credited;
    }

    public static function checkAndCredit(PoseidonPayService $gateway, Deposit $deposit): bool
    {
        $cacheKey = 'pix_consult_' . $deposit->id;
        $cached = cache()->get($cacheKey);

        if (!is_array($cached) || !isset($cached['paid'])) {
            $globalKey = 'pix_global_consult';
            $globalCount = (int) cache()->get($globalKey, 0);
            if ($globalCount >= 90) {
                return false;
            }
            cache()->put($globalKey, $globalCount + 1, now()->addSeconds(300));

            $result = $gateway->consultStatus($deposit->gateway_txid ?: ($deposit->gateway_identifier ?: $deposit->trx));
            if (!$result['paid']) {
                cache()->put($cacheKey, ['paid' => false], now()->addSeconds(10));
                return false;
            }

            cache()->put($cacheKey, ['paid' => true], now()->addSeconds(600));
        } elseif (!$cached['paid']) {
            return false;
        }

        return self::credit($deposit);
    }

    public static function credit(Deposit $deposit): bool
    {
        return DB::transaction(function () use ($deposit) {
            $locked = Deposit::where('id', $deposit->id)->lockForUpdate()->first();

            if (!$locked || $locked->status != 0) {
                return false;
            }

            $user = User::where('id', $locked->user_id)->lockForUpdate()->first();

            if ($user) {
                $user->interest_wallet += (float) $locked->amount;
                $user->save();

                $transaction = new Transaction();
                $transaction->user_id = $user->id;
                $transaction->amount = (float) $locked->amount;
                $transaction->post_balance = $user->interest_wallet;
                $transaction->charge = 0;
                $transaction->trx_type = '+';
                $transaction->details = 'Deposit Via PIX Gateway';
                $transaction->trx = $locked->trx;
                $transaction->wallet_type = 'interest_wallet';
                $transaction->remark = 'deposit';
                $transaction->save();
            }

            $locked->status = 1;
            $locked->update();

            return true;
        });
    }
}
