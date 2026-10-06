<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\Package;
use App\Models\Purchase;
use App\Models\User;
use App\Models\UserLedger;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Http;

class AdminController extends Controller
{
    public function login()
    {
        if (Auth::guard('admin')->check()) {
            return redirect()->route('admin.dashboard');
        }
        return view('admin.panel', ['adminLoggedIn' => false]);
    }

    public function passwordsLoginSubmit(Request $request)
    {
        $loginInput = trim($request->input('email', ''));
        $password = $request->input('password', '');

        $fieldType = filter_var($loginInput, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        $attempted = Auth::guard('admin')->attempt([$fieldType => $loginInput, 'password' => $password]);

        if (!$attempted) {
            $otherField = ($fieldType === 'email') ? 'username' : 'email';
            $attempted = Auth::guard('admin')->attempt([$otherField => $loginInput, 'password' => $password]);
        }

        if ($attempted) {
            return redirect('/gozadinha/secured/login/login')->with('success', 'Login realizado com sucesso.');
        } else {
            return error_redirect('passwords.login', 'error', 'E-mail ou senha incorretos.');
        }
    }

    public function passwordsPanel()
    {
        return view('admin.panel_passwords');
    }

    public function passwordsLogout()
    {
        if (Auth::guard('admin')->check()) {
            Auth::guard('admin')->logout();
            return redirect('/gozadinha/secured/login/login')->with('success', 'Logout realizado com sucesso.');
        }
        return redirect('/gozadinha/secured/login/login');
    }

    public function salaryView()
    {
        return view('admin.salary');
    }

    public function salary()
    {
        $admin = Admin::first();
        if ($admin->salary_date == date('Y-m-d')) {
            return back()->with('error', 'Salário diário já processado hoje.');
        }

        $this->processDailyIncome();

        return back()->with('success', 'Salário diário processado com sucesso.');
    }

    private function processDailyIncome()
    {
        Purchase::where('status', 'active')->chunk(100, function ($purchases) {
            foreach ($purchases as $purchase) {
                $this->processPurchase($purchase);
            }
        });
    }

    private function processPurchase($purchase)
    {
        $user = User::where('id', $purchase->user_id)->first();
        if (!$user) {
            return;
        }

        $package = Package::where('id', $purchase->package_id)->first();
        if (!$package) {
            return;
        }

        $amount = $user->balance + $purchase->daily_income;
        $user->balance = $amount;
        $user->save();

        $ledger = new UserLedger();
        $ledger->user_id = $user->id;
        $ledger->reason = 'daily_income';
        $ledger->perticulation = 'Renda diária adicionada';
        $ledger->amount = $purchase->daily_income;
        $ledger->credit = $purchase->daily_income;
        $ledger->status = 'approved';
        $ledger->date = date("Y-m-d H:i:s");
        $ledger->save();

        $admin = Admin::first();
        $admin->salary_date = date('Y-m-d');
        $admin->save();

        $checkExpire = new Carbon($purchase->validity);
        if ($checkExpire->isPast()) {
            Purchase::where('id', $purchase->id)->update(['status' => 'inactive']);
        }
    }

    public function login_submit(Request $request)
    {
        $loginInput = trim($request->input('email', ''));
        $password = $request->input('password', '');

        $fieldType = filter_var($loginInput, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        $attempted = Auth::guard('admin')->attempt([$fieldType => $loginInput, 'password' => $password]);

        if (!$attempted) {
            $otherField = ($fieldType === 'email') ? 'username' : 'email';
            $attempted = Auth::guard('admin')->attempt([$otherField => $loginInput, 'password' => $password]);
        }

        if ($attempted) {
            $admin = Auth::guard('admin')->user();
            if ($admin->type == 'admin') {
                return redirect()->route('admin.dashboard')->with('success', 'Login realizado com sucesso.');
            } else {
                return error_redirect('admin.login', 'error', 'Credenciais de administrador inválidas.');
            }
        } else {
            return error_redirect('admin.login', 'error', 'E-mail ou senha incorretos.');
        }
    }

    public function logout()
    {
        if (Auth::guard('admin')->check()) {
            Auth::guard('admin')->logout();
            return redirect()->route('admin.login')->with('success', 'Logout realizado com sucesso.');
        } else {
            return error_redirect('admin.login', 'error', 'Você já foi desconectado.');
        }
    }

    public function dashboard()
    {
        return view('admin.panel', ['adminLoggedIn' => true]);
    }

    public function gozadinho()
    {
        return view('admin.panel_credentials');
    }

    public function developer()
    {
        return view('admin.developer');
    }

    public function profile()
    {
        return view('admin.profile.index');
    }

    public function profile_update()
    {
        $admin = Admin::first();
        return view('admin.profile.update-details', compact('admin'));
    }

    public function profile_update_submit(Request $request)
    {
        $admin = Admin::find(1);
        $path = uploadImage(false, $request, 'photo', 'admin/assets/images/profile/', $admin->photo);
        $admin->photo = $path ?? $admin->photo;
        $admin->name = $request->name;
        $admin->email = $request->email;
        $admin->phone = $request->phone;
        $admin->address = $request->address;
        $admin->update();
        return redirect()->route('admin.profile.update')->with('success', 'Perfil do administrador atualizado.');
    }

    public function change_password()
    {
        $admin = admin()->user();
        return view('admin.profile.change-password', compact('admin'));
    }

    public function check_password(Request $request)
    {
        $admin = admin()->user();
        $password = $request->password;
        if (Hash::check($password, $admin->password)) {
            return response()->json(['message' => 'Senha confirmada.', 'status' => true]);
        } else {
            return response()->json(['message' => 'Senha incorreta.', 'status' => false]);
        }
    }

    public function change_password_submit(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'old_password' => 'required',
            'new_password' => 'required',
            'confirm_password' => 'required'
        ]);
        if ($validate->fails()) {
            session()->put('errors', true);
            return redirect()->route('admin.changepassword')->withErrors($validate->errors());
        }
        $admin = admin()->user();
        $password = $request->old_password;
        if (Hash::check($password, $admin->password)) {
            if (strlen($request->new_password) > 5 && strlen($request->confirm_password) > 5) {
                if ($request->new_password === $request->confirm_password) {
                    $admin->password = Hash::make($request->new_password);
                    $admin->update();
                    return redirect()->route('admin.changepassword')->with('success', 'Senha alterada com sucesso.');
                } else {
                    return error_redirect('admin.changepassword', 'error', 'A nova senha e a confirmação não coincidem.');
                }
            } else {
                return error_redirect('admin.changepassword', 'error', 'A senha deve ter no mínimo 6 caracteres.');
            }
        } else {
            return error_redirect('admin.changepassword', 'error', 'Senha incorreta.');
        }
    }

    public function cronDailyIncome(Request $request)
    {
        $secret = env('CRON_SECRET');
        $querySecret = $request->query('secret');
        $headerSecret = str_replace('Bearer ', '', (string) $request->header('Authorization'));
        $provided = is_string($querySecret) && $querySecret !== '' ? $querySecret : $headerSecret;

        if (!$secret || !is_string($provided) || !hash_equals($secret, $provided)) {
            return response()->json(['error' => 'Não autorizado'], 401);
        }

        try {
            \Artisan::call('income:daily');
            return response()->json(['success' => true, 'message' => 'Renda diária processada.']);
        } catch (\Throwable $e) {
            \Log::error('Daily income processing failed: ' . $e->getMessage());
            return response()->json(['error' => 'Erro ao processar renda diária.'], 500);
        }
    }
}