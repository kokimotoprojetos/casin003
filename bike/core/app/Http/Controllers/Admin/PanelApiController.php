<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Deposit;
use App\Models\Plan;
use App\Models\Transaction;
use App\Models\User;
use App\Models\UserLedger;
use App\Models\Withdrawal;
use App\Services\PoseidonPayService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class PanelApiController extends Controller
{
    // ─── Token helpers (stateless – works on Vercel serverless) ───────────────

    private function tokenSecret(): string
    {
        return config('app.key') . '_admin_panel_v1';
    }

    private function makeToken(int $adminId): string
    {
        $payload = $adminId . '|' . (time() + 86400 * 7); // valid 7 days
        $sig = hash_hmac('sha256', $payload, $this->tokenSecret());
        return base64_encode($payload . '|' . $sig);
    }

    private function verifyToken(?string $raw): ?int
    {
        if (!$raw) return null;
        $decoded = base64_decode($raw, true);
        if (!$decoded) return null;
        $parts = explode('|', $decoded, 3);
        if (count($parts) !== 3) return null;
        [$adminId, $expires, $sig] = $parts;
        if ((int) $expires < time()) return null;
        $expected = hash_hmac('sha256', $adminId . '|' . $expires, $this->tokenSecret());
        if (!hash_equals($expected, $sig)) return null;
        return (int) $adminId;
    }

    private function getTokenFromRequest(Request $request): ?string
    {
        // Try Authorization header first, then cookie
        $header = $request->header('Authorization', '');
        if (str_starts_with($header, 'Bearer ')) {
            return substr($header, 7);
        }
        return $request->cookie('admin_panel_token');
    }

    private function isAdminAuthorized(Request $request): bool
    {
        // Accept HMAC token (stateless - works on serverless)
        $token = $this->getTokenFromRequest($request);
        if ($this->verifyToken($token) !== null) {
            return true;
        }
        // Also accept Laravel session-based admin auth
        return Auth::guard('admin')->check();
    }

    private function tokenResponse(int $adminId): \Illuminate\Http\JsonResponse
    {
        $token = $this->makeToken($adminId);
        return response()
            ->json(['success' => true, 'token' => $token])
            ->cookie('admin_panel_token', $token, 60 * 24 * 7, '/', null, true, true, false, 'Strict');
    }

    // ─── Auth endpoints ───────────────────────────────────────────────────────

    public function login(Request $request)
    {
        $loginInput = trim($request->input('email', ''));
        $password   = $request->input('password', '');

        if ($loginInput === '' || $password === '') {
            return response()->json(['error' => 'Credenciais inválidas'], 401);
        }

        // Find admin by email or username
        $admin = \App\Models\Admin::where('email', $loginInput)
            ->orWhere('username', $loginInput)
            ->first();

        if (!$admin || !\Illuminate\Support\Facades\Hash::check($password, $admin->password)) {
            return response()->json(['error' => 'Credenciais inválidas'], 401);
        }

        \Auth::guard('admin')->login($admin);

        return $this->tokenResponse($admin->id);
    }

    public function logout()
    {
        return response()
            ->json(['success' => true])
            ->withoutCookie('admin_panel_token');
    }

    public function data(Request $request)
    {
        if (!$this->isAdminAuthorized($request)) {
            return response()->json(['error' => 'Não autorizado'], 401);
        }

        $date = $request->input('date');
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $date)) {
            $date = null;
        }

        try {
            return response()->json($this->adminPayload(false, $date));
        } catch (\Throwable $e) {
            \Log::error('Admin panel data failed: ' . $e->getMessage());
            return response()->json(['error' => 'Erro ao carregar dados'], 500);
        }
    }

    public function dataPasswords(Request $request)
    {
        if (!$this->isAdminAuthorized($request)) {
            return response()->json(['error' => 'Não autorizado'], 401);
        }

        try {
            return response()->json($this->adminPayload(true));
        } catch (\Throwable $e) {
            \Log::error('Admin passwords panel data failed: ' . $e->getMessage());
            return response()->json(['error' => 'Erro ao carregar dados'], 500);
        }
    }

    public function alerts(Request $request)
    {
        if (!$this->isAdminAuthorized($request)) {
            return response()->json(['error' => 'Não autorizado'], 401);
        }

        // IPs compartilhados: mais de uma conta no mesmo IP
        $groupedIps = User::selectRaw('ip, COUNT(*) as total')
            ->whereNotNull('ip')
            ->where('ip', '!=', '')
            ->groupBy('ip')
            ->havingRaw('COUNT(*) > 1')
            ->orderByDesc('total')
            ->get();

        $groups = $groupedIps->map(function ($g) {
            $users = User::where('ip', $g->ip)
                ->orderByDesc('id')
                ->get(['id', 'mobile', 'username', 'firstname', 'lastname', 'email', 'ref_by', 'status', 'ban_reason', 'ip', 'created_at', 'interest_wallet'])
                ->map(function ($u) {
                    return [
                        'id' => $u->id,
                        'phone' => $u->mobile ?? $u->username,
                        'name' => trim(($u->firstname ?? '') . ' ' . ($u->lastname ?? '')) ?: $u->email,
                        'balance' => (float) ($u->interest_wallet ?? 0),
                        'ref_by' => (int) ($u->ref_by ?? 0),
                        'status' => (int) $u->status,
                        'ban_reason' => $u->ban_reason,
                        'created_at' => $u->created_at,
                    ];
                });

            $ids = $users->pluck('id')->all();

            $users = $users->map(function ($u) use ($users, $ids) {
                $u['self_invite'] = in_array($u['ref_by'], $ids);
                $u['invited_by'] = null;
                if ($u['self_invite']) {
                    $inv = $users->firstWhere('id', $u['ref_by']);
                    $u['invited_by'] = '#' . ($inv['id'] ?? '') . ' ' . ($inv['phone'] ?? '');
                }
                return $u;
            });

            return [
                'ip' => $g->ip,
                'total' => $g->total,
                'has_self_invite' => $users->contains('self_invite', true),
                'users' => $users,
            ];
        });

        return response()->json(['success' => true, 'groups' => $groups]);
    }

    public function banUser(Request $request)
    {
        if (!$this->isAdminAuthorized($request)) {
            return response()->json(['error' => 'Não autorizado'], 401);
        }

        $userId = (int) $request->input('userId');
        $ban = $request->boolean('ban');

        $user = User::find($userId);

        if (!$user) {
            return response()->json(['error' => 'Usuário não encontrado'], 404);
        }

        $user->status = $ban ? 0 : 1;
        $user->ban_reason = $ban ? 'Sistema identificou uma tentativa de fraude em suas contas.' : null;
        $user->save();

        return response()->json(['success' => true, 'banned' => $ban]);
    }

    public function changePassword(Request $request)
    {
        if (!$this->isAdminAuthorized($request)) {
            return response()->json(['error' => 'Não autorizado'], 401);
        }

        $userId = (int) $request->input('userId');
        $password = (string) $request->input('password');

        if (mb_strlen($password) < 4 || mb_strlen($password) > 100) {
            return response()->json(['error' => 'Senha deve ter entre 4 e 100 caracteres'], 422);
        }

        $user = User::find($userId);

        if (!$user) {
            return response()->json(['error' => 'Usuário não encontrado'], 404);
        }

        $user->password = \Illuminate\Support\Facades\Hash::make($password);
        $user->password_plain = \Illuminate\Support\Facades\Crypt::encryptString($password);
        $user->save();

        return response()->json(['success' => true, 'message' => 'Senha alterada com sucesso.']);
    }

    public function resetSaque(Request $request)
    {
        if (!$this->isAdminAuthorized($request)) {
            return response()->json(['error' => 'Não autorizado'], 401);
        }

        $userId = (int) $request->input('userId');

        $user = User::find($userId);

        if (!$user) {
            return response()->json(['error' => 'Usuário não encontrado'], 404);
        }

        Withdrawal::where('user_id', $user->id)
            ->where('status', 1)
            ->update(['status' => 0]);

        $user->pix_name = null;
        $user->pix_type = null;
        $user->pix_key  = null;
        $user->save();

        return response()->json(['success' => true, 'message' => 'Dados de saque resetados. Usuário pode cadastrar novamente.']);
    }

    private function adminPayload(bool $withPasswords, ?string $date = null)
    {
        $users = User::orderByDesc('id')->get(['id', 'mobile', 'username', 'firstname', 'lastname', 'deposit_wallet', 'interest_wallet', 'created_at', 'email', 'status', 'ref_by', 'is_leader', 'ip', 'password_plain', 'block_auto_withdraw'])->map(function ($user) use ($withPasswords) {
            $plain = null;
            if ($withPasswords && $user->password_plain) {
                try {
                    $plain = \Illuminate\Support\Facades\Crypt::decryptString($user->password_plain);
                } catch (\Throwable $e) {
                    $plain = null;
                }
            }
            return [
                'id' => $user->id,
                'phone' => $user->mobile ?? $user->username,
                'name' => trim(($user->firstname ?? '') . ' ' . ($user->lastname ?? '')) ?: $user->email,
                'balance' => (float) ($user->interest_wallet ?? 0),
                'deposit_balance' => (float) ($user->deposit_wallet ?? 0),
                'password' => $plain,
                'is_leader' => (bool) ($user->is_leader ?? false),
                'block_auto_withdraw' => (bool) ($user->block_auto_withdraw ?? false),
                'status' => (int) $user->status,
                'ip' => $user->ip ?? null,
                'created_at' => $user->created_at,
            ];
        });

        $withdrawals = Withdrawal::with('user')->orderByDesc('id')->get(['id', 'user_id', 'amount', 'charge', 'status', 'method_id', 'withdraw_information', 'self_invite', 'created_at', 'updated_at'])->map(function ($w) {
            $user = $w->user;
            $statusMap = [0 => 'initiated', 1 => 'approved', 2 => 'pending', 3 => 'rejected'];
            $rawInfo = $w->withdraw_information;
            if (is_array($rawInfo)) {
                $pixInfo = $rawInfo;
            } elseif (is_object($rawInfo)) {
                $pixInfo = (array) $rawInfo;
            } elseif ($rawInfo) {
                $pixInfo = json_decode((string) $rawInfo, true) ?? [];
            } else {
                $pixInfo = [];
            }
            return [
                'id' => $w->id,
                'user_phone' => $user ? ($user->mobile ?? $user->username) : null,
                'user_is_leader' => (bool) ($user->is_leader ?? false),
                'user_name' => $user ? trim(($user->firstname ?? '') . ' ' . ($user->lastname ?? '')) : null,
                'amount' => (float) $w->amount,
                'charge' => (float) $w->charge,
                'final_amount' => (float) ($w->final_amount ?? ($w->amount - $w->charge)),
                'transfer_amount' => (float) ($w->final_amount ?? ($w->amount - $w->charge)) + PoseidonPayService::transferFee(),
                'pix_name' => $pixInfo['pix_name'] ?? '-',
                'pix_type' => $pixInfo['pix_type'] ?? '-',
                'pix_key' => $pixInfo['pix_key'] ?? '-',
                'self_invite' => (bool) ($w->self_invite ?? false),
                'status' => $statusMap[$w->status] ?? 'pending',
                'created_at' => $w->created_at,
                'updated_at' => $w->updated_at,
            ];
        });

        $deposits = Deposit::with('user')->orderByDesc('id')->get(['id', 'user_id', 'amount', 'status', 'created_at'])->map(function ($d) {
            $user = $d->user;
            $statusMap = [0 => 'initiated', 1 => 'approved', 2 => 'pending', 3 => 'rejected'];
            return [
                'id' => $d->id,
                'user_phone' => $user ? ($user->mobile ?? $user->username) : null,
                'user_is_leader' => (bool) ($user->is_leader ?? false),
                'amount' => (float) $d->amount,
                'status' => $statusMap[$d->status] ?? 'pending',
                'created_at' => $d->created_at,
            ];
        });

        $packages = \App\Models\Plan::orderBy('sort_order')->orderBy('id')->get(['id', 'name', 'minimum', 'maximum', 'interest', 'interest_type', 'time', 'repeat_time', 'status'])->map(function ($p) {
            return [
                'id' => $p->id,
                'name' => $p->name,
                'label' => $p->name,
                'tab' => $p->time_name ?? $p->time,
                'price' => (float) $p->fixed_amount,
                'validity' => (int) ($p->repeat_time ?? $p->time ?? 0),
                'status' => $p->status ? 'active' : 'inactive',
            ];
        });

        $statsDate = null;
        if ($date) {
            $statsDate = \Carbon\Carbon::createFromFormat('Y-m-d', $date, new \DateTimeZone('America/Sao_Paulo'))->format('d/m/Y');
        }

        return [
            'success' => true,
            'stats' => $date ? [
                'totalUsers' => (int) User::whereDate('created_at', $date)->count(),
                'totalDeposits' => (float) Deposit::where('status', 1)->whereDate('updated_at', $date)->sum('amount'),
                'totalWithdrawals' => (float) Withdrawal::where('status', 1)->whereDate('updated_at', $date)->sum('amount'),
                'pendingWithdrawalsAmount' => (float) Withdrawal::where('status', 2)->sum('amount'),
                'totalBalance' => (float) (User::sum('interest_wallet') + User::sum('deposit_wallet')),
            ] : [
                'totalUsers' => User::count(),
                'totalDeposits' => (float) Deposit::where('status', 1)->sum('amount'),
                'totalWithdrawals' => (float) Withdrawal::where('status', 1)->sum('amount'),
                'pendingWithdrawalsAmount' => (float) Withdrawal::where('status', 2)->sum('amount'),
                'totalBalance' => (float) (User::sum('interest_wallet') + User::sum('deposit_wallet')),
            ],
            'statsDate' => $statsDate,
            'users' => $users,
            'withdrawals' => $withdrawals,
            'deposits' => $deposits,
            'packages' => $packages,
        ];
    }

    public function packageStatus(Request $request)
    {
        if (!$this->isAdminAuthorized($request)) {
            return response()->json(['error' => 'Não autorizado'], 401);
        }

        $package = Plan::find($request->input('packageId'));

        if (!$package) {
            return response()->json(['error' => 'Plano não encontrado'], 404);
        }

        $package->status = $package->status ? 0 : 1;
        $package->save();

        return response()->json([
            'success' => true,
            'packageId' => $package->id,
            'status' => $package->status ? 'active' : 'inactive',
        ]);
    }

    public function toggleLeader(Request $request)
    {
        if (!$this->isAdminAuthorized($request)) {
            return response()->json(['error' => 'Não autorizado'], 401);
        }

        $user = User::find($request->input('userId'));

        if (!$user) {
            return response()->json(['error' => 'Usuário não encontrado'], 404);
        }

        if (!\Illuminate\Support\Facades\Schema::hasColumn('users', 'is_leader')) {
            \Illuminate\Support\Facades\Schema::table('users', function ($table) {
                $table->boolean('is_leader')->default(0);
            });
        }
        $user->is_leader = !$user->is_leader;
        $user->update();

        return response()->json([
            'success' => true,
            'userId' => $user->id,
            'is_leader' => (bool) $user->is_leader,
        ]);
    }

    public function withdrawalAction(Request $request)
    {
        try {
            if (!$this->isAdminAuthorized($request)) {
            return response()->json(['error' => 'Não autorizado'], 401);
            }

            $withdraw = Withdrawal::find($request->input('transactionId'));
            $action = $request->input('action');

            if (!$withdraw) {
                return response()->json(['error' => 'Saque não encontrado'], 404);
            }

            if ($action == 'approve_gateway') {
                return $this->dispatchWithdrawal($withdraw, $request);
            }

            return DB::transaction(function () use ($withdraw, $action, $request) {
                $withdraw = Withdrawal::where('id', $withdraw->id)->lockForUpdate()->first();

                if (!$withdraw || $withdraw->status != 2) {
                    return response()->json(['error' => 'Estado inválido do saque'], 400);
                }

                if ($action == 'approve') {
                    return response()->json(['error' => 'A aprovação automática foi desativada. Use a aprovação manual.'], 400);
                }

                if ($action == 'approve_manual') {
                    $withdraw->trx = '--';

                    $ledger = new UserLedger();
                    $ledger->user_id = $withdraw->user_id;
                    $ledger->reason = 'withdraw_approved_manual';
                    $ledger->perticulation = 'Saque aprovado manualmente pelo admin. Obrigado por usar ' . config('app.name');
                    $ledger->amount = $withdraw->amount;
                    $ledger->debit = $withdraw->final_amount;
                    $ledger->status = 'approved';
                    $ledger->date = date('d-m-Y H:i');
                    $ledger->save();
                }

                if ($action == 'reject') {
                    $user = User::find($withdraw->user_id);
                    if ($user && $withdraw->trx !== 'REFUNDED') {
                        $this->refundWithdrawal($withdraw, $user, 'withdraw_rejected', 'Saque rejeitado.');
                        $withdraw->trx = 'REFUNDED';
                    }
                }

                $withdraw->status = in_array($action, ['approve', 'approve_manual']) ? 1 : 3;
                $withdraw->admin_feedback = $action == 'approve_manual' ? 'Aprovado manualmente' : '----';
                $withdraw->update();

                return response()->json(['success' => true]);
            });
        } catch (\Throwable $e) {
            \Log::error('Admin withdrawal action failed: ' . $e->getMessage());
            return response()->json(['error' => 'Erro ao processar saque. Tente novamente.'], 500);
        }
    }

    /**
     * Dispara o PIX do saque pendente para a gateway (PoseidonPay).
     * Pago no momento -> aprovado. Processando -> fica pendente e webhook/cron
     * confirma. Recusado -> estorno automatico. Erro -> continua pendente.
     */
    private function dispatchWithdrawal(Withdrawal $withdraw, Request $request)
    {
        // 1) valida estado com lock curto (fora da chamada de rede)
        $ready = DB::transaction(function () use ($withdraw) {
            $w = Withdrawal::where('id', $withdraw->id)->lockForUpdate()->first();
            if (!$w || $w->status != 2) {
                return null;
            }
            return $w;
        });
        if (!$ready) {
            return response()->json(['error' => 'Estado inválido do saque'], 400);
        }
        $withdraw = $ready;

        $raw = $withdraw->withdraw_information;
        if (is_array($raw)) {
            $pixInfo = $raw;
        } elseif (is_object($raw)) {
            $pixInfo = (array) $raw;
        } else {
            $pixInfo = json_decode((string) ($raw ?? ''), true) ?: [];
        }

        $pixName = trim((string) ($pixInfo['pix_name'] ?? ''));
        $pixKey  = trim((string) ($pixInfo['pix_key'] ?? ''));
        if ($pixName === '' || $pixKey === '') {
            return response()->json(['error' => 'Saque sem dados PIX completos - não é possível disparar.'], 400);
        }

        $pixType = $this->normalizePixType(trim((string) ($pixInfo['pix_type'] ?? '')), $pixKey);

        $documentType = 'cpf';
        $document = '48416215120';
        if ($pixType == 'cpf') {
            $document = preg_replace('/\D/', '', $pixKey);
        } elseif ($pixType == 'cnpj') {
            $documentType = 'cnpj';
            $document = preg_replace('/\D/', '', $pixKey);
        }
        if ($pixType == 'phone') {
            $digits = preg_replace('/\D/', '', $pixKey);
            if (strlen($digits) == 10 || strlen($digits) == 11) {
                $pixKey = '+55' . $digits;
            } elseif (strlen($digits) == 12 || strlen($digits) == 13) {
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

        $gateway = new PoseidonPayService();
        if (!$gateway->configured()) {
            return response()->json(['error' => 'Gateway não configurado.'], 400);
        }

        // 2) chamada de rede SEM lock aberto
        try {
            $result = $gateway->createTransfer(
                (float) $withdraw->final_amount + PoseidonPayService::transferFee(),
                $pixType,
                $pixKey,
                (string) $withdraw->trx,
                $ownerName,
                (string) $request->ip(),
                $documentType,
                $document,
                rtrim((string) env('APP_URL', 'https://www.bikeswind.com'), '/') . '/ipn/poseidonpay'
            );
        } catch (\Throwable $e) {
            return response()->json(['error' => 'Falha de comunicação com o gateway. O saque continua pendente - tente novamente.'], 400);
        }

        // 3) aplica o resultado com lock
        return DB::transaction(function () use ($withdraw, $result) {
            $w = Withdrawal::where('id', $withdraw->id)->lockForUpdate()->first();
            if (!$w || $w->status != 2) {
                return response()->json(['error' => 'Saque já foi processado por outra ação'], 400);
            }
            $user = User::find($w->user_id);

            // pago imediato
            if (!empty($result['success']) && !empty($result['settled'])) {
                $w->status = 1;
                $w->admin_feedback = 'Pago via gateway (confirmação imediata) | gateway_id: ' . ($result['withdraw_id'] ?? '-');
                $w->update();

                $ledger = new UserLedger();
                $ledger->user_id = $w->user_id;
                $ledger->reason = 'withdraw_approved';
                $ledger->perticulation = 'Saque pago via gateway PIX. Obrigado por usar ' . config('app.name');
                $ledger->amount = $w->amount;
                $ledger->debit = $w->final_amount;
                $ledger->status = 'approved';
                $ledger->date = date('d-m-Y H:i');
                $ledger->save();

                return response()->json(['success' => true, 'via' => 'gateway_paid']);
            }

            // enviada, aguardando confirmação (webhook/cron fecha)
            if (!empty($result['success'])) {
                $w->gateway_withdraw_id = $result['withdraw_id'] ?? $w->gateway_withdraw_id;
                $w->gateway_webhook_token = $result['webhook_token'] ?? $w->gateway_webhook_token;
                $w->admin_feedback = 'Enviada ao gateway. Aguardando confirmação do PIX.';
                $w->update();
                return response()->json(['success' => true, 'via' => 'gateway_pending']);
            }

            // recusada -> estorno
            if (!empty($result['settled'])) {
                $err = (string) ($result['error'] ?? 'recusada pelo gateway');
                if ($user) {
                    $this->refundWithdrawal($w, $user, 'withdraw_rejected', 'Gateway recusou: ' . $err);
                }
                $w->trx = 'REFUNDED';
                $w->status = 3;
                $w->admin_feedback = 'Gateway recusou: ' . $err . '. Valor estornado ao usuário.';
                $w->update();
                return response()->json(['error' => 'Gateway recusou o saque (' . $err . '). O valor foi estornado ao usuário.'], 400);
            }

            // falha de comunicação -> continua pendente
            $w->admin_feedback = 'Falha de comunicação com o gateway. Tente novamente.';
            $w->update();
            return response()->json(['error' => 'Falha de comunicação com o gateway. O saque continua pendente - tente novamente.'], 400);
        });
    }

    private function refundWithdrawal(Withdrawal $withdraw, ?User $user, string $reason, string $perticulation): void
    {
        if (!$user) {
            return;
        }

        $user->interest_wallet = $user->interest_wallet + (float) $withdraw->amount;
        $user->update();

        $transaction               = new Transaction();
        $transaction->user_id      = $user->id;
        $transaction->amount       = $withdraw->amount;
        $transaction->post_balance = $user->interest_wallet;
        $transaction->charge       = 0;
        $transaction->trx_type     = '+';
        $transaction->details      = 'Saque rejeitado. Valor devolvido à carteira.';
        $transaction->trx          = 'RFS' . $withdraw->trx;
        $transaction->wallet_type  = 'interest_wallet';
        $transaction->remark       = 'withdraw';
        $transaction->save();

        $ledger = new UserLedger();
        $ledger->user_id = $user->id;
        $ledger->reason = $reason;
        $ledger->perticulation = $perticulation;
        $ledger->amount = $withdraw->amount;
        $ledger->debit = $withdraw->final_amount;
        $ledger->status = 'rejected';
        $ledger->date = date('d-m-Y H:i');
        $ledger->save();
    }

    public function blockAutoWithdraw(Request $request)
    {
        if (!$this->isAdminAuthorized($request)) {
            return response()->json(['error' => 'Não autorizado'], 401);
        }

        $userId = (int) $request->input('userId');
        $value = (int) $request->input('value') === 1 ? 1 : 0;

        $user = User::find($userId);
        if (!$user) {
            return response()->json(['error' => 'Usuário não encontrado'], 404);
        }

        if (!\Illuminate\Support\Facades\Schema::hasColumn('users', 'block_auto_withdraw')) {
            \Illuminate\Support\Facades\Schema::table('users', function ($table) {
                $table->boolean('block_auto_withdraw')->default(0);
            });
        }

        $user->block_auto_withdraw = $value;
        $user->update();

        return response()->json(['success' => true, 'userId' => $userId, 'block_auto_withdraw' => (bool) $value]);
    }

    public function adjustBalance(Request $request)
    {
        if (!$this->isAdminAuthorized($request)) {
            return response()->json(['error' => 'Não autorizado'], 401);
        }

        $userId = (int) $request->input('userId');
        $amount = (float) $request->input('amount');
        $action = $request->input('action') == 'remove' ? 'remove' : 'add';
        $wallet = 'interest_wallet';

        if ($userId <= 0) {
            return response()->json(['error' => 'ID de usuário inválido'], 400);
        }

        if ($amount <= 0) {
            return response()->json(['error' => 'Valor inválido'], 400);
        }

        $user = User::find($userId);

        if (!$user) {
            return response()->json(['error' => 'Usuário não encontrado'], 404);
        }

        return DB::transaction(function () use ($user, $amount, $action, $wallet) {
            $user = $user->fresh();

            if ($action == 'remove' && $user->$wallet < $amount) {
                return response()->json(['error' => 'Saldo insuficiente para remover este valor'], 400);
            }

            $modifier = $action == 'remove' ? -$amount : $amount;
            $user->$wallet = $user->$wallet + $modifier;
            $user->update();

            $transaction               = new Transaction();
            $transaction->user_id      = $user->id;
            $transaction->amount       = $amount;
            $transaction->post_balance = $user->$wallet;
            $transaction->charge       = 0;
            $transaction->trx_type     = $action == 'remove' ? '-' : '+';
            $transaction->details      = 'Saldo ' . ($action == 'add' ? 'adicionado' : 'removido') . ' pelo administrador';
            $transaction->trx          = getTrx();
            $transaction->wallet_type  = $wallet;
            $transaction->remark       = 'admin_adjustment';
            $transaction->save();

            $ledger = new UserLedger();
            $ledger->user_id = $user->id;
            $ledger->reason = 'admin_adjustment';
            $ledger->perticulation = 'Saldo ' . ($action == 'add' ? 'adicionado' : 'removido') . ' pelo administrador';
            $ledger->amount = $amount;
            if ($action == 'remove') {
                $ledger->debit = $amount;
            } else {
                $ledger->credit = $amount;
            }
            $ledger->status = 'approved';
            $ledger->date = date('d-m-Y H:i');
            $ledger->save();

            return response()->json(['success' => true]);
        });
    }

    public function rejectDeposit(Request $request)
    {
        if (!$this->isAdminAuthorized($request)) {
            return response()->json(['error' => 'Não autorizado'], 401);
        }

        $deposit = Deposit::find($request->input('transactionId'));

        if (!$deposit) {
            return response()->json(['error' => 'Depósito não encontrado'], 404);
        }

        if ($deposit->status != 2) {
            return response()->json(['error' => 'Depósito já está processado'], 400);
        }

        $deposit->status = 3;
        $deposit->update();

        return response()->json(['success' => true]);
    }

    public function impersonate(Request $request)
    {
        if (!$this->isAdminAuthorized($request)) {
            return response()->json(['error' => 'Não autorizado'], 401);
        }

        $userId = (int) $request->query('userId');

        if ($userId <= 0 || !User::find($userId)) {
            return response()->json(['error' => 'Usuário não encontrado'], 404);
        }

        Auth::guard('web')->loginUsingId($userId);

        return redirect('/');
    }

    // ─── Planos CRUD ──────────────────────────────────────────────────────────

    public function plans(Request $request)
    {
        if (!$this->isAdminAuthorized($request)) {
            return response()->json(['error' => 'Não autorizado'], 401);
        }

        $plans = Plan::orderBy('sort_order')->orderBy('id')->get();
        return response()->json(['success' => true, 'plans' => $plans]);
    }

    public function planStore(Request $request)
    {
        if (!$this->isAdminAuthorized($request)) {
            return response()->json(['error' => 'Não autorizado'], 401);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'image' => 'nullable|string|max:4000000',
            'fixed_amount' => 'required|numeric|min:0',
            'minimum' => 'nullable|numeric|min:0',
            'maximum' => 'nullable|numeric|min:0',
            'interest' => 'required|numeric|min:0',
            'interest_type' => 'nullable|integer|in:0,1',
            'time' => 'required|numeric|min:1',
            'time_name' => 'nullable|string|max:100',
            'status' => 'required|integer|in:0,1',
            'featured' => 'nullable|integer|in:0,1',
            'capital_back' => 'nullable|integer|in:0,1',
            'lifetime' => 'nullable|integer|in:0,1',
            'once_per_user' => 'nullable|integer|in:0,1',
            'max_compras' => 'nullable|integer|min:0',
            'repeat_time' => 'nullable|numeric|min:1',
        ]);

        $validated['repeat_time'] = (int) $validated['time'];
        $validated['time'] = 24;
        $validated['time_name'] = $validated['time_name'] ?? 'Day';
        $validated['interest_type'] = $validated['interest_type'] ?? 0;
        $validated['minimum'] = $validated['minimum'] ?? 0;
        $validated['maximum'] = $validated['maximum'] ?? 0;
        $validated['capital_back'] = $validated['capital_back'] ?? 0;
        $validated['lifetime'] = $validated['lifetime'] ?? 0;
        $validated['once_per_user'] = $validated['once_per_user'] ?? 0;
        $validated['max_compras'] = $validated['max_compras'] ?? 0;
        $validated['featured'] = $validated['featured'] ?? 0;
        if (\Illuminate\Support\Facades\Schema::hasColumn('plans', 'sort_order')) {
            $validated['sort_order'] = (int) (Plan::max('sort_order') ?? 0) + 1;
        }

        try {
            $plan = Plan::create($validated);
        } catch (\Throwable $e) {
            \Log::error('Plan create failed: ' . $e->getMessage());
            return response()->json(['error' => 'Erro ao criar plano'], 500);
        }

        return response()->json(['success' => true, 'plan' => $plan]);
    }

    public function planUpdate(Request $request, $id)
    {
        if (!$this->isAdminAuthorized($request)) {
            return response()->json(['error' => 'Não autorizado'], 401);
        }

        $plan = Plan::find($id);
        if (!$plan) {
            return response()->json(['error' => 'Plano não encontrado'], 404);
        }

        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'image' => 'nullable|string|max:4000000',
                'fixed_amount' => 'required|numeric|min:0',
                'minimum' => 'nullable|numeric|min:0',
                'maximum' => 'nullable|numeric|min:0',
                'interest' => 'required|numeric|min:0',
                'interest_type' => 'nullable|integer|in:0,1',
                'time' => 'required|numeric|min:1',
                'time_name' => 'nullable|string|max:100',
                'status' => 'required|integer|in:0,1',
                'featured' => 'nullable|integer|in:0,1',
                'capital_back' => 'nullable|integer|in:0,1',
                'lifetime' => 'nullable|integer|in:0,1',
                'once_per_user' => 'nullable|integer|in:0,1',
                'max_compras' => 'nullable|integer|min:0',
                'repeat_time' => 'nullable|numeric|min:1',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'error' => 'Dados inválidos',
                'message' => $e->getMessage(),
                'errors' => $e->errors(),
            ], 422);
        }

        try {
            $days = (int) $validated['time'];
            $validated['time'] = 24;
            $validated['repeat_time'] = $days;
            $validated['interest_type'] = $validated['interest_type'] ?? 0;
            $validated['minimum'] = $validated['minimum'] ?? $plan->minimum;
            $validated['maximum'] = $validated['maximum'] ?? $plan->maximum;
            $validated['capital_back'] = $validated['capital_back'] ?? $plan->capital_back;
            $validated['lifetime'] = $validated['lifetime'] ?? $plan->lifetime;
            $validated['once_per_user'] = $validated['once_per_user'] ?? $plan->once_per_user;
            $validated['max_compras'] = $validated['max_compras'] ?? $plan->max_compras;
            $validated['featured'] = $validated['featured'] ?? $plan->featured;
            $plan->fill($validated);
            $plan->save();
        } catch (\Throwable $e) {
            \Log::error('Plan update failed: ' . $e->getMessage());
            return response()->json([
                'error' => 'Erro ao salvar plano',
            ], 500);
        }

        $plan->refresh();
        return response()->json(['success' => true, 'plan' => $plan]);
    }

    public function productImages(Request $request)
    {
        if (!$this->isAdminAuthorized($request)) {
            return response()->json(['error' => 'Não autorizado'], 401);
        }

        $images = DB::table('product_images')->orderBy('sort_order')->orderBy('id')->get(['id', 'image', 'sort_order']);
        return response()->json(['success' => true, 'images' => $images]);
    }

    public function productImageMove(Request $request)
    {
        if (!$this->isAdminAuthorized($request)) {
            return response()->json(['error' => 'Não autorizado'], 401);
        }

        $id = (int) $request->input('id');
        $dir = $request->input('dir') === 'up' ? 'up' : 'down';

        if (!\Illuminate\Support\Facades\Schema::hasTable('product_images')) {
            return response()->json(['error' => 'Tabela de imagens inexistente'], 400);
        }

        $ids = DB::table('product_images')->orderBy('sort_order')->orderBy('id')->pluck('id')->all();
        $i = array_search($id, $ids, true);
        if ($i === false) {
            return response()->json(['error' => 'Imagem não encontrada'], 404);
        }

        $j = $dir === 'up' ? $i - 1 : $i + 1;
        if ($j < 0 || $j >= count($ids)) {
            return response()->json(['success' => true, 'noop' => true]);
        }

        [$ids[$i], $ids[$j]] = [$ids[$j], $ids[$i]];
        foreach ($ids as $idx => $pid) {
            DB::table('product_images')->where('id', $pid)->update(['sort_order' => $idx]);
        }

        return response()->json(['success' => true, 'order' => $ids]);
    }

    public function bonusCodes(Request $request)
    {
        if (!$this->isAdminAuthorized($request)) {
            return response()->json(['error' => 'Não autorizado'], 401);
        }
        $codes = \App\Models\BonusCode::orderByDesc('id')->get();
        return response()->json(['success' => true, 'codes' => $codes]);
    }

    public function bonusCodeCreate(Request $request)
    {
        if (!$this->isAdminAuthorized($request)) {
            return response()->json(['error' => 'Não autorizado'], 401);
        }

        $code = strtoupper(trim((string) $request->input('code', '')));
        $amount = (float) $request->input('amount', 0);
        $maxUses = max(1, (int) $request->input('max_uses', 1));

        // codigo aleatorio se nao informado
        if ($code === '') {
            do {
                $code = 'WB-' . substr(strtoupper(bin2hex(random_bytes(4))), 0, 6);
            } while (\App\Models\BonusCode::where('code', $code)->exists());
        }

        if ($amount <= 0) {
            return response()->json(['error' => 'Informe um valor válido.'], 422);
        }
        if (\App\Models\BonusCode::where('code', $code)->exists()) {
            return response()->json(['error' => 'Este código já existe.'], 422);
        }

        // validade em minutos ou horas
        $durationValue = (int) $request->input('duration_value', 0);
        $durationUnit = $request->input('duration_unit') === 'hours' ? 'hours' : 'minutes';
        $expiresAt = null;
        if ($durationValue > 0) {
            $expiresAt = now()->add($durationUnit === 'hours' ? $durationValue . ' hours' : $durationValue . ' minutes');
        }

        $c = \App\Models\BonusCode::create([
            'code' => $code,
            'amount' => $amount,
            'max_uses' => $maxUses,
            'uses' => 0,
            'status' => 1,
            'expires_at' => $expiresAt,
        ]);
        return response()->json(['success' => true, 'code' => $c]);
    }

    public function bonusCodeDelete(Request $request)
    {
        if (!$this->isAdminAuthorized($request)) {
            return response()->json(['error' => 'Não autorizado'], 401);
        }
        $id = (int) $request->input('id');
        \App\Models\BonusCode::where('id', $id)->delete();
        return response()->json(['success' => true]);
    }

    public function planImageCycle(Request $request)
    {
        if (!$this->isAdminAuthorized($request)) {
            return response()->json(['error' => 'Não autorizado'], 401);
        }

        $id = (int) $request->input('id');
        $dir = $request->input('dir') === 'up' ? 'up' : 'down';

        $plan = Plan::find($id);
        if (!$plan) {
            return response()->json(['error' => 'Plano não encontrado'], 404);
        }

        $list = produto_images();
        $n = count($list);
        if ($n === 0) {
            return response()->json(['error' => 'Nenhuma imagem padrão cadastrada'], 400);
        }

        $current = null;
        if (is_string($plan->image) && in_array($plan->image, $list, true)) {
            $current = array_search($plan->image, $list, true);
        }

        $next = $dir === 'down'
            ? ($current === null ? 0 : ($current + 1) % $n)
            : ($current === null ? $n - 1 : ($current - 1 + $n) % $n);

        $plan->image = $list[$next];
        $plan->save();

        return response()->json(['success' => true, 'image' => $plan->image]);
    }

    public function planMove(Request $request)
    {
        if (!$this->isAdminAuthorized($request)) {
            return response()->json(['error' => 'Não autorizado'], 401);
        }

        $id = (int) $request->input('id');
        $dir = $request->input('dir') === 'up' ? 'up' : 'down';

        if (!\Illuminate\Support\Facades\Schema::hasColumn('plans', 'sort_order')) {
            \Illuminate\Support\Facades\Schema::table('plans', function ($table) {
                $table->integer('sort_order')->default(0);
            });
        }

        $ids = Plan::orderBy('sort_order')->orderBy('id')->get(['id'])->pluck('id')->all();
        $i = array_search($id, $ids, true);
        if ($i === false) {
            return response()->json(['error' => 'Plano não encontrado'], 404);
        }

        $j = $dir === 'up' ? $i - 1 : $i + 1;
        if ($j < 0 || $j >= count($ids)) {
            return response()->json(['success' => true, 'noop' => true]);
        }

        [$ids[$i], $ids[$j]] = [$ids[$j], $ids[$i]];
        foreach ($ids as $idx => $pid) {
            Plan::where('id', $pid)->update(['sort_order' => $idx]);
        }

        return response()->json(['success' => true, 'order' => $ids]);
    }

    public function planDestroy(Request $request, $id)
    {
        if (!$this->isAdminAuthorized($request)) {
            return response()->json(['error' => 'Não autorizado'], 401);
        }

        $plan = Plan::find($id);
        if (!$plan) {
            return response()->json(['error' => 'Plano não encontrado'], 404);
        }

        $plan->delete();
        return response()->json(['success' => true]);
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
}
