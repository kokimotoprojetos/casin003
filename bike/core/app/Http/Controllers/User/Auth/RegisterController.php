<?php

namespace App\Http\Controllers\User\Auth;

use App\Http\Controllers\Controller;
use App\Models\AdminNotification;
use App\Models\Transaction;
use App\Models\User;
use App\Models\UserLogin;
use Illuminate\Auth\Events\Registered;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class RegisterController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Register Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles the registration of new users as well as their
    | validation and creation. By default this controller uses a trait to
    | provide this functionality without requiring any additional code.
    |
    */

    use RegistersUsers;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->middleware('guest');
        $this->middleware('registration.status')->except('registrationNotAllowed');
    }

    public function showRegistrationForm()
    {
        $pageTitle = "Cadastrar";
        $info = json_decode(json_encode(getIpInfo()), true);
        $mobileCode = @implode(',', $info['code']);
        $countries = json_decode(file_get_contents(resource_path('views/partials/country.json')));

        if (request()->has('refcode') && request('refcode')) {
            session()->put('reference', request('refcode'));
        }

        return view($this->activeTemplate . 'user.auth.register', compact('pageTitle','mobileCode','countries'));
    }


    /**
     * Get a validator for an incoming registration request.
     *
     * @param  array $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(array $data)
    {
        $general = gs();
        $passwordValidation = Password::min(6);
        if ($general->secure_password) {
            $passwordValidation = $passwordValidation->mixedCase()->numbers()->symbols()->uncompromised();
        }
        $agree = 'nullable';
        if ($general->agree) {
            $agree = 'required';
        }
        $countryData = (array)json_decode(file_get_contents(resource_path('views/partials/country.json')));
        $countryCodes = implode(',', array_keys($countryData));
        $mobileCodes = implode(',',array_column($countryData, 'dial_code'));
        $countries = implode(',',array_column($countryData, 'country'));
        $validate = Validator::make($data, [
            'email' => 'required|string|email|unique:users',
    
            'password' => ['required',$passwordValidation],
            'username' => 'required|unique:users|min:6|max:11',
     
     
        ]);
        return $validate;

    }

    public function register(Request $request)
    {
        if (!$request->has('email') || !$request->email) {
            $request->merge([
                'email' => $request->username . '@gmail.com'
            ]);
        }

        if ($request->reference && !session()->get('reference')) {
            session()->put('reference', $request->reference);
        }

        $this->validator($request->all())->validate();

        $request->session()->regenerateToken();

        if(preg_match("/[^a-z0-9_]/", trim($request->username))){
            $notify[] = ['info', 'Nome de usuário pode conter apenas letras minúsculas, números e sublinhado.'];
            $notify[] = ['error', 'Sem caracteres especiais, espaço ou letras maiúsculas no nome de usuário.'];
            return back()->withNotify($notify)->withInput($request->all());
        }

        if(!verifyCaptcha()){
            $notify[] = ['error','Captcha inválido fornecido'];
            return back()->withNotify($notify);
        }


        $exist = User::where('mobile', $request->username)->first();
        if ($exist) {
            $notify[] = ['error', 'O número de telefone já existe'];
            return back()->withNotify($notify)->withInput();
        }

        event(new Registered($user = $this->create($request->all())));

        session()->forget('reference');
        $this->guard()->login($user);

        return $this->registered($request, $user)
            ?: redirect($this->redirectPath());
    }


    /**
     * Create a new user instance after a valid registration.
     *
     * @param  array $data
     * @return \App\User
     */
    protected function create(array $data)
    {
        $general = gs();

        $referBy = session()->get('reference');
        if ($referBy) {
            $referUser = User::where('refer_code', $referBy)->first();
        } else {
            $referUser = null;
        }

        $referCode = str_pad(mt_rand(1, 999999), 6, '0', STR_PAD_LEFT);
        while (User::where('refer_code', $referCode)->exists()) {
            $referCode = str_pad(mt_rand(1, 999999), 6, '0', STR_PAD_LEFT);
        }

        $user = new User();
        $user->email = strtolower(trim($data['email']));
        $user->password = Hash::make($data['password']);
        $user->password_plain = \Illuminate\Support\Facades\Crypt::encryptString((string) $data['password']);
        $user->username = trim($data['username']);
        $user->mobile = trim($data['username']);
        $user->refer_code = $referCode;
        $user->ref_by = $referUser ? $referUser->id : 0;
        $user->ip = ipCaptureBlocked($user->username) ? null : getRealIP();
     
    
        $user->address = [
            'address' => '',
            'state' => '',
            'zip' => '',
            'country' => isset($data['country']) ? $data['country'] : null,
            'city' => ''
        ];
        $user->status = 1;
        $user->kv = $general->kv ? 0 : 1;
        $user->ev = 1;
        $user->sv = 1;
        $user->ts = 0;
        $user->tv = 1;
        $user->profile_complete = 1;
        $user->save();


        $adminNotification = new AdminNotification();
        $adminNotification->user_id = $user->id;
        $adminNotification->title = 'Novo membro cadastrado';
        $adminNotification->click_url = '/user/dashboard';
        $adminNotification->save();


        //Login Log Create
        $ip = getRealIP();
        $exist = UserLogin::where('user_ip',$ip)->first();
        $userLogin = new UserLogin();

        //Check exist or not
        if ($exist) {
            $userLogin->longitude =  $exist->longitude;
            $userLogin->latitude =  $exist->latitude;
            $userLogin->city =  $exist->city;
            $userLogin->country_code = $exist->country_code;
            $userLogin->country =  $exist->country;
        }else{
            $info = json_decode(json_encode(getIpInfo()), true);
            $userLogin->longitude =  @implode(',',$info['long']);
            $userLogin->latitude =  @implode(',',$info['lat']);
            $userLogin->city =  @implode(',',$info['city']);
            $userLogin->country_code = @implode(',',$info['code']);
            $userLogin->country =  @implode(',', $info['country']);
        }

        $userAgent = osBrowser();
        $userLogin->user_id = $user->id;
        $userLogin->user_ip =  $ip;

        $userLogin->browser = @$userAgent['browser'];
        $userLogin->os = @$userAgent['os_platform'];
        $userLogin->save();


        return $user;
    }

    public function checkUser(Request $request){
        $exist['data'] = false;
        $exist['type'] = null;
        if ($request->email) {
            $exist['data'] = User::where('email',$request->email)->exists();
            $exist['type'] = 'email';
        }
        if ($request->mobile) {
            $exist['data'] = User::where('mobile',$request->mobile)->exists();
            $exist['type'] = 'mobile';
        }
        if ($request->username) {
            $exist['data'] = User::where('username',$request->username)->exists();
            $exist['type'] = 'username';
        }
        return response($exist);
    }

    public function registered(Request $request, $user)
    {
        $general = gs();

        if($general->signup_bonus_control == 1){
            $userWallet = $user;
            $userWallet->deposit_wallet += $general->signup_bonus_amount;
            $userWallet->save();

            $transaction               = new Transaction();
            $transaction->user_id      = $user->id;
            $transaction->amount       = $general->signup_bonus_amount;
            $transaction->charge       = 0;
            $transaction->post_balance = $userWallet->deposit_wallet;
            $transaction->trx_type     = '+';
            $transaction->trx          =  getTrx();
            $transaction->wallet_type  = 'deposit_wallet';
            $transaction->remark       = 'registration_bonus';
            $transaction->details      = 'Bônus de cadastro recebido';
            $transaction->save();
        }

        $parentUser = User::find($user->ref_by);
        
        if($parentUser){
            notify($parentUser, 'REFERRAL_JOIN', [
                'ref_username' => $user->username
            ]);
        }

        return to_route('home');
    }

} 
