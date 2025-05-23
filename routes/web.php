<?php

use App\Http\Controllers\Controller;
// use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;


use App\Http\Controllers\BarcodeController;
use App\Http\Controllers\landingController;
use App\Http\Controllers\Api\SlideController;
use App\Http\Controllers\BuyTicketController;
use App\Http\Controllers\Api\ConfirmController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\Dashboard\addController;
use App\Http\Controllers\Auth\UserLoginController;
use App\Http\Controllers\Dashboard\editController;
use App\Http\Controllers\Dashboard\DeleteController;
use App\Http\Controllers\Auth\UserRegisterController;
use App\Http\Controllers\Dashboard\CashController;
use App\Http\Controllers\Dashboard\DashboardController;
use App\Http\Controllers\Dashboard\PaymentGatewayController;
use App\Http\Controllers\Dashboard\TController;
use App\Http\Controllers\Dashboard\TransaksiController;
use App\Http\Controllers\Penyewa\AddController as PenyewaAddController;
use App\Http\Controllers\Penyewa\Auth\LoginController;
use App\Http\Controllers\Penyewa\BeliCash\CashController as BeliCashCashController;
use App\Http\Controllers\Penyewa\EditController as PenyewaEditController;
use App\Http\Controllers\Penyewa\DeleteController as PenyewaDelete;
use App\Http\Controllers\Penyewa\PenyewaController;

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
    ->namespace('Penyewa')
    ->middleware(['auth', 'penyewa'])
    ->group(function () {
        Route::get('/', [PenyewaController::class, 'index'])->name('dashboard');
        Route::get('/transaksi/{uid?}', [PenyewaController::class, 'transaksi'])->name('dashboard.transaksi');

        Route::get('/cash/{uid?}', [PenyewaController::class, 'cash'])->name('dashboard.cash');
        Route::get('/event/{addEvent?}/{uid?}', [PenyewaController::class, 'event']);
        Route::get('/ubahEvents/{uid}', [PenyewaController::class, 'ubahEvents']);
        Route::get('/voucher', [PenyewaController::class, 'voucher']);
        Route::get('/partner', [PenyewaController::class, 'partner']);
        Route::get('/money', [PenyewaController::class, 'money']);
        Route::get('/profile', [PenyewaController::class, 'profile']);

        Route::post('/addEvents', [PenyewaAddController::class, 'addEvent'])->name('dashboard.addEvent');
        Route::post('/addTalent', [PenyewaAddController::class, 'addTalent']);
        Route::post('/addHarga', [PenyewaAddController::class, 'addHarga']);
        Route::post('/addVoucher', [PenyewaAddController::class, 'addVoucher']);
        Route::post('/addPenarikan', [PenyewaAddController::class, 'addPenarikan']);
        // Route::post('/addCash', [BeliCashCashController::class, 'addCash']);
        Route::post('/addCash', [BeliCashCashController::class, 'createCash'])->name('add.cash');
        Route::post('/addPartner', [PenyewaAddController::class, 'addPartner']);

        Route::post('/editTalent', [PenyewaEditController::class, 'editTalent']);
        Route::post('/editEventPenyewa', [PenyewaEditController::class, 'editEventPenyewa']);
        Route::post('/editEvent', [PenyewaEditController::class, 'editEvent']);
        Route::post('/editHarga', [PenyewaEditController::class, 'editHarga']);
        Route::post('/editRekening', [PenyewaEditController::class, 'editRekening']);
        Route::post('/editProfile', [PenyewaEditController::class, 'editProfile']);
        Route::post('/editPartner', [PenyewaEditController::class, 'editPartner']);
        Route::post('/updateVoucher', [PenyewaEditController::class, 'editVoucher']);

        Route::get('/events/delete/{id}', [PenyewaDelete::class, 'eventDelete']);
        Route::get('/delete/{id}', [PenyewaDelete::class, 'deleteTalent']);
        Route::get('/hargas/delete/{id}', [PenyewaDelete::class, 'deleteHarga']);
        Route::get('/delete/voucher/{id}', [PenyewaDelete::class, 'deleteVoucher']);
        Route::get('/delete/partner/{id}', [PenyewaDelete::class, 'deletePartner']);
    });
Route::prefix('admin')
    ->namespace('Dashboard')
    ->middleware(['auth', 'admin'])
    ->group(function () {
        Route::get('/', [DashboardController::class, 'dashboard']);

        Route::get('/search',[DashboardController::class, 'event']);

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
        route::get('/delete/contact/{data}', [DeleteController::class, 'deleteContact']);


        Route::get('/payment-gateway', [PaymentGatewayController::class, 'index'])->name('payments');
        Route::post('/payment-gateway/store', [PaymentGatewayController::class, 'store'])->name('payments.store');
        Route::post('/payment-gateway/update/{paymentGateway}', [PaymentGatewayController::class, 'update'])->name('payments.update');
        Route::delete('/payment-gateway/delete/{paymentGateway}', [PaymentGatewayController::class, 'destroy'])->name('payments.destroy');



    });




// ====================
