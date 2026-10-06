<?php

namespace App\Http\Controllers;

use App\Models\Deposit;
use App\Models\GeneralSetting;
use App\Models\Transaction;
use App\Models\User;
use App\Models\UserLedger;
use App\Models\Withdrawal;
use App\Services\PixDepositSweeper;
use App\Services\PoseidonPayService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class CronController extends Controller
{
    private function authorizeCron(): bool
    {
        $secret = (string) env('CRON_SECRET');

        if (!$secret) {
            return false;
        }

        $bearer = request()->header('Authorization');
        if ($bearer === 'Bearer ' . $secret) {
            return true;
        }

        return (string) request()->query('token') === $secret;
    }

    public function depositSweep()
    {
        if (!$this->authorizeCron()) {
            return response()->json(['error' => 'unauthorized'], 403);
        }

        $gateway = new PoseidonPayService();

        if (!$gateway->configured()) {
            return response()->json(['ok' => true, 'credited' => 0, 'skipped' => 'gateway_not_configured']);
        }

        $deposits = Deposit::where('status', 0)
            ->where('method_code', 1001)
            ->whereNotNull('gateway_txid')
            ->where('created_at', '>', now()->subDays(3))
            ->orderBy('id', 'asc')
            ->limit(4)
            ->get();

        $credited = 0;

        foreach ($deposits as $deposit) {
            if (PixDepositSweeper::checkAndCredit($gateway, $deposit)) {
                $credited++;
            }
        }

        $transfersSettled = $this->settlePendingTransfers();

        return response()->json([
            'ok'                => true,
            'credited'          => $credited,
            'scanned'           => $deposits->count(),
            'transfersSettled'  => $transfersSettled,
        ]);
    }

    private function settlePendingTransfers(): int
    {
        return \App\Services\WithdrawSettler::settlePending(5);
    }

    public function cron()
    {
        if (!$this->authorizeCron()) {
            return response()->json(['error' => 'unauthorized'], 403);
        }

        $now                = Carbon::now();
        $general            = GeneralSetting::first();
        $general->last_cron = $now;
        $general->save();

        $day    = strtolower(date('D'));
        $offDay = (array) $general->off_day;
        if (array_key_exists($day, $offDay)) {
            echo "Holiday";
            exit;
        }

        echo "OK";
    }
}
