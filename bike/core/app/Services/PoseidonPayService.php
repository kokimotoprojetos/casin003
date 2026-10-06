<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class PoseidonPayService
{
    private string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = rtrim((string) env('POSEIDONPAY_BASE_URL', 'https://app.poseidonpay.site/api/v1/gateway'), '/');
    }

    public function configured(): bool
    {
        return !empty($this->getClientId()) && !empty($this->getSecret());
    }

    private function requestClient()
    {
        $client = Http::withHeaders([
            'X-Public-Key' => $this->getClientId(),
            'X-Secret-Key' => $this->getSecret(),
        ]);

        $proxy = trim((string) env('POSEIDONPAY_PROXY'));

        if ($proxy !== '') {
            $client = $client->withOptions(['proxy' => $proxy]);
        }

        return $client;
    }

    public static function transferFee(): float
    {
        return (float) env('POSEIDONPAY_TRANSFER_FEE', 1.00);
    }

    private function getClientId(): string
    {
        return trim((string) (env('POSEIDONPAY_CLIENT_ID') ?: 'endersonsouza656_pqdwx187pdeetrp3'));
    }

    private function getSecret(): string
    {
        return trim((string) (env('POSEIDONPAY_SECRET') ?: 'joxi8ytllvfkp888htdzby1wn8a0pkhwn8nqsxe8cvz69bbg52ca6r700x5rlxhr'));
    }

    public function createPix(float $amount, string $identifier, array $client): array
    {
        $url = $this->baseUrl . '/pix/receive';
        $payload = [
            'identifier' => $identifier,
            'dueDate' => now()->addDay()->format('Y-m-d H:i:s'),
            'amount' => round($amount, 2),
            'client' => $client,
            'callbackUrl' => rtrim(env('APP_URL', 'https://www.bikeswind.com'), '/') . '/ipn/poseidonpay',
        ];

        try {
            $response = Http::withHeaders([
                'X-Public-Key' => $this->getClientId(),
                'X-Secret-Key' => $this->getSecret(),
            ])->timeout(20)->post($url, $payload);
        } catch (\Throwable $e) {
            try {
                $response = $this->requestClient()->timeout(20)->post($url, $payload);
            } catch (\Throwable $e2) {
                \Log::error('PoseidonPay createPix falhou direto e via proxy', [
                    'identifier' => $identifier,
                    'e1' => $e->getMessage(),
                    'e2' => $e2->getMessage(),
                ]);
                return [
                    'success' => false,
                    'error' => 'Falha de conexão com o gateway de pagamento. Tente novamente em instantes.',
                ];
            }
        }

        $data = json_decode($response->body(), true);

        $errorMsg = $data['message'] ?? ($data['error'] ?? 'A API da PoseidonPay recusou a transação');
        if (empty($errorMsg) || !is_string($errorMsg)) {
            $errorMsg = 'A API da PoseidonPay recusou a transação';
        }

        if (!$response->ok() || !is_array($data) || !in_array($data['status'] ?? '', ['OK', 'PENDING'])) {
            return [
                'success' => false,
                'error' => $errorMsg,
            ];
        }

        $qrUrl = (string) ($data['pix']['image'] ?? $data['pix']['base64'] ?? '');
        $pixCode = (string) ($data['pix']['code'] ?? '');

        if (!empty($qrUrl) && str_starts_with($qrUrl, 'data:image')) {
            // base64 image
        } elseif (!empty($qrUrl) && str_starts_with($qrUrl, 'http')) {
            // URL direta
        } else {
            $qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=' . urlencode($pixCode);
        }

        return [
            'success' => true,
            'gateway_txid' => (string) ($data['transactionId'] ?? ''),
            'webhook_token' => (string) ($data['webhookToken'] ?? ''),
            'pix_code' => $pixCode,
            'qr_url' => $qrUrl,
        ];
    }

    public function consultStatus(string $transactionId): array
    {
        $url = $this->baseUrl . '/transactions';
        $query = ['id' => $transactionId];

        try {
            $response = Http::withHeaders([
                'X-Public-Key' => $this->getClientId(),
                'X-Secret-Key' => $this->getSecret(),
            ])->timeout(15)->get($url, $query);
        } catch (\Throwable $e) {
            try {
                $response = $this->requestClient()->timeout(15)->get($url, $query);
            } catch (\Throwable $e2) {
                return ['paid' => false, 'raw' => null, 'error' => $e2->getMessage()];
            }
        }

            $data = json_decode($response->body(), true);

        if (!is_array($data)) {
            return ['paid' => false, 'raw' => null];
        }

        $status = $data['status'] ?? '';

        $paid = in_array(strtoupper($status), ['PAID', 'PAID_OUT', 'PAID_OK', 'APPROVED', 'CONFIRMED', 'COMPLETED']);

        return ['paid' => $paid, 'raw' => $data];
    }

    public function createTransfer(float $amount, string $pixType, string $pixKey, string $identifier, string $ownerName, string $ownerIp, string $ownerDocumentType, string $ownerDocument, string $callbackUrl): array
    {
        try {
            $response = $this->requestClient()->timeout(10)->post($this->baseUrl . '/transfers', [
                'identifier' => $identifier,
                'amount' => round($amount, 2),
                'discountFeeOfReceiver' => true,
                'pix' => [
                    'type' => $pixType,
                    'key' => $pixKey,
                ],
                'owner' => [
                    'ip' => $ownerIp,
                    'name' => $ownerName,
                    'document' => [
                        'type' => $ownerDocumentType,
                        'number' => $ownerDocument,
                    ],
                ],
                'callbackUrl' => $callbackUrl,
            ]);

            $data = json_decode($response->body(), true);
            $withdraw = is_array($data['withdraw'] ?? null) ? $data['withdraw'] : [];
            $status = strtoupper((string) ($withdraw['status'] ?? ''));

            if (in_array($status, ['CANCELED', 'FAILED'], true)) {
                return [
                    'success' => false,
                    'settled' => true,
                    'error' => $withdraw['rejectedReason'] ?? ($data['message'] ?? 'Transferência recusada pelo gateway'),
                    'withdraw_id' => (string) ($withdraw['id'] ?? ''),
                ];
            }

            if (in_array($status, ['PENDING', 'TRANSFERRING', 'PROCESSING', 'COMPLETED'], true)) {
                return [
                    'success' => true,
                    'settled' => $status === 'COMPLETED',
                    'withdraw_id' => (string) ($withdraw['id'] ?? ''),
                    'status' => $status,
                    'webhook_token' => (string) ($data['webhookToken'] ?? ''),
                    'receipt_url' => (string) ($data['receiptUrl'] ?? ''),
                ];
            }

            if (!$response->ok()) {
                $details = is_array($data['details'] ?? null) ? json_encode($data['details']) : '';
                return [
                    'success' => false,
                    'settled' => true,
                    'error' => trim(($data['message'] ?? 'A API da PoseidonPay recusou a transferência') . ($details !== '' ? ' | ' . $details : '')),
                ];
            }

            return [
                'success' => false,
                'settled' => true,
                'error' => $data['message'] ?? 'A API da PoseidonPay recusou a transferência',
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'settled' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    public function consultTransfer(string $withdrawId): array
    {
        try {
            $response = $this->requestClient()->timeout(10)->get($this->baseUrl . '/transfers', [
                'id' => $withdrawId,
            ]);

            $data = json_decode($response->body(), true);
            $withdraw = is_array($data['withdraw'] ?? null) ? $data['withdraw'] : (is_array($data) ? $data : []);
            $status = strtoupper((string) ($withdraw['status'] ?? ''));

            if (in_array($status, ['COMPLETED', 'PAID', 'APPROVED'], true)) {
                return ['found' => true, 'settled' => true, 'paid' => true, 'status' => $status];
            }

            if (in_array($status, ['CANCELED', 'FAILED'], true)) {
                return ['found' => true, 'settled' => true, 'paid' => false, 'status' => $status];
            }

            if ($status !== '' ) {
                return ['found' => true, 'settled' => false, 'paid' => false, 'status' => $status];
            }

            return ['found' => false, 'settled' => false, 'paid' => false, 'status' => null];
        } catch (\Throwable $e) {
            return ['found' => false, 'settled' => false, 'paid' => false, 'status' => null, 'error' => $e->getMessage()];
        }
    }
}
