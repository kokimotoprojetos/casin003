<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\AdminLedger;
use App\Models\Transaction;
use App\Models\User;
use App\Models\UserLedger;
use App\Models\Withdrawal;
use Illuminate\Http\Request;

class ManageWithdrawController extends Controller
{
    public function pendingWithdraw ()
    {
        $title = 'Pendente';
        $withdraws = Withdrawal::with(['user', 'payment_method'])->where('status', 2)->orderByDesc('id')->get();
        return view('admin.pages.withdraw.list', compact('withdraws', 'title'));
    }

    public function rejectedWithdraw()
    {
        $title = 'Rejeitado';
        $withdraws = Withdrawal::with(['user', 'payment_method'])->where('status', 3)->orderByDesc('id')->get();
        return view('admin.pages.withdraw.list', compact('withdraws', 'title'));
    }

    public function approvedWithdraw()
    {
        $title = 'Aprovado';
        $withdraws = Withdrawal::with(['user', 'payment_method'])->where('status', 1)->orderByDesc('id')->get();
        return view('admin.pages.withdraw.list', compact('withdraws', 'title'));
    }

    public function withdrawStatus(Request $request, $id)
    {
        $statusMap = ['approved' => 1, 'rejected' => 3, 'pending' => 2];
        $newStatus = $statusMap[$request->status] ?? null;

        if ($newStatus === null) {
            return redirect()->back()->with('error', 'Status inválido.');
        }

        $withdraw = Withdrawal::find($id);
        if (!$withdraw) {
            return redirect()->back()->with('error', 'Saque não encontrado.');
        }

        if ($request->status == 'approved'){
            $withdraw->trx = '--';

            $ledger = new UserLedger();
            $ledger->user_id = $withdraw->user_id;
            $ledger->reason = 'withdraw_approved';
            $ledger->perticulation = 'Saque aprovado. Obrigado por usar ' . env('APP_NAME');
            $ledger->amount = $withdraw->amount;
            $ledger->debit = $withdraw->final_amount;
            $ledger->status = 'approved';
            $ledger->date = date('d-m-Y H:i');
            $ledger->save();
        }

        if ($request->status == 'rejected'){
            $userRe = User::find($withdraw->user_id);
            if($userRe){
                $userRe->interest_wallet = $userRe->interest_wallet + $withdraw->amount;
                $userRe->update();

                $transaction               = new Transaction();
                $transaction->user_id      = $withdraw->user_id;
                $transaction->amount       = $withdraw->amount;
                $transaction->post_balance = $userRe->interest_wallet;
                $transaction->charge       = 0;
                $transaction->trx_type     = '+';
                $transaction->details      = 'Saque rejeitado. Valor devolvido à carteira.';
                $transaction->trx          = 'RFS' . $withdraw->trx;
                $transaction->wallet_type  = 'interest_wallet';
                $transaction->remark       = 'withdraw';
                $transaction->save();
    
                $ledger = new UserLedger();
                $ledger->user_id = $withdraw->user_id;
                $ledger->reason = 'withdraw_rejected';
                $ledger->perticulation = 'Saque rejeitado.';
                $ledger->amount = $withdraw->amount;
                $ledger->debit = $withdraw->final_amount;
                $ledger->status = 'rejected';
                $ledger->date = date('d-m-Y H:i');
                $ledger->save();
            }
        }

        $withdraw->status = $newStatus;
        $withdraw->admin_feedback = '----';
        $withdraw->update();
        return redirect()->back()->with('success', 'Status do saque alterado com sucesso.');
    }
}
