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
use App\Http\Controllers\Dashboard\DashboardController;
use App\Http\Controllers\Penyewa\AddController as PenyewaAddController;
use App\Http\Controllers\Penyewa\Auth\LoginController;
use App\Http\Controllers\Penyewa\EditController as PenyewaEditController;
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
        Route::get('/transaksi', [PenyewaController::class, 'transaksi'])->name('dashboard.transaksi');
        Route::get('/cash', [PenyewaController::class, 'cash'])->name('dashboard.cash');
        Route::get('/event/{addEvent?}/{uid?}', [PenyewaController::class, 'event']);
        Route::get('/ubahEvents/{uid}', [PenyewaController::class, 'ubahEvents']);
        Route::get('/voucher', [PenyewaController::class, 'voucher']);
        Route::get('/money', [PenyewaController::class, 'money']);
        Route::get('/profile', [PenyewaController::class, 'profile']);

        Route::post('/addEvents', [PenyewaAddController::class, 'addEvent'])->name('dashboard.addEvent');
        Route::post('/addTalent', [PenyewaAddController::class, 'addTalent']);
        Route::post('/addHarga', [PenyewaAddController::class, 'addHarga']);
        Route::post('/addVoucher', [PenyewaAddController::class, 'addVoucher']);
        Route::post('/addPenarikan', [PenyewaAddController::class, 'addPenarikan']);

        Route::post('/editTalent', [PenyewaEditController::class, 'editTalent']);
        Route::post('/editEvent', [PenyewaEditController::class, 'editEvent']);
        Route::post('/editHarga', [PenyewaEditController::class, 'editHarga']);
        Route::post('/editRekening', [PenyewaEditController::class, 'editRekening']);
        Route::post('/editProfile', [PenyewaEditController::class, 'editProfile']);


        Route::get('/delete/{id}', [DeleteController::class, 'deleteTalent']);
        Route::get('/hargas/delete/{id}', [DeleteController::class, 'deleteHarga']);
        Route::get('/delete/voucher/{id}', [DeleteController::class, 'deleteVoucher']);
    });
Route::prefix('admin')
    ->namespace('Dashboard')
    ->middleware(['auth', 'admin'])
    ->group(function () {
        Route::get('/', [DashboardController::class, 'dashboard']);
        Route::get('/landing', [DashboardController::class, 'landing']);
        Route::get('/transaksi', [DashboardController::class, 'transaksi']);
        Route::get('/user/{data?}', [DashboardController::class, 'user']);
        Route::get('/event/{addEvent?}/{uid?}', [DashboardController::class, 'event']);
        Route::get('/ubahEvents/{uid}', [DashboardController::class, 'ubahEvents']);
        Route::get('/penarikan', [DashboardController::class, 'penarikan']);
        

        // Route::get('/event', [DashboardController::class, 'event']);
        // ROUTE ADD
        Route::post('/addEvents', [addController::class, 'addEvent']);
        Route::post('/addTalent', [addController::class, 'addTalent']);
        Route::post('/addHarga', [addController::class, 'addHarga']);
        Route::post('/addSlide', [addController::class, 'addSlide']);
        Route::post('/addTerm', [addController::class, 'addTerm']);
        Route::post('/addAdmin', [addController::class, 'addAdmin']);

        // ROUTE EDIT
        // Route::get('/updateLogo/{data}', [editController::class, 'updateLogo']);
        Route::post('/editTalent', [editController::class, 'editTalent']);
        Route::post('/editEvent', [editController::class, 'editEvent']);
        Route::post('/editHarga', [editController::class, 'editHarga']);
        Route::post('/editSlide', [editController::class, 'editSlide']);
        Route::post('/editLogo', [editController::class, 'editLogo']);
        Route::post('/editTerm', [editController::class, 'editTerm']);
        Route::post('/user/editUser', [editController::class, 'editUser']);
        Route::get('/setujuiEvent/{data}', [editController::class, 'setujuiEvent']);
        Route::post('/editPenarikan', [editController::class, 'editStatusInvoice']);


        // ROUTE DELETE
        Route::get('/delete/{id}', [DeleteController::class, 'deleteTalent']);
        Route::get('/landing/delete/{uid}', [DeleteController::class, 'deleteSlide']);
        Route::get('/events/delete/{uid}', [DeleteController::class, 'deleteEvent']);
        Route::get('/hargas/delete/{id}', [DeleteController::class, 'deleteHarga']);
        Route::get('/term/delete/{id}', [DeleteController::class, 'deleteTerm']);
        Route::get('/user/delete/{id}', [DeleteController::class, 'deleteUser']);
        Route::get('/deletePen/{data}', [DeleteController::class, 'deletePenarikan']);
    });
    
    


// ====================
