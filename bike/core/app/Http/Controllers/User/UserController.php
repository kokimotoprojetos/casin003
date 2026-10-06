<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Lib\FormProcessor;
use App\Lib\GoogleAuthenticator;
use App\Lib\HyipLab;
use App\Models\Deposit;
use App\Models\Form;
use App\Models\Invest;
use App\Models\PromotionTool;
use App\Models\Referral;
use App\Models\SupportTicket;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Withdrawal;
use App\Services\PixDepositSweeper;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    public function produtos()
    {
        $pageTitle = 'Produtos';
        return view($this->activeTemplate . 'user.produtos', compact('pageTitle'));
    }

    public function home()
    {
        PixDepositSweeper::sweepForUser(auth()->id());
        \App\Services\WithdrawSettler::settlePending(3);

        $data['pageTitle']         = 'Painel';
        $user                      = auth()->user();
        $data['user']              = $user;
        $data['totalInvest']       = Invest::where('user_id', auth()->id())->sum('amount');
        $data['totalWithdraw']     = Withdrawal::where('user_id', $user->id)->whereIn('status', [1])->sum('amount');
        $data['lastWithdraw']      = Withdrawal::where('user_id', $user->id)->whereIn('status', [1])->latest()->first('amount');
        $data['totalDeposit']      = Deposit::where('user_id', $user->id)->where('status', 1)->sum('amount');
        $data['lastDeposit']       = Deposit::where('user_id', $user->id)->where('status', 1)->latest()->first('amount');
        $data['totalTicket']       = SupportTicket::where('user_id', $user->id)->count();
        $data['transactions']      = $data['user']->transactions->sortByDesc('id')->take(8);
        $data['referral_earnings'] = Transaction::where('remark', 'referral_commission')->where('user_id', auth()->id())->sum('amount');

        $data['submittedDeposits']  = Deposit::where('status', '!=', 0)->where('user_id', $user->id)->sum('amount');
        $data['successfulDeposits'] = Deposit::successful()->where('user_id', $user->id)->sum('amount');
        $data['requestedDeposits']  = Deposit::where('user_id', $user->id)->sum('amount');
        $data['initiatedDeposits']  = Deposit::initiated()->where('user_id', $user->id)->sum('amount');
        $data['pendingDeposits']    = Deposit::pending()->where('user_id', $user->id)->sum('amount');
        $data['rejectedDeposits']   = Deposit::rejected()->where('user_id', $user->id)->sum('amount');

        $data['submittedWithdrawals']  = Withdrawal::where('status', '!=', 0)->where('user_id', $user->id)->sum('amount');
        $data['successfulWithdrawals'] = Withdrawal::approved()->where('user_id', $user->id)->sum('amount');
        $data['rejectedWithdrawals']   = Withdrawal::rejected()->where('user_id', $user->id)->sum('amount');
        $data['initiatedWithdrawals']  = Withdrawal::initiated()->where('user_id', $user->id)->sum('amount');
        $data['requestedWithdrawals']  = Withdrawal::where('user_id', $user->id)->sum('amount');
        $data['pendingWithdrawals']    = Withdrawal::pending()->where('user_id', $user->id)->sum('amount');

        $data['invests']               = Invest::where('user_id', $user->id)->sum('amount');
        $data['completedInvests']      = Invest::where('user_id', $user->id)->where('status', 0)->sum('amount');
        $data['runningInvests']        = Invest::where('user_id', $user->id)->where('status', 1)->sum('amount');
        $data['interests']             = Transaction::where('remark', 'interest')->where('user_id', $user->id)->sum('amount');
        $data['depositWalletInvests']  = Invest::where('user_id', $user->id)->where('wallet_type', 'deposit_wallet')->where('status', 1)->sum('amount');
        $data['interestWalletInvests'] = Invest::where('user_id', $user->id)->where('wallet_type', 'interest_wallet')->where('status', 1)->sum('amount');

        $data['isHoliday']      = HyipLab::isHoliDay(now()->toDateTimeString(), gs());
        $data['nextWorkingDay'] = now()->toDateString();
        if ($data['isHoliday']) {
            $data['nextWorkingDay'] = HyipLab::nextWorkingDay(24);
            $data['nextWorkingDay'] = Carbon::parse($data['nextWorkingDay'])->toDateString();
        }

        $data['chartData'] = Transaction::where('remark', 'interest')
            ->where('created_at', '>=', Carbon::now()->subDays(30))
            ->where('user_id', $user->id)
            ->selectRaw("SUM(amount) as amount, DATE_FORMAT(created_at,'%Y-%m-%d') as date")
            ->orderBy('created_at', 'asc')
            ->groupBy('date')
            ->get();

        return view($this->activeTemplate . 'user.dashboard', $data);
    }

    public function depositHistory(Request $request)
    {
        PixDepositSweeper::sweepForUser(auth()->id());

        $pageTitle = 'Histórico de Depósitos';
        $userId    = auth()->id();

        // Apenas entradas pagas/creditadas: depósitos, comissões e ajustes do admin
        $rows = DB::table('transactions')
            ->leftJoin('deposits', 'deposits.trx', '=', 'transactions.trx')
            ->where('transactions.user_id', $userId)
            ->whereIn('transactions.remark', ['deposit', 'referral_commission', 'admin_adjustment'])
            ->select(
                'transactions.id',
                'transactions.amount',
                'transactions.trx_type',
                'transactions.trx',
                'transactions.remark',
                'transactions.created_at',
                'deposits.status as deposit_status'
            )
            ->orderByDesc('transactions.id')
            ->paginate(getPaginate());

        return view($this->activeTemplate . 'user.deposit_history', compact('pageTitle', 'rows'));
    }

    public function show2faForm()
    {
        $general   = gs();
        $ga        = new GoogleAuthenticator();
        $user      = auth()->user();
        $secret    = $ga->createSecret();
        $qrCodeUrl = $ga->getQRCodeGoogleUrl($user->username . '@' . $general->site_name, $secret);
        $pageTitle = 'Autenticação em Duas Etapas';
        return view($this->activeTemplate . 'user.twofactor', compact('pageTitle', 'secret', 'qrCodeUrl'));
    }

    public function create2fa(Request $request)
    {
        $user = auth()->user();
        $this->validate($request, [
            'key'  => 'required',
            'code' => 'required',
        ]);
        $response = verifyG2fa($user, $request->code, $request->key);
        if ($response) {
            $user->tsc = $request->key;
            $user->ts  = 1;
            $user->save();
            $notify[] = ['success', 'Google Authenticator ativado com sucesso'];
            return back()->withNotify($notify);
        } else {
            $notify[] = ['error', 'Código de verificação incorreto'];
            return back()->withNotify($notify);
        }
    }

    public function disable2fa(Request $request)
    {
        $this->validate($request, [
            'code' => 'required',
        ]);

        $user     = auth()->user();
        $response = verifyG2fa($user, $request->code);
        if ($response) {
            $user->tsc = null;
            $user->ts  = 0;
            $user->save();
            $notify[] = ['success', 'Autenticação em duas etapas desativada com sucesso'];
        } else {
            $notify[] = ['error', 'Código de verificação incorreto'];
        }
        return back()->withNotify($notify);
    }

    public function transactions(Request $request)
    {
        $pageTitle = 'Transações';
        $remarks   = Transaction::distinct('remark')->orderBy('remark')->get('remark');

        $transactions = Transaction::where('user_id', auth()->id())->searchable(['trx'])->filter(['trx_type', 'remark', 'wallet_type'])->orderBy('id', 'desc')->paginate(getPaginate());
        return view($this->activeTemplate . 'user.transactions', compact('pageTitle', 'transactions', 'remarks'));
    }

    public function kycForm()
    {
        if (auth()->user()->kv == 2) {
            $notify[] = ['error', 'Seu KYC está em análise'];
            return to_route('user.home')->withNotify($notify);
        }
        if (auth()->user()->kv == 1) {
            $notify[] = ['error', 'Você já está com KYC verificado'];
            return to_route('user.home')->withNotify($notify);
        }
        $pageTitle = 'Formulário KYC';
        $form      = Form::where('act', 'kyc')->first();
        return view($this->activeTemplate . 'user.kyc.form', compact('pageTitle', 'form'));
    }

    public function kycData()
    {
        $user      = auth()->user();
        $pageTitle = 'Dados KYC';
        return view($this->activeTemplate . 'user.kyc.info', compact('pageTitle', 'user'));
    }

    public function kycSubmit(Request $request)
    {
        $form           = Form::where('act', 'kyc')->first();
        $formData       = $form->form_data;
        $formProcessor  = new FormProcessor();
        $validationRule = $formProcessor->valueValidation($formData);
        $request->validate($validationRule);

        $userData       = $formProcessor->processFormData($request, $formData);
        $user           = auth()->user();
        $user->kyc_data = $userData;
        $user->kv       = 2;
        $user->save();

        $notify[] = ['success', 'Dados KYC enviados com sucesso'];
        return to_route('user.home')->withNotify($notify);

    }

    public function attachmentDownload($fileHash)
    {
        $filePath  = decrypt($fileHash);

        // Prevent path traversal
        $realPath = realpath($filePath);
        $storagePath = realpath(storage_path('app'));
        $publicPath = realpath(public_path());
        if ($realPath === false || ($storagePath !== false && strpos($realPath, $storagePath) !== 0) && ($publicPath !== false && strpos($realPath, $publicPath) !== 0)) {
            abort(403);
        }

        $extension = pathinfo($filePath, PATHINFO_EXTENSION);
        $general   = gs();
        $title     = slug($general->site_name) . '- attachments.' . $extension;
        $mimetype  = mime_content_type($filePath);
        header('Content-Disposition: attachment; filename="' . $title);
        header("Content-Type: " . $mimetype);
        return readfile($filePath);
    }

    public function userData()
    {
        $user = auth()->user();
        if ($user->profile_complete == 1) {
            return to_route('user.home');
        }
        $pageTitle = 'Dados do Usuário';
        return view($this->activeTemplate . 'user.user_data', compact('pageTitle', 'user'));
    }

    public function userDataSubmit(Request $request)
    {
        $user = auth()->user();
        if ($user->profile_complete == 1) {
            return to_route('user.home');
        }
        $request->validate([
            'firstname' => 'required',
            'lastname'  => 'required',
        ]);
        $user->firstname = $request->firstname;
        $user->lastname  = $request->lastname;
        $user->address   = [
            'country' => @$user->address->country,
            'address' => $request->address,
            'state'   => $request->state,
            'zip'     => $request->zip,
            'city'    => $request->city,
        ];
        $user->profile_complete = 1;
        $user->save();

        $notify[] = ['success', 'Cadastro concluído com sucesso'];
        return to_route('user.home')->withNotify($notify);
    }

    public function referrals()
    {
        $pageTitle = 'Indicações';
        $user      = auth()->user();
        $maxLevel  = Referral::max('level');

        $referrals = User::where('ref_by', $user->id)
            ->withCount(['invests as active_plans_count' => function ($q) {
                $q->where('status', 1);
            }])
            ->withSum(['invests as active_plans_total' => function ($q) {
                $q->where('status', 1);
            }], 'amount')
            ->orderByDesc('id')
            ->get();

        $teamInvestments = Invest::whereIn('user_id', User::where('ref_by', $user->id)->pluck('id'))->sum('amount');

        return view($this->activeTemplate . 'user.referrals', compact('pageTitle', 'user', 'maxLevel', 'referrals', 'teamInvestments'));
    }

    public function promotionalBanners()
    {
        $general = gs();
        $promotionCount = PromotionTool::count();
        if (!$general->promotional_tool || !$promotionCount) {
            abort(404);
        }
        $pageTitle    = 'Banners Promocionais';
        $banners      = PromotionTool::orderBy('id', 'desc')->get();
        $emptyMessage = 'Nenhum banner encontrado';
        return view($this->activeTemplate . 'user.promo_tools', compact('pageTitle', 'banners', 'emptyMessage'));
    }

    public function transferBalance()
    {
        $general = gs();
        if (!$general->b_transfer) {
            abort(404);
        }
        $pageTitle = 'Transferência de Saldo';
        $user      = auth()->user();
        return view($this->activeTemplate . 'user.balance_transfer', compact('pageTitle', 'user'));
    }

    public function transferBalanceSubmit(Request $request)
    {
        $general = gs();
        if (!$general->b_transfer) {
            abort(404);
        }
        $request->validate([
            'username' => 'required',
            'amount'   => 'required|numeric|gt:0',
            'wallet'   => 'required|in:deposit_wallet,interest_wallet',
        ]);

        $user = auth()->user();
        if ($user->username == $request->username) {
            $notify[] = ['error', 'Não é possível transferir saldo para sua própria conta'];
            return back()->withNotify($notify);
        }

        $receiver = User::where('username', $request->username)->first();
        if (!$receiver) {
            $notify[] = ['error', 'Usuário não encontrado'];
            return back()->withNotify($notify);
        }

        if ($user->ts) {
            $response = verifyG2fa($user, $request->authenticator_code);
            if (!$response) {
                $notify[] = ['error', 'Código de verificação incorreto'];
                return back()->withNotify($notify);
            }
        }

        $general     = gs();
        $charge      = $general->f_charge + ($request->amount * $general->p_charge) / 100;
        $afterCharge = $request->amount + $charge;
        $wallet      = $request->wallet;

        if ($user->$wallet < $afterCharge) {
            $notify[] = ['error', 'Saldo insuficiente nesta carteira'];
            return back()->withNotify($notify);
        }

        $user->$wallet -= $afterCharge;
        $user->save();

        $trx1                      = getTrx();
        $transaction               = new Transaction();
        $transaction->user_id      = $user->id;
        $transaction->amount       = getAmount($afterCharge);
        $transaction->charge       = $charge;
        $transaction->trx_type     = '-';
        $transaction->trx          = $trx1;
        $transaction->wallet_type  = $wallet;
        $transaction->remark       = 'balance_transfer';
        $transaction->details      = 'Transferência para ' . $receiver->username;
        $transaction->post_balance = getAmount($user->$wallet);
        $transaction->save();

        $receiver->deposit_wallet += $request->amount;
        $receiver->save();

        $trx2                      = getTrx();
        $transaction               = new Transaction();
        $transaction->user_id      = $receiver->id;
        $transaction->amount       = getAmount($request->amount);
        $transaction->charge       = 0;
        $transaction->trx_type     = '+';
        $transaction->trx          = $trx2;
        $transaction->wallet_type  = 'deposit_wallet';
        $transaction->remark       = 'balance_received';
        $transaction->details      = 'Saldo recebido de ' . $user->username;
        $transaction->post_balance = getAmount($receiver->deposit_wallet);
        $transaction->save();

        notify($user, 'BALANCE_TRANSFER', [
            'amount'        => showAmount($request->amount),
            'charge'        => showAmount($charge),
            'wallet_type'   => keyToTitle($wallet),
            'post_balance'  => showAmount($user->$wallet),
            'user_fullname' => $receiver->fullname,
            'username'      => $receiver->username,
            'trx'           => $trx1,
        ]);

        notify($receiver, 'BALANCE_RECEIVE', [
            'wallet_type'  => 'Carteira de Depósito',
            'amount'       => showAmount($request->amount),
            'post_balance' => showAmount($receiver->deposit_wallet),
            'sender'       => $user->username,
            'trx'          => $trx2,
        ]);

        $notify[] = ['success', 'Saldo transferido com sucesso'];
        return back()->withNotify($notify);
    }

    public function findUser(Request $request)
    {
        $user    = User::where('username', $request->username)->first();
        $message = null;
        if (!$user) {
            $message = 'Usuário não encontrado';
        }
        if (@$user->username == auth()->user()->username) {
            $message = 'Não é possível enviar dinheiro para sua própria conta';
        }
        return response(['message' => $message]);
    }

    const DAILY_REWARD = 0.05;

    public function redeemBonusCode(Request $request)
    {
        $user = auth()->user();
        $code = strtoupper(trim((string) $request->input('code', '')));

        if ($code === '') {
            return response()->json(['error' => 'Informe o código bônus.'], 422);
        }

        try {
            $result = DB::transaction(function () use ($code, $user) {
                $bonus = \App\Models\BonusCode::where('code', $code)->lockForUpdate()->first();

                if (!$bonus || !$bonus->status) {
                    return ['error' => 'Código inválido ou inativo.'];
                }
                if ($bonus->expires_at && $bonus->expires_at->isPast()) {
                    return ['error' => 'Este código expirou.'];
                }
                if ($bonus->uses >= $bonus->max_uses) {
                    return ['error' => 'Este código já atingiu o limite de usos.'];
                }
                $jaUsou = \App\Models\BonusRedemption::where('user_id', $user->id)->where('code_id', $bonus->id)->lockForUpdate()->exists();
                if ($jaUsou) {
                    return ['error' => 'Você já resgatou este código.'];
                }

                $bonus->uses = $bonus->uses + 1;
                $bonus->save();

                $user = User::where('id', $user->id)->lockForUpdate()->first();
                $user->interest_wallet += (float) $bonus->amount;
                $user->save();

                $transaction = new Transaction();
                $transaction->user_id = $user->id;
                $transaction->amount = $bonus->amount;
                $transaction->post_balance = $user->interest_wallet;
                $transaction->charge = 0;
                $transaction->trx_type = '+';
                $transaction->trx = getTrx();
                $transaction->details = 'Bônus resgatado: ' . $bonus->code;
                $transaction->remark = 'bonus_code';
                $transaction->wallet_type = 'interest_wallet';
                $transaction->save();

                \App\Models\BonusRedemption::create([
                    'user_id' => $user->id,
                    'code_id' => $bonus->id,
                    'amount' => $bonus->amount,
                ]);

                return ['success' => 'Código resgatado! +' . showAmount($bonus->amount) . ' ' . gs()->cur_text . ' adicionado ao seu saldo.'];
            });
        } catch (\Throwable $e) {
            return response()->json(['error' => 'Erro ao resgatar o código. Tente novamente.'], 500);
        }

        if (isset($result['error'])) {
            return response()->json(['error' => $result['error']], 422);
        }
        return response()->json(['success' => true, 'message' => $result['success']]);
    }

    public function collectDailyReward()
    {
        $user = auth()->user();
        $today = Carbon::today()->toDateString();

        if ($user->last_daily_reward === $today) {
            return response()->json(['error' => 'Já coletou a recompensa de hoje'], 422);
        }

        $rewardAmount = self::DAILY_REWARD;
        $user->interest_wallet += $rewardAmount;
        $user->last_daily_reward = $today;
        $user->save();

        $trx = getTrx();
        $transaction = new Transaction();
        $transaction->user_id = $user->id;
        $transaction->amount = $rewardAmount;
        $transaction->charge = 0;
        $transaction->post_balance = $user->interest_wallet;
        $transaction->trx_type = '+';
        $transaction->trx = $trx;
        $transaction->remark = 'daily_reward';
        $transaction->wallet_type = 'interest_wallet';
        $transaction->details = showAmount($rewardAmount) . ' ' . gs()->cur_text . ' Recompensa diária coletada';
        $transaction->save();

        return response()->json(['success' => true, 'balance' => $user->interest_wallet]);
    }
}
