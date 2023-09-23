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

Route::get('/', [landingController::class, 'home']);
Route::get('/ticket/{event}', [landingController::class, 'ticket']);

Route::get('/register', [UserRegisterController::class, 'index']);
Route::post('/registerUser', [UserRegisterController::class, 'create'])->name('register-user');

Route::get('/login', [UserLoginController::class, 'signIn'])->name('login');
Route::post('/loginUser', [UserLoginController::class, 'loginUser']);
// Route::get('/postEvent/{search?}', [landingController::class, 'cari']);
Route::get('/search/{cari?}/', [landingController::class, 'search']);
Route::get('/cari', [landingController::class, 'cari']);
Route::get('/term', [landingController::class, 'term']);




Route::get('/confir/data/{data}', [Controller::class, 'confir']);
Route::post('/confir/success', [Controller::class, 'success']);
// Route::post('/generate-barcode', [BarcodeController::class, 'generateBarcode']);
Route::get('/generate-barcode/{data}', [BarcodeController::class, 'generateBarcode']);

Route::middleware(['auth'])->group(function () {

    Route::get('/profile', [editController::class, 'profile']);
    Route::post('/profile/update-profile', [editController::class, 'editProfile']);

    Route::get('email/notif-email', [Controller::class, 'notif']);
    Route::get('/detail-ticket/{uid}/{user}', [BuyTicketController::class, 'index']);
    Route::post('/checkout', [BuyTicketController::class, 'checkout']);
    Route::get('/transaksi', [landingController::class, 'listTransaksi']);
    Route::post('/paynow', [TransactionController::class, 'paynow']);

    // Route::post('/paynow/callback', [TransactionController::class, 'callback'])->name('midtrans-callback');

    Route::get('/detail-ticket/delete/{uid}/{user_uid}', [DeleteController::class, 'deteleListTransaksi']);

    Route::get('/logout', function () {
        Auth::logout();
        return redirect('/');
    });
});

Route::prefix('admin')
    ->namespace('Dashboard')
    ->middleware(['auth', 'admin'])
    ->group(function () {
        Route::get('/', [DashboardController::class, 'dashboard']);
        Route::get('/landing', [DashboardController::class, 'landing']);
        Route::get('/transaksi', [DashboardController::class, 'transaksi']);
        Route::get('/ubahEvents/{uid}', [DashboardController::class, 'ubahEvents']);
        

        Route::get('/event', [DashboardController::class, 'event']);
        Route::get('/event/{addEvent?}/{uid?}', [DashboardController::class, 'event']);

        // ROUTE ADD
        Route::post('/addEvents', [addController::class, 'addEvent']);
        Route::post('/addTalent', [addController::class, 'addTalent']);
        Route::post('/addHarga', [addController::class, 'addHarga']);
        Route::post('/addSlide', [addController::class, 'addSlide']);
        Route::post('/addTerm', [addController::class, 'addTerm']);

        // ROUTE EDIT
        // Route::get('/updateLogo/{data}', [editController::class, 'updateLogo']);
        Route::post('/editTalent', [editController::class, 'editTalent']);
        Route::post('/editEvent', [editController::class, 'editEvent']);
        Route::post('/editHarga', [editController::class, 'editHarga']);
        Route::post('/editSlide', [editController::class, 'editSlide']);
        Route::post('/editLogo', [editController::class, 'editLogo']);
        Route::post('/editTerm', [editController::class, 'editTerm']);
        

        // ROUTE DELETE
        Route::get('/delete/{id}', [DeleteController::class, 'deleteTalent']);
        Route::get('/landing/delete/{uid}', [DeleteController::class, 'deleteSlide']);
        Route::get('/events/delete/{uid}', [DeleteController::class, 'deleteEvent']);
        Route::get('/hargas/delete/{id}', [DeleteController::class, 'deleteHarga']);
        Route::get('/term/delete/{id}', [DeleteController::class, 'deleteTerm']);
    });
    
    


// ====================
