<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Deposit;
use App\Models\Transaction;
use App\Models\User;
use App\Models\UserLedger;
use App\Models\Withdrawal;
use App\Services\PixDepositSweeper;
use App\Services\PoseidonPayService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PoseidonPayWebhookController extends Controller
{
    public function handle(Request $request)
    {
        $data = json_decode($request->getContent(), true);

        if (!is_array($data)) {
            return response()->json(['error' => 'JSON invalido'], 400);
        }

        $txData = $data['transaction'] ?? $data;
        $status = $txData['status'] ?? $data['statusTransaction'] ?? $data['status'] ?? '';
        $event = $data['event'] ?? '';
        $identifier = $txData['identifier'] ?? $data['identifier'] ?? '';
        $clientIdentifier = $txData['clientIdentifier'] ?? $data['clientIdentifier'] ?? '';
        $gatewayId = $txData['id'] ?? $data['idTransaction'] ?? $data['id'] ?? '';

        $withdrawPayload = is_array($data['withdraw'] ?? null) ? $data['withdraw'] : (is_array($data['withdrawal'] ?? null) ? $data['withdrawal'] : null);

        // Transferencia: payload aninhado OU objeto de saque no topo do webhook
        if (is_array($withdrawPayload)) {
            return $this->handleTransferWebhook($data, $withdrawPayload, $clientIdentifier);
        }

        $pendingTransferMatch = Withdrawal::where('status', 2)
            ->where(function ($q) use ($gatewayId, $clientIdentifier) {
                $q->where('gateway_withdraw_id', $gatewayId)
                  ->orWhere('trx', $clientIdentifier);
            })
            ->first();

        if ($pendingTransferMatch) {
            $transferPayload = [
                'id'             => (string) ($pendingTransferMatch->gateway_withdraw_id ?: $gatewayId),
                'status'         => $status,
                'rejectedReason' => $data['rejectedReason'] ?? null,
            ];
            return $this->handleTransferWebhook($data, $transferPayload, $clientIdentifier);
        }

        $paidStatuses = ['PAID', 'PAID_OUT', 'approved', 'PAID_OK', 'APPROVED', 'CONFIRMED', 'COMPLETED'];
        if (!in_array(strtoupper((string) $status), $paidStatuses) && $event !== 'TRANSACTION_PAID') {
            return response()->json(['received' => true, 'ignored' => true, 'reason' => 'Status nao e pago']);
        }

        if (!$identifier && !$clientIdentifier && !$gatewayId) {
            return response()->json(['error' => 'IDs de transacao faltando'], 400);
        }

        return DB::transaction(function () use ($identifier, $clientIdentifier, $gatewayId, $data) {
            $deposit = Deposit::where('gateway_identifier', $identifier)
                ->when($clientIdentifier, fn ($q) => $q->orWhere('gateway_identifier', $clientIdentifier))
                ->when($gatewayId, fn ($q) => $q->orWhere('gateway_txid', $gatewayId))
                ->orderBy('id', 'desc')
                ->lockForUpdate()
                ->first();

            if (!$deposit) {
                return response()->json(['error' => 'Transacao nao encontrada'], 404);
            }

            $payloadToken = $data['token'] ?? $data['webhookToken'] ?? '';
            if ($deposit->webhook_token) {
                // Deposito com token registrado: exige correspondencia exata
                if (!$payloadToken || $payloadToken !== $deposit->webhook_token) {
                    return response()->json(['error' => 'Token de webhook invalido'], 401);
                }
            } else {
                // Deposito sem token registrado (criado antes do recurso):
                // so credita apos CONFIRMAR o pagamento direto no gateway
                $gateway = new PoseidonPayService();
                if (!PixDepositSweeper::checkAndCredit($gateway, $deposit)) {
                    return response()->json(['received' => true, 'ignored' => true, 'reason' => 'Pagamento nao confirmado no gateway']);
                }

                return response()->json(['success' => true, 'via' => 'gateway_confirmed']);
            }

            if ($deposit->status == 1) {
                return response()->json(['success' => true, 'message' => 'Ja processado']);
            }

            if ($deposit->status != 0) {
                return response()->json(['error' => 'O deposito nao esta pendente'], 400);
            }

            PixDepositSweeper::credit($deposit);

            return response()->json(['success' => true]);
        });
    }

    private function handleTransferWebhook(array $data, array $withdrawPayload, string $clientIdentifier = '')
    {
        $gwId   = (string) ($withdrawPayload['id'] ?? '');
        $token  = (string) ($data['token'] ?? $data['webhookToken'] ?? '');
        $gwStatus = strtoupper((string) ($withdrawPayload['status'] ?? ''));

        if ((!$gwId && !$clientIdentifier) || !$token) {
            return response()->json(['error' => 'IDs de transferencia faltando'], 400);
        }

        return DB::transaction(function () use ($gwId, $token, $gwStatus, $withdrawPayload, $clientIdentifier) {
            $withdraw = Withdrawal::where(function ($q) use ($gwId, $clientIdentifier) {
                if ($gwId !== '') {
                    $q->orWhere('gateway_withdraw_id', $gwId);
                }
                if ($clientIdentifier !== '') {
                    $q->orWhere('trx', $clientIdentifier);
                }
            })
                ->lockForUpdate()
                ->first();

            if (!$withdraw) {
                return response()->json(['error' => 'Transferencia nao encontrada'], 404);
            }

            if (!$withdraw->gateway_webhook_token) {
                // Saque pendente sem token registrado (falha de comunicacao):
                // webhook nao pode autenticar - segue para confirmacao via polling
                return response()->json(['received' => true, 'ignored' => true, 'reason' => 'Sem token de webhook registrado']);
            }

            if ($token !== $withdraw->gateway_webhook_token) {
                return response()->json(['error' => 'Token de webhook invalido'], 401);
            }

            if ($withdraw->status != 2) {
                return response()->json(['success' => true, 'message' => 'Ja processado']);
            }

            $paidStatuses = ['COMPLETED', 'PAID', 'APPROVED', 'PAID_OUT'];
            $failStatuses = ['CANCELED', 'FAILED'];

            if (in_array($gwStatus, $paidStatuses, true)) {
                $withdraw->admin_feedback = 'Confirmado pelo gateway via webhook | gateway_id: ' . $gwId;
                $withdraw->save();

                $this->approveAutoWithdrawFor($withdraw);

                return response()->json(['success' => true, 'via' => 'webhook']);
            }

            if (in_array($gwStatus, $failStatuses, true)) {
                $reason = (string) ($withdrawPayload['rejectedReason'] ?? 'Transferencia recusada pelo gateway');
                $this->refundAutoWithdrawFor($withdraw, 'Gateway recusou: ' . $reason);
                return response()->json(['success' => true, 'via' => 'webhook_refund']);
            }

            return response()->json(['received' => true, 'ignored' => true, 'reason' => 'Status pendente']);
        });
    }

    private function approveAutoWithdrawFor(Withdrawal $withdraw): void
    {
        $withdraw->status         = 1;
        $withdraw->admin_feedback = 'Pago automaticamente via PoseidonPay (webhook) | gateway_id: ' . ($withdraw->gateway_withdraw_id ?: '-');
        $withdraw->update();

        $ledger = new \App\Models\UserLedger();
        $ledger->user_id       = $withdraw->user_id;
        $ledger->reason        = 'withdraw_approved';
        $ledger->perticulation = 'Saque pago automaticamente via gateway PIX. Obrigado por usar ' . config('app.name');
        $ledger->amount        = $withdraw->amount;
        $ledger->debit         = $withdraw->final_amount;
        $ledger->status        = 'approved';
        $ledger->date          = date('d-m-Y H:i');
        $ledger->save();
    }

    private function refundAutoWithdrawFor(Withdrawal $withdraw, string $feedback): void
    {
        $wallet = 'interest_wallet';

            $lockedUser = User::where('id', $withdraw->user_id)->lockForUpdate()->first();

        if ($lockedUser) {
            $lockedUser->$wallet += (float) $withdraw->amount;
            $lockedUser->save();

            $refund               = new Transaction();
            $refund->user_id      = $lockedUser->id;
            $refund->amount       = $withdraw->amount;
            $refund->post_balance = $lockedUser->$wallet;
            $refund->charge       = 0;
            $refund->trx_type     = '+';
            $refund->trx          = 'RFS' . $withdraw->trx;
            $refund->details      = showAmount($withdraw->amount) . ' ' . $withdraw->currency . ' Estorno de saque recusado';
            $refund->wallet_type  = $wallet;
            $refund->remark       = 'withdraw';
            $refund->save();
        }

        $withdraw->status         = 3;
        $withdraw->admin_feedback = $feedback . '. Valor estornado ao usuario.';
        $withdraw->update();

        $ledger = new UserLedger();
        $ledger->user_id       = $withdraw->user_id;
        $ledger->reason        = 'withdraw_rejected';
        $ledger->perticulation = 'Saque recusado pelo gateway. Valor devolvido a carteira.';
        $ledger->amount        = $withdraw->amount;
        $ledger->debit         = $withdraw->final_amount;
        $ledger->status        = 'rejected';
        $ledger->date          = date('d-m-Y H:i');
        $ledger->save();
    }
}
