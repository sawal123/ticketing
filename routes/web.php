<?php

use App\Http\Controllers\Api\SlideController;
use App\Http\Controllers\Auth\UserLoginController;
use App\Http\Controllers\Auth\UserRegisterController;
use App\Http\Controllers\BarcodeController;
use App\Http\Controllers\BuyTicketController;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Dashboard\addController;
use App\Http\Controllers\Dashboard\CashController;
use App\Http\Controllers\Dashboard\DashboardController;
use App\Http\Controllers\Dashboard\DeleteController;
use App\Http\Controllers\Dashboard\editController;
use App\Http\Controllers\Dashboard\PaymentGatewayController;
use App\Http\Controllers\Dashboard\TController;
use App\Http\Controllers\Dashboard\TransaksiController;
use App\Http\Controllers\landingController;
use App\Http\Controllers\Penyewa\AddController as PenyewaAddController;
use App\Http\Controllers\Penyewa\Auth\LoginController;
use App\Http\Controllers\Penyewa\BeliCash\CashController as BeliCashCashController;
use App\Http\Controllers\Penyewa\DeleteController as PenyewaDelete;
use App\Http\Controllers\Penyewa\EditController as PenyewaEditController;
use App\Http\Controllers\Penyewa\PenyewaController;
use App\Http\Controllers\Penyewa\StaffController;
use App\Http\Controllers\TransactionController;
use App\Livewire\Admin\DashboardDemo;
use App\Livewire\Admin\EmailBlast;
use App\Livewire\Admin\EventDetail;
use App\Livewire\Admin\EventIndex;
use App\Livewire\Admin\PaymentGatewayIndex;
use App\Livewire\Admin\PenarikanIndex;
use App\Livewire\Admin\SettingIndex;
use App\Livewire\Admin\TransaksiIndex;
use App\Livewire\Admin\UserIndex;
use App\Livewire\Admin\TermIndex;
use App\Livewire\Admin\SliderIndex;
use App\Livewire\Admin\ActivityIndex;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// Route::get('/api/slide', [SlideController::class, 'slide']);
// Route::post('/api/callback', [TransactionController::class, 'callback']);

// ============================================================================
// ============================================================================
// ============================================================================

Route::get('/', [landingController::class, 'home'])->name('home');
Route::get('/ticket/{event}', [landingController::class, 'ticket']);

Route::get('/register', \App\Livewire\Auth\Register::class)->name('register');
// Route::post('/registerUser', [UserRegisterController::class, 'create'])->name('register-user');

Route::get('/forgot-password', \App\Livewire\Auth\ForgotPassword::class)->name('forgot');
Route::post('/email', [UserLoginController::class, 'email'])->name('email');
Route::get('/reset-password/{data}', \App\Livewire\Auth\ResetPassword::class)->name('password.reset');
Route::post('/new-password', [UserLoginController::class, 'newPassword']);
use App\Http\Controllers\Auth\GoogleController;

Route::get('auth/google', [GoogleController::class, 'redirectToGoogle'])->name('auth.google');
Route::get('auth/google/callback', [GoogleController::class, 'handleGoogleCallback']);

Route::get('/login', \App\Livewire\Auth\Login::class)->name('login');
// Route::post('/loginUser', [UserLoginController::class, 'loginUser']);

// Route::get('/postEvent/{search?}', [landingController::class, 'cari']);
Route::get('/search/{cari?}/', [landingController::class, 'search']);
Route::get('/cari', [landingController::class, 'cari']);
Route::get('/term', [landingController::class, 'term']);

Route::get('/contact', [landingController::class, 'contact']);

Route::get('/invoice/{uid}', [Controller::class, 'invoice']);

Route::get('/confir/data/{data}', [Controller::class, 'confir']);
Route::post('/confir/success', [Controller::class, 'success']);
// Route::post('/generate-barcode', [BarcodeController::class, 'generateBarcode']);
Route::get('/generate-barcode/{data}', [BarcodeController::class, 'generateBarcode']);

Route::middleware(['auth'])->group(function () {
    Route::get('/profile', [editController::class, 'profile']);
    Route::post('/profile/update-profile', [editController::class, 'editProfile']);
    Route::get('email/notif-email', [Controller::class, 'notif']);
    Route::get('/detail-ticket/{uid}/{user}', [BuyTicketController::class, 'index']);
    Route::post('/checkVoucer', [BuyTicketController::class, 'checkVoucher']);
    Route::post('/closeVoucher', [BuyTicketController::class, 'closeVoucher']);
    Route::post('/checkout', [BuyTicketController::class, 'checkout']);
    Route::get('/transaksi', [landingController::class, 'listTransaksi']);

    Route::post('/paynow', [TransactionController::class, 'paynow']);
    Route::get('/detail-ticket/delete/{uid}/{user_uid}', [DeleteController::class, 'deteleListTransaksi']);
    Route::get('/logout', function () {
        Auth::logout();

        return redirect('/');
    });
    Route::get('/out', function () {
        Auth::logout();

        return redirect('/signin');
    });
});

Route::get('/signin', [PenyewaController::class, 'login'])->name('signIn');
Route::post('/signin/cekLogin', [LoginController::class, 'index'])->name('cekLogin');

Route::prefix('dashboard')
    ->middleware(['roles:penyewa,staff'])
    ->group(function () {
        // =========================================================
        // NEW LIVEWIRE DASHBOARD (PRIMARY)
        // =========================================================
        Route::get('/', \App\Livewire\Dashboard\DemoIndex::class)->name('dashboard');
        Route::get('/event', \App\Livewire\Dashboard\EventIndex::class)->name('dashboard.event');
        Route::get('/event/create', \App\Livewire\Dashboard\EventCreate::class)->name('dashboard.event.create');
        Route::get('/event/edit/{uid}', \App\Livewire\Dashboard\EventCreate::class)->name('dashboard.event.edit');
        Route::get('/event/{uid}', \App\Livewire\Dashboard\EventDetail::class)->name('dashboard.event.detail');
        Route::get('/voucher', \App\Livewire\Dashboard\VoucherIndex::class)->name('dashboard.voucher');
        Route::get('/penarikan', \App\Livewire\Dashboard\PenarikanIndex::class)->name('dashboard.penarikan');
        Route::get('/staff-index', \App\Livewire\Dashboard\StaffIndex::class)->name('dashboard.staff');
        Route::get('/partner', \App\Livewire\Dashboard\PartnerIndex::class)->name('dashboard.partner');
        Route::get('/settings', \App\Livewire\Dashboard\SettingsIndex::class)->name('dashboard.settings');

        // =========================================================
        // LEGACY DASHBOARD (MOVED TO /old)
        // =========================================================
        Route::prefix('old')->group(function() {
            Route::middleware(['roles:penyewa,staff'])->group(function () {
                Route::get('/', [PenyewaController::class, 'index'])->name('dashboard.old');
                Route::get('/transaksi/{uid?}', [PenyewaController::class, 'transaksi'])->name('dashboard.old.transaksi');
                Route::get('/cash/{uid?}', [PenyewaController::class, 'cash'])->name('dashboard.old.cash');
                Route::get('/event/{addEvent?}/{uid?}', [PenyewaController::class, 'event']);
                Route::get('/ubahEvents/{uid}', [PenyewaController::class, 'ubahEvents']);
                Route::get('/voucher', [PenyewaController::class, 'voucher']);
                Route::get('/staff/delete/{uid}', [StaffController::class, 'destroy']);
                Route::resource('staff', StaffController::class);
                Route::post('/event/toggle-status/{uid}', [PenyewaController::class, 'toggleStatusEvent']);
                Route::post('/hargas/toggle-status/{id}', [PenyewaController::class, 'toggleStatusHarga']);
                Route::post('/updatePassword', [PenyewaEditController::class, 'updatePassword']);
            });

            Route::middleware(['roles:penyewa'])->group(function () {
                Route::get('/money', [PenyewaController::class, 'money']);
                Route::get('/profile', [PenyewaController::class, 'profile']);
                Route::post('/addPenarikan', [PenyewaAddController::class, 'addPenarikan']);
                Route::post('/editRekening', [PenyewaEditController::class, 'editRekening']);
                Route::post('/editProfile', [PenyewaEditController::class, 'editProfile']);
                Route::get('/events/delete/{id}', [PenyewaDelete::class, 'eventDelete']);
                Route::get('/delete/{id}', [PenyewaDelete::class, 'deleteTalent']);
                Route::get('/hargas/delete/{id}', [PenyewaDelete::class, 'deleteHarga']);
                Route::get('/delete/voucher/{id}', [PenyewaDelete::class, 'deleteVoucher']);
                Route::get('/delete/partner/{id}', [PenyewaDelete::class, 'deletePartner']);
                Route::post('/addEvents', [PenyewaAddController::class, 'addEvent'])->name('dashboard.old.addEvent');
                Route::post('/addTalent', [PenyewaAddController::class, 'addTalent']);
                Route::post('/addHarga', [PenyewaAddController::class, 'addHarga']);
                Route::post('/addVoucher', [PenyewaAddController::class, 'addVoucher']);
                Route::post('/addCash', [BeliCashCashController::class, 'createCash'])->name('old.add.cash');
                Route::post('/addPartner', [PenyewaAddController::class, 'addPartner']);
                Route::post('/editTalent', [PenyewaEditController::class, 'editTalent']);
                Route::post('/editEventPenyewa', [PenyewaEditController::class, 'editEventPenyewa']);
                Route::post('/editEvent', [PenyewaEditController::class, 'editEvent']);
                Route::post('/editHarga', [PenyewaEditController::class, 'editHarga']);
                Route::post('/editPartner', [PenyewaEditController::class, 'editPartner']);
                Route::post('/updateVoucher', [PenyewaEditController::class, 'editVoucher']);
            });
        });
    });

Route::get('/staff/verify/{uid}', \App\Livewire\Auth\StaffVerify::class)->name('staff.verify');
// Route::post('/staff/complete-profile/{uid}', [StaffController::class, 'completeProfile']);

Route::prefix('admin')
    ->middleware(['auth', 'admin'])
    ->group(function () {
        // =========================================================
        // NEW LIVEWIRE ADMIN (PRIMARY)
        // =========================================================
        Route::get('/', DashboardDemo::class)->name('admin');
        Route::get('/event', EventIndex::class)->name('admin.event');
        Route::get('/event/{uid}', EventDetail::class)->name('admin.event.detail');
        Route::get('/transaksi', TransaksiIndex::class)->name('admin.transaksi');
        Route::get('/penarikan', PenarikanIndex::class)->name('admin.penarikan');
        Route::get('/payment-gateway', PaymentGatewayIndex::class)->name('admin.payments');
        Route::get('/setting', SettingIndex::class)->name('admin.setting');
        Route::get('/term', TermIndex::class)->name('admin.term');
        Route::get('/slider', SliderIndex::class)->name('admin.slider');
        Route::get('/activity', ActivityIndex::class)->name('admin.activity');
        Route::get('/user', UserIndex::class)->name('admin.user');
        Route::get('/category', \App\Livewire\Admin\CategoryIndex::class)->name('admin.category');
        Route::get('/fasilitas', \App\Livewire\Admin\FasilitasIndex::class)->name('admin.fasilitas');
        Route::get('/email-blast', EmailBlast::class)->name('admin.email-blast');

        // =========================================================
        // LEGACY ADMIN (MOVED TO /old)
        // =========================================================
        Route::prefix('old')
            ->namespace('Dashboard')
            ->group(function () {
                Route::get('/', [DashboardController::class, 'dashboard']);
                Route::get('/search', [DashboardController::class, 'event']);
                Route::get('/transaksi/{uid?}', [TransaksiController::class, 'transaksi']);
                Route::get('/cash/{uid?}', [CashController::class, 'cash']);
                Route::get('/editCash', [CashController::class, 'editCash'])->name('AEditCash');
                Route::get('t/online/{uid?}', [TController::class, 'tonline'])->name('tonline');
                Route::get('/user/{data?}', [DashboardController::class, 'user']);
                Route::get('/event/{addEvent?}/{uid?}', [DashboardController::class, 'event']);
                Route::get('/ubahEvents/{uid}', [DashboardController::class, 'ubahEvents']);
                Route::get('/penarikan', [DashboardController::class, 'penarikan']);
                Route::get('/setting/slide', [DashboardController::class, 'landing']);
                Route::get('/setting/seo', [DashboardController::class, 'seo']);
                Route::get('/setting/term', [DashboardController::class, 'term']);
                Route::get('/profile', [DashboardController::class, 'profile']);
                Route::post('/addEvents', [addController::class, 'addEvent']);
                Route::post('/addTalent', [addController::class, 'addTalent']);
                Route::post('/addHarga', [addController::class, 'addHarga']);
                Route::post('/addSlide', [addController::class, 'addSlide']);
                Route::post('/addTerm', [addController::class, 'addTerm']);
                Route::post('/addAdmin', [addController::class, 'addAdmin']);
                Route::post('/addContact', [addController::class, 'addContact']);
                Route::post('/editTalent', [editController::class, 'editTalent']);
                Route::post('/editEvent', [editController::class, 'editEvent']);
                Route::post('/editHarga', [editController::class, 'editHarga']);
                Route::post('/editSlide', [editController::class, 'editSlide']);
                Route::post('/editTerm', [editController::class, 'editTerm']);
                Route::post('/user/editUser', [editController::class, 'editUser']);
                Route::post('/user/editCashes', [editController::class, 'editCashes']);
                Route::get('/setujuiEvent/{data}', [editController::class, 'setujuiEvent']);
                Route::post('/editPenarikan', [editController::class, 'editStatusInvoice']);
                Route::post('/editContact', [editController::class, 'editContact']);
                Route::post('/editLogo', [editController::class, 'editLogo']);
                Route::post('/editIcon', [editController::class, 'editIcon']);
                Route::post('/edit/seoDeskripsi', [editController::class, 'editDeskripis']);
                Route::post('/edit/seoKeyword', [editController::class, 'editKeyword']);
                Route::post('/editTransaksi', [editController::class, 'editTransaksi']);
                Route::post('/editPro', [editController::class, 'editPro']);
                Route::post('/editRekening', [editController::class, 'editRekening']);
                Route::get('/delete/{id}', [DeleteController::class, 'deleteTalent']);
                Route::get('/deleteTransksi/{uid}', [DeleteController::class, 'deleteTransaksi']);
                Route::get('/landing/delete/{uid}', [DeleteController::class, 'deleteSlide']);
                Route::get('/events/delete/{uid}', [DeleteController::class, 'deleteEvent']);
                Route::get('/hargas/delete/{id}', [DeleteController::class, 'deleteHarga']);
                Route::get('/term/delete/{id}', [DeleteController::class, 'deleteTerm']);
                Route::get('/user/delete/{id}', [DeleteController::class, 'deleteUser']);
                Route::get('/cashes/delete/{id}', [DeleteController::class, 'deleteCashes']);
                Route::get('/deletePen/{data}', [DeleteController::class, 'deletePenarikan']);
                Route::get('/delete/contact/{data}', [DeleteController::class, 'deleteContact']);
                Route::get('/payment-gateway', [PaymentGatewayController::class, 'index'])->name('old.payments');
                Route::post('/payment-gateway/store', [PaymentGatewayController::class, 'store'])->name('old.payments.store');
                Route::post('/payment-gateway/update/{paymentGateway}', [PaymentGatewayController::class, 'update'])->name('old.payments.update');
                Route::delete('/payment-gateway/delete/{paymentGateway}', [PaymentGatewayController::class, 'destroy'])->name('old.payments.destroy');
            });
    });

// ====================
