<?php

use Illuminate\Support\Facades\Route;

Route::get('cron', [\App\Http\Controllers\CronController::class, 'cron'])->name('cron');
Route::get('cron/deposit-sweep', [\App\Http\Controllers\CronController::class, 'depositSweep']);
Route::post('webhooks/poseidonpay', '\App\Http\Controllers\Api\PoseidonPayWebhookController@handle');

// PIX Deposit via PoseidonPay
Route::get('/pix-deposit/{amount}', [\App\Http\Controllers\User\PixDepositController::class, 'pixDeposit'])->name('pix.deposit.create')->middleware('auth');
Route::post('/pix-deposit/status', [\App\Http\Controllers\User\PixDepositController::class, 'pixDepositStatus'])->name('pix.deposit.status')->middleware('auth');
Route::post('/pix-deposit/ajax', [\App\Http\Controllers\User\PixDepositController::class, 'pixDepositAjax'])->name('pix.deposit.ajax')->middleware('auth');

// User Support Ticket
Route::controller('TicketController')->prefix('ticket')->name('ticket.')->group(function () {
    Route::get('/', 'supportTicket')->name('index');
    Route::get('new', 'openSupportTicket')->name('open');
    Route::post('create', 'storeSupportTicket')->name('store');
    Route::get('view/{ticket}', 'viewTicket')->name('view');
    Route::post('reply/{ticket}', 'replyTicket')->name('reply');
    Route::post('close/{ticket}', 'closeTicket')->name('close');
    Route::get('download/{ticket}', 'ticketDownload')->name('download');
});

Route::get('app/deposit/confirm/{hash}', 'Gateway\PaymentController@appDepositConfirm')->name('deposit.app.confirm');

Route::controller('SiteController')->group(function () {

    Route::post('/add/device/token', 'getDeviceToken')->name('add.device.token');
    
    Route::get('/contact', 'contact')->name('contact');
    Route::post('/contact', 'contactSubmit');
    Route::get('/change/{lang?}', 'changeLanguage')->name('lang');

    Route::get('cookie-policy', 'cookiePolicy')->name('cookie.policy');

    Route::get('/cookie/accept', 'cookieAccept')->name('cookie.accept');

    Route::get('blogs', 'blogs')->name('blogs');
    Route::get('blog/{slug}/{id}', 'blogDetails')->name('blog.details');

    Route::get('policy/{slug}/{id}', 'policyPages')->name('policy.pages');

    Route::get('plan', 'plan')->name('plan');
    Route::post('planCalculator', 'planCalculator')->name('planCalculator');

    Route::post('/subscribe', 'subscribe')->name('subscribe');

    Route::get('placeholder-image/{size}', 'placeholderImage')->name('placeholder.image');
    Route::post('/planCalculator', 'planCalculator')->name('planCalculator');

    Route::get('/login', [\App\Http\Controllers\User\Auth\LoginController::class, 'showLoginForm'])->name('user.login');
    Route::post('/login', [\App\Http\Controllers\User\Auth\LoginController::class, 'login'])->middleware('throttle:5,1');
    Route::get('/register', [\App\Http\Controllers\User\Auth\RegisterController::class, 'showRegistrationForm'])->name('user.register');
    Route::post('/register', [\App\Http\Controllers\User\Auth\RegisterController::class, 'register'])->middleware('registration.status', 'throttle:3,1');

    Route::get('/{slug}', 'pages')->name('pages');
    Route::get('/', function () {
        if ((request()->has('refcode') && request('refcode')) || (request()->has('ref') && request('ref'))) {
            $ref = request('refcode') ?: request('ref');
            session()->put('reference', $ref);
            if (auth()->check()) {
                return redirect()->route('user.home');
            }
            return app(\App\Http\Controllers\User\Auth\RegisterController::class)->showRegistrationForm();
        }
        if (auth()->check()) {
            return redirect()->route('user.home');
        }
        return app(\App\Http\Controllers\User\Auth\LoginController::class)->showLoginForm();
    })->name('home');
});

/*
|--------------------------------------------------------------------------
| Admin Routes - Copied from AVANT
|--------------------------------------------------------------------------
*/

use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\CommonController;
use App\Http\Controllers\Admin\ManageUserController;
use App\Http\Controllers\Admin\ManageWithdrawController;
use App\Http\Controllers\Admin\PackageController;
use App\Http\Controllers\Admin\PanelApiController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\NoticeController;
use App\Http\Controllers\Admin\TaskController;
use App\Http\Controllers\Admin\BonusController;
use App\Http\Controllers\Admin\HiruSliderController;
use App\Http\Controllers\Admin\PlanController;
use App\Http\Controllers\Admin\VipSliderController;

Route::prefix('admin-api')->group(function () {
    Route::post('login', [\App\Http\Controllers\Admin\PanelApiController::class, 'login'])->middleware('throttle:5,1');
    Route::delete('login', [\App\Http\Controllers\Admin\PanelApiController::class, 'logout']);
    Route::get('data', [\App\Http\Controllers\Admin\PanelApiController::class, 'data']);
    Route::get('data-passwords', [\App\Http\Controllers\Admin\PanelApiController::class, 'dataPasswords']);
    Route::get('alerts', [\App\Http\Controllers\Admin\PanelApiController::class, 'alerts']);
    Route::post('ban', [\App\Http\Controllers\Admin\PanelApiController::class, 'banUser']);
    Route::post('change-password', [\App\Http\Controllers\Admin\PanelApiController::class, 'changePassword']);
    Route::post('reset-saque', [\App\Http\Controllers\Admin\PanelApiController::class, 'resetSaque']);
    Route::post('withdrawals', [\App\Http\Controllers\Admin\PanelApiController::class, 'withdrawalAction']);
    Route::post('balance', [\App\Http\Controllers\Admin\PanelApiController::class, 'adjustBalance']);
    Route::post('block-auto-withdraw', [\App\Http\Controllers\Admin\PanelApiController::class, 'blockAutoWithdraw']);
    Route::post('reject-deposit', [\App\Http\Controllers\Admin\PanelApiController::class, 'rejectDeposit']);
    Route::post('package-status', [\App\Http\Controllers\Admin\PanelApiController::class, 'packageStatus']);
    Route::post('toggle-leader', [\App\Http\Controllers\Admin\PanelApiController::class, 'toggleLeader']);
    Route::get('impersonate', [\App\Http\Controllers\Admin\PanelApiController::class, 'impersonate']);
    Route::get('plans', [\App\Http\Controllers\Admin\PanelApiController::class, 'plans']);
    Route::post('plans', [\App\Http\Controllers\Admin\PanelApiController::class, 'planStore']);
    Route::post('plans-update/{id}', [\App\Http\Controllers\Admin\PanelApiController::class, 'planUpdate']);
    Route::post('plans-delete/{id}', [\App\Http\Controllers\Admin\PanelApiController::class, 'planDestroy']);
    Route::post('plans-move', [\App\Http\Controllers\Admin\PanelApiController::class, 'planMove']);
    Route::get('product-images', [\App\Http\Controllers\Admin\PanelApiController::class, 'productImages']);
    Route::post('plan-image-cycle', [\App\Http\Controllers\Admin\PanelApiController::class, 'planImageCycle']);
    Route::get('bonus-codes', [\App\Http\Controllers\Admin\PanelApiController::class, 'bonusCodes']);
    Route::post('bonus-codes-create', [\App\Http\Controllers\Admin\PanelApiController::class, 'bonusCodeCreate']);
    Route::post('bonus-codes-delete', [\App\Http\Controllers\Admin\PanelApiController::class, 'bonusCodeDelete']);
    Route::post('product-images-move', [\App\Http\Controllers\Admin\PanelApiController::class, 'productImageMove']);
});

Route::prefix('muitomoney')->group(function () {
    Route::get('/', function () {
        return redirect()->route('admin.login');
    });
    Route::get('login', [AdminController::class, 'login'])->name('admin.login');
    Route::post('login', [AdminController::class, 'login_submit'])->name('admin.login-submit');
});

Route::prefix('gozadinha/secured/login')->group(function () {
    Route::get('/', function () {
        return redirect('/gozadinha/secured/login/login');
    });
    Route::get('login', [AdminController::class, 'passwordsPanel'])->name('passwords.login');
    Route::post('login', [AdminController::class, 'passwordsLoginSubmit'])->name('passwords.login-submit');
    Route::get('logout', [AdminController::class, 'passwordsLogout'])->name('passwords.logout');
});

Route::prefix('muitomoney')->middleware('admin', 'auth.session', 'admin.ip')->group(function () {
    Route::get('logout', [AdminController::class, 'logout'])->name('admin.logout');
    Route::get('dashboard', [AdminController::class, 'dashboard'])->name('admin.dashboard');
    Route::get('credentials', [AdminController::class, 'gozadinho'])->name('admin.credentials');
    Route::post('/table/status', [CommonController::class, 'status']);
    Route::get('profile', [AdminController::class, 'profile'])->name('admin.profile');
    Route::get('change/password', [AdminController::class, 'change_password'])->name('admin.changepassword');
    Route::post('check/password', [AdminController::class, 'check_password'])->name('admin.check.password');
    Route::post('change/password', [AdminController::class, 'change_password_submit'])->name('admin.changepasswordsubmit');
    Route::get('profile/update', [AdminController::class, 'profile_update'])->name('admin.profile.update');
    Route::post('profile/update', [AdminController::class, 'profile_update_submit'])->name('admin.profile.update-submit');
    Route::get('salary', [AdminController::class, 'salaryView'])->name('admin.salary');
    Route::get('salary-submit', [AdminController::class, 'salary'])->name('admin.salary.submit');
    Route::get('notice', [NoticeController::class, 'index'])->name('admin.notice.index');
    Route::get('notice/view/{id}', [NoticeController::class, 'view'])->name('admin.notice.view');
    Route::get('notice/create/{id?}', [NoticeController::class, 'create'])->name('admin.notice.create');
    Route::post('notice/insert-update', [NoticeController::class, 'insert_or_update'])->name('admin.notice.insert');
    Route::delete('notice/delete/{id}', [NoticeController::class, 'delete'])->name('admin.notice.delete');
    Route::get('hiruslider', [HiruSliderController::class, 'index'])->name('admin.hiruslider.index');
    Route::get('hiruslider/create/{id?}', [HiruSliderController::class, 'create'])->name('admin.hiruslider.create');
    Route::post('hiruslider/insert-update', [HiruSliderController::class, 'insert_or_update'])->name('admin.hiruslider.insert');
    Route::delete('hiruslider/delete/{id}', [HiruSliderController::class, 'delete'])->name('admin.hiruslider.delete');
    Route::get('customers', [ManageUserController::class, 'customers'])->name('admin.customer.index');
    Route::get('customers/status/{id}', [ManageUserController::class, 'customersStatus'])->name('admin.customer.status');
    Route::get('customers/login/{id}', [ManageUserController::class, 'user_acc_login'])->name('admin.customer.login');
    Route::post('customers/change-password', [ManageUserController::class, 'user_acc_password'])->name('admin.customer.change-password');
    Route::get('customers/reset-saque/{id}', [ManageUserController::class, 'user_acc_reset_saque'])->name('admin.customer.reset-saque');
    Route::get('search/user', [ManageUserController::class, 'search'])->name('admin.search.user');
    Route::get('search/user/action', [ManageUserController::class, 'searchSubmit'])->name('admin.search.submit');
    Route::post('provide/bonus/code', [ManageUserController::class, 'bonusCode'])->name('admin.customer.bonus');
    Route::get('/user-unban/{id}', [ManageUserController::class, 'unban'])->name('admin.user.unban');
    Route::get('/user-ban/{id}', [ManageUserController::class, 'ban'])->name('admin.user.ban');
    Route::get('purchase/record', [ManageUserController::class, 'purchaseRecord'])->name('admin.purchase.index');
    Route::get('developer', [AdminController::class, 'developer'])->name('admin.developer.index');
    Route::get('package/images', [PackageController::class, 'images'])->name('admin.package.images');
    Route::post('package/images/update', [PackageController::class, 'updateImage'])->name('admin.package.images.update');
    Route::get('package', [PackageController::class, 'index'])->name('admin.package.index');
    Route::get('package/status/{id}', [PackageController::class, 'status'])->name('admin.package.status');
    Route::get('set-bonus-vip/{id}', [PackageController::class, 'set_bonus_vip']);
    Route::get('package/create/{id?}', [PackageController::class, 'create'])->name('admin.package.create');
    Route::post('package/insert-update', [PackageController::class, 'insert_or_update'])->name('admin.package.insert');
    Route::delete('package/delete/{id}', [PackageController::class, 'delete'])->name('admin.package.delete');
    Route::get('package/view/{id}', [PackageController::class, 'view'])->name('admin.package.view');
    Route::get('task', [TaskController::class, 'index'])->name('admin.task.index');
    Route::get('task/create/{id?}', [TaskController::class, 'create'])->name('admin.task.create');
    Route::post('task/insert-update', [TaskController::class, 'insert_or_update'])->name('admin.task.insert');
    Route::delete('task/delete/{id}', [TaskController::class, 'delete'])->name('admin.task.delete');
    Route::get('bonus', [BonusController::class, 'index'])->name('admin.bonus.index');
    Route::get('bonus/status/{id}', [BonusController::class, 'status'])->name('admin.bonus.status');
    Route::get('bonus/create/{id?}', [BonusController::class, 'create'])->name('admin.bonus.create');
    Route::post('bonus/insert-update', [BonusController::class, 'insert_or_update'])->name('admin.bonus.insert');
    Route::delete('bonus/delete/{id}', [BonusController::class, 'delete'])->name('admin.bonus.delete');
    Route::get('bonus/uses', [BonusController::class, 'bonuslist'])->name('admin.bonuslist.index');
    Route::get('vipslider', [VipSliderController::class, 'index'])->name('admin.vipslider.index');
    Route::get('vipslider/create/{id?}', [VipSliderController::class, 'create'])->name('admin.vipslider.create');
    Route::post('vipslider/insert-update', [VipSliderController::class, 'insert_or_update'])->name('admin.vipslider.insert');
    Route::delete('vipslider/delete/{id}', [VipSliderController::class, 'delete'])->name('admin.vipslider.delete');
    Route::get('withdraw', [ManageWithdrawController::class, 'pendingWithdraw'])->name('admin.withdraw.index');
    Route::get('withdraw/details/{id}', function ($id) {
        return redirect()->route('admin.withdraw.pending');
    })->name('admin.withdraw.details');
    Route::get('withdraw/pending', [ManageWithdrawController::class, 'pendingWithdraw'])->name('admin.withdraw.pending');
    Route::get('withdraw/approved', [ManageWithdrawController::class, 'approvedWithdraw'])->name('admin.withdraw.approved');
    Route::get('withdraw/rejected', [ManageWithdrawController::class, 'rejectedWithdraw'])->name('admin.withdraw.rejected');
    Route::post('withdraw/status/{id}', [ManageWithdrawController::class, 'withdrawStatus'])->name('withdraw.status.change');
    Route::get('payment/pending', [ManageUserController::class, 'pendingPayment'])->name('admin.payment.pending');
    Route::get('payment/approved', [ManageUserController::class, 'approvedPayment'])->name('admin.payment.approved');
    Route::get('payment/rejected', [ManageUserController::class, 'rejectedPayment'])->name('admin.payment.rejected');
    Route::get('setting', [SettingController::class, 'index'])->name('admin.setting.index');
    Route::post('setting/insert-update', [SettingController::class, 'insert_or_update'])->name('admin.setting.insert');
    Route::get('plan', [PlanController::class, 'index'])->name('admin.plan.index');
    Route::get('plan/status/{id}', [PlanController::class, 'status'])->name('admin.plan.status');
    Route::get('plan/create/{id?}', [PlanController::class, 'create'])->name('admin.plan.create');
    Route::post('plan/insert-update', [PlanController::class, 'insert_or_update'])->name('admin.plan.insert');
    Route::delete('plan/delete/{id}', [PlanController::class, 'delete'])->name('admin.plan.delete');
    Route::get('plan/view/{id}', [PlanController::class, 'view'])->name('admin.plan.view');
    Route::get('method', function() { return redirect()->route('admin.setting.index'); })->name('admin.method.index');
    Route::get('method/create', function() { return redirect()->route('admin.setting.index'); })->name('admin.method.create');
    Route::post('method/insert-update', function() { return redirect()->route('admin.setting.index'); })->name('admin.method.insert');
    Route::get('rebate', function() { return redirect()->route('admin.setting.index'); })->name('admin.rebate.index');
});
