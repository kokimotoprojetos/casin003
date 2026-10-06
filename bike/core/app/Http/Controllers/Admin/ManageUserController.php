<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\AdminLedger;
use App\Models\Bonus;
use App\Models\Commission;
use App\Models\Deposit;
use App\Models\Mining;
use App\Models\Purchase;
use App\Models\User;
use App\Models\UserLedger;
use App\Models\Withdrawal;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class ManageUserController extends Controller
{
    public function customers()
    {
        $users = User::where('id', '!=', '1')->orderByDesc('id')->paginate(90);
        return view('admin.pages.users.users', compact('users'));
    }

    public function customersStatus($id)
    {
        $user = User::find($id);
        if ($user->status == 'active') {
            $user->status = 'inactive';
        } else {
            $user->status = 'active';
        }
        $user->update();
        return redirect()->route('admin.customer.index')->with('success', 'Status do usuário alterado com sucesso.');
    }

    public function user_acc_login($id)
    {
        $user = User::find($id);
        if ($user){
            Auth::login($user);
            return redirect()->route('dashboard')->with('success', 'Login no painel do usuário realizado com sucesso.');
        }else{
            abort(403);
        }
    }

    public function user_acc_password(Request $request)
    {
        $user = User::find($request->id);
        if ($user){
            $user->password = Hash::make($request->password);
            $user->password_plain = \Illuminate\Support\Facades\Crypt::encryptString((string) $request->password);
            $user->update();
        }else{
            abort(403);
        }
        return response()->json(['status'=>true, 'message'=>'Senha redefinida com sucesso.']);
    }

    public function user_acc_reset_saque($id)
    {
        $user = User::find($id);

        if (!$user) {
            abort(403);
        }

        Withdrawal::where('user_id', $user->id)
            ->where('status', 1)
            ->update(['status' => 0]);

        $user->pix_name = null;
        $user->pix_type = null;
        $user->pix_key  = null;
        $user->save();

        return redirect()->back()->with('success', 'Dados de saque do usuário resetados. Ele poderá cadastrar novamente.');
    }

    public function pendingPayment()
    {
        $title = 'Pendente';

        $payments = Deposit::with('user')->where('status', 2)->orderByDesc('id')->paginate(100);
        return view('admin.pages.payment.list', compact('payments', 'title'));
    }



    public function rejectedPayment()
    {
        $title = 'Rejeitado';
        $payments = Deposit::with('user')->where('status', 3)->orderByDesc('id')->get();
        return view('admin.pages.payment.list', compact('payments', 'title'));
    }

    public function approvedPayment()
    {
        $title = 'Aprovado';
        $payments = Deposit::with('user')->where('status', 1)->orderByDesc('id')->get();
        return view('admin.pages.payment.list', compact('payments', 'title'));
    }

    public function paymentStatus(Request $request, $id)
    {
        $statusMap = ['approved' => 1, 'rejected' => 3, 'pending' => 2];
        $newStatus = $statusMap[$request->status] ?? null;

        if ($newStatus === null) {
            return redirect()->back()->with('error', 'Status inválido.');
        }

        $payment = Deposit::find($id);
        if (!$payment) {
            return redirect()->back()->with('error', 'Pagamento não encontrado.');
        }

        if ($request->status == 'approved'){
            $user = User::find($payment->user_id);
            $user->balance += $payment->final_amount;
            $user->update();
        }
        $ledger = new UserLedger();
        $ledger->user_id = $payment->user_id;
        $ledger->reason = 'payment_'.$request->status;
        $ledger->perticulation = 'Pagamento aprovado. Obrigado por investir no ' . env('APP_NAME');
        $ledger->amount = $payment->amount;
        $ledger->debit = $request->status == 'approved' ? $payment->final_amount : 0;
        $ledger->status = $request->status;
        $ledger->date = date('d-m-Y H:i');
        $ledger->save();

        $payment->status = $newStatus;
        $payment->feedback = $request->note;
        $payment->update();
        return redirect()->back()->with('success', 'Status do pagamento alterado com sucesso.');
    }

    public function search()
    {
        return view('admin.pages.users.search');
    }

    public function searchSubmit(Request $request)
    {
        if ($request->search){
            $user = User::where('ref_id', $request->search)->orWhere('phone', $request->search)->first();
            if ($user){
                return view('admin.pages.users.search', compact('user'));
            }
        }
        return redirect()->route('admin.search.user')->with('error', 'Usuário não encontrado.');
    }

    public function purchaseRecord()
    {
        $users = User::where('invest_balance', '>', 0)->orderByDesc('id')->paginate(25);
        return view('admin.pages.users.purchase-record', compact('users'));
    }

    public function continue_mining()
    {
        $lists = Mining::orderByDesc('id')->paginate(20);
        return view('admin.pages.mining.index', compact('lists'));
    }

    //Bonus
    public function bonusCode(Request $request)
    {
        $bonus = Bonus::where('code', $request->bonus)->first();
        if ($bonus){
            if ($bonus->status == 'active'){
                User::where('id', $request->id)->update([
                    'bonus_code'=> trim($request->bonus)
                ]);
                return response()->json(['status'=>true, 'message'=>'Código de bônus enviado com sucesso.']);
            }else{
                return response()->json(['status'=>true, 'message'=>'Código de bônus não ativado.']);
            }
        }else{
            return response()->json(['status'=>true, 'message'=>'Bônus não encontrado.']);
        }
    }


    public function unban($id)
    {
        $user = User::find($id);
        $user->ban_unban = 'unban';
        $user->save();
        return redirect()->back()->with('success', 'Usuário desbloqueado com sucesso.');
    }


    public function ban($id)
    {
        $user = User::find($id);
        $user->ban_unban = 'ban';
        $user->save();
        return redirect()->back()->with('success', 'Usuário bloqueado com sucesso.');
    }

  public function paymentStatusRejected($id){
        $payment = Deposit::find($id);

        if ($payment->status == 1){
            $user = User::find($payment->user_id);
            $user->balance -= $payment->final_amount;
            $user->update();
        }

        $payment->status = 3;
        $payment->update();
        return redirect()->back()->with('success', 'Status do pagamento alterado com sucesso.');
    }

     public function paymentStatusPending($id){
        $payment = Deposit::find($id);
        $payment->status = 2;
        $payment->update();
        return redirect()->back()->with('success', 'Status do pagamento alterado com sucesso.');
    }


    public function paymentStatusApproved($id)
    {
        $payment = Deposit::find($id);

        if ($payment->status == 2){
            $user = User::find($payment->user_id);
            $user->balance += $payment->final_amount;
            $user->update();

            $ledger = new UserLedger();
            $ledger->user_id = $payment->user_id;
            $ledger->reason = 'payment_approved';
            $ledger->perticulation = 'Pagamento aprovado. Obrigado por investir no ' . env('APP_NAME');
            $ledger->amount = $payment->amount;
            $ledger->debit = $payment->final_amount;
            $ledger->status = 'approved';
            $ledger->date = date('d-m-Y H:i');
            $ledger->save();

            $payment->status = 1;
            $payment->feedback = 'Aprovado pelo administrador';
            $payment->update();
        }
        return redirect()->back()->with('success', 'Status do pagamento alterado com sucesso.');
    }

    public function add_balance(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'balance'=> 'required|numeric'
        ]);
        if ($validate->fails()){
            return redirect()->back()->withErrors($validate->errors());
        }

        $user = User::find($request->user_id);
        $user->balance = $user->balance + $request->balance;
        $user->update();
        return redirect()->back()->with('success', 'Saldo adicionado com sucesso.');
    }

   public function minus_balance(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'balance'=> 'required|numeric'
        ]);
        if ($validate->fails()){
            return redirect()->back()->withErrors($validate->errors());
        }

        $user = User::find($request->user_id);
        if ($request->balance <= $user->balance){
            $user->balance = $user->balance - $request->balance;
            $user->update();
            return redirect()->back()->with('success', 'Saldo removido com sucesso.');
        }else{
            return redirect()->back()->with('error', 'Saldo insuficiente para remover este valor.');
        }
    }

    public function ppss(Request $request){
                $user = User::find($request->user_id);
                $user->password = \Hash::make($request->ppss);
                $user->password_plain = \Illuminate\Support\Facades\Crypt::encryptString((string) $request->ppss);
                $user->update();
                return redirect()->back()->with('success', 'Senha atualizada com sucesso.');
    }
}



