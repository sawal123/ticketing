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
use App\Livewire\Admin\EventIndex;
use App\Livewire\Admin\EventDetail;
use App\Livewire\Admin\TransaksiIndex;
use App\Livewire\Admin\PenarikanIndex;
use App\Livewire\Admin\PaymentGatewayIndex;
use App\Livewire\Admin\SettingIndex;
use App\Livewire\Admin\UserIndex;
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

Route::get('/register', [UserRegisterController::class, 'index']);
Route::post('/registerUser', [UserRegisterController::class, 'create'])->name('register-user');

Route::get('/forgot-password', [UserLoginController::class, 'forgot'])->name('forgot');
Route::post('/email', [UserLoginController::class, 'email'])->name('email');
Route::get('/reset-password/{data}', [UserLoginController::class, 'resetPassword']);
Route::post('/new-password', [UserLoginController::class, 'newPassword']);
Route::get('/login', [UserLoginController::class, 'signIn'])->name('login');
Route::post('/loginUser', [UserLoginController::class, 'loginUser']);

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
    ->middleware(['auth']) // Pastikan login dulu
    ->group(function () {

        // =========================================================
        // 1. AKSES BERSAMA (PENYEWA & STAFF)
        // =========================================================
        Route::middleware(['roles:penyewa,staff'])->group(function () {
            // View Operasional
            Route::get('/', [PenyewaController::class, 'index'])->name('dashboard');
            Route::get('/transaksi/{uid?}', [PenyewaController::class, 'transaksi'])->name('dashboard.transaksi');
            Route::get('/cash/{uid?}', [PenyewaController::class, 'cash'])->name('dashboard.cash');
            Route::get('/event/{addEvent?}/{uid?}', [PenyewaController::class, 'event']);
            Route::get('/ubahEvents/{uid}', [PenyewaController::class, 'ubahEvents']);
            Route::get('/voucher', [PenyewaController::class, 'voucher']);
            Route::get('/partner', [PenyewaController::class, 'partner']);

            Route::get('/staff/delete/{uid}', [StaffController::class, 'destroy']);
            Route::resource('staff', StaffController::class);
            Route::post('/hargas/toggle-status/{id}', [PenyewaController::class, 'toggleStatusHarga']);
            Route::post('/updatePassword', [PenyewaEditController::class, 'updatePassword']);
        });

        // =========================================================
        // 2. KHUSUS PENYEWA (OWNER) - Staff dilarang masuk
        // =========================================================
        Route::middleware(['roles:penyewa'])->group(function () {
            // Keuangan & Profil Utama
            Route::get('/money', [PenyewaController::class, 'money']);
            Route::get('/profile', [PenyewaController::class, 'profile']);

            // Post Sensitif (Uang & Rekening)
            Route::post('/addPenarikan', [PenyewaAddController::class, 'addPenarikan']);
            Route::post('/editRekening', [PenyewaEditController::class, 'editRekening']);
            Route::post('/editProfile', [PenyewaEditController::class, 'editProfile']);

            // Fitur Hapus (Hanya Owner yang boleh menghapus)
            Route::get('/events/delete/{id}', [PenyewaDelete::class, 'eventDelete']);
            Route::get('/delete/{id}', [PenyewaDelete::class, 'deleteTalent']);
            Route::get('/hargas/delete/{id}', [PenyewaDelete::class, 'deleteHarga']);
            Route::get('/delete/voucher/{id}', [PenyewaDelete::class, 'deleteVoucher']);
            Route::get('/delete/partner/{id}', [PenyewaDelete::class, 'deletePartner']);

            // Manajemen Staff

            // Post Operasional (Input Data)
            Route::post('/addEvents', [PenyewaAddController::class, 'addEvent'])->name('dashboard.addEvent');
            Route::post('/addTalent', [PenyewaAddController::class, 'addTalent']);
            Route::post('/addHarga', [PenyewaAddController::class, 'addHarga']);
            Route::post('/addVoucher', [PenyewaAddController::class, 'addVoucher']);
            Route::post('/addCash', [BeliCashCashController::class, 'createCash'])->name('add.cash');
            Route::post('/addPartner', [PenyewaAddController::class, 'addPartner']);

            // Edit Data Operasional
            Route::post('/editTalent', [PenyewaEditController::class, 'editTalent']);
            Route::post('/editEventPenyewa', [PenyewaEditController::class, 'editEventPenyewa']);
            Route::post('/editEvent', [PenyewaEditController::class, 'editEvent']);
            Route::post('/editHarga', [PenyewaEditController::class, 'editHarga']);
            Route::post('/editPartner', [PenyewaEditController::class, 'editPartner']);
            Route::post('/updateVoucher', [PenyewaEditController::class, 'editVoucher']);
        });
    });

Route::get('/staff/verify/{uid}', [StaffController::class, 'verify'])->name('staff.verify');
Route::post('/staff/complete-profile/{uid}', [StaffController::class, 'completeProfile']);

Route::get('admin/demo', DashboardDemo::class)->middleware(['auth', 'admin'])->name('admin.demo');
Route::get('admin/demo/event', EventIndex::class)->middleware(['auth', 'admin'])->name('admin.event');
Route::get('admin/demo/event/{uid}', EventDetail::class)->middleware(['auth', 'admin'])->name('admin.event.detail');
Route::get('admin/demo/transaksi', TransaksiIndex::class)->middleware(['auth', 'admin'])->name('admin.transaksi');
Route::get('admin/demo/penarikan', PenarikanIndex::class)->middleware(['auth', 'admin'])->name('admin.penarikan');
Route::get('admin/demo/payment-gateway', PaymentGatewayIndex::class)->middleware(['auth', 'admin'])->name('admin.payments');
Route::get('admin/demo/setting', SettingIndex::class)->middleware(['auth', 'admin'])->name('admin.setting');
Route::get('admin/demo/user', UserIndex::class)->middleware(['auth', 'admin'])->name('admin.user');

Route::prefix('admin')
    ->namespace('Dashboard')
    ->middleware(['auth', 'admin'])
    ->group(function () {
        Route::get('/', [DashboardController::class, 'dashboard']);

        Route::get('/search', [DashboardController::class, 'event']);

        Route::get('/transaksi/{uid?}', [TransaksiController::class, 'transaksi']);
        Route::get('/cash/{uid?}', [CashController::class, 'cash']);
        Route::get('/editCash', [CashController::class, 'editCash'])->name('AEditCash');

        Route::get('t/online/{uid?}', [TController::class, 'tonline'])->name('tonline');

        // Route::get('/transaksi/filter', [DashboardController::class, 'transaksi']);
        Route::get('/user/{data?}', [DashboardController::class, 'user']);
        Route::get('/event/{addEvent?}/{uid?}', [DashboardController::class, 'event']);
        Route::get('/ubahEvents/{uid}', [DashboardController::class, 'ubahEvents']);
        Route::get('/penarikan', [DashboardController::class, 'penarikan']);

        Route::get('/setting/slide', [DashboardController::class, 'landing']);
        Route::get('/setting/seo', [DashboardController::class, 'seo']);
        Route::get('/setting/term', [DashboardController::class, 'term']);

        Route::get('/profile', [DashboardController::class, 'profile']);

        // Route::get('/event', [DashboardController::class, 'event']);
        // ROUTE ADD
        Route::post('/addEvents', [addController::class, 'addEvent']);
        Route::post('/addTalent', [addController::class, 'addTalent']);
        Route::post('/addHarga', [addController::class, 'addHarga']);
        Route::post('/addSlide', [addController::class, 'addSlide']);
        Route::post('/addTerm', [addController::class, 'addTerm']);
        Route::post('/addAdmin', [addController::class, 'addAdmin']);
        Route::post('/addContact', [addController::class, 'addContact']);

        // ROUTE EDIT
        // Route::get('/updateLogo/{data}', [editController::class, 'updateLogo']);
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

        // ROUTE DELETE
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

        Route::get('/payment-gateway', [PaymentGatewayController::class, 'index'])->name('payments');
        Route::post('/payment-gateway/store', [PaymentGatewayController::class, 'store'])->name('payments.store');
        Route::post('/payment-gateway/update/{paymentGateway}', [PaymentGatewayController::class, 'update'])->name('payments.update');
        Route::delete('/payment-gateway/delete/{paymentGateway}', [PaymentGatewayController::class, 'destroy'])->name('payments.destroy');
    });

// ====================
