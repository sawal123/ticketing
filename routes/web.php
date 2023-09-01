<?php

use App\Http\Controllers\Controller;
use App\Http\Controllers\TransactionController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;


use App\Http\Controllers\landingController;
use App\Http\Controllers\BuyTicketController;
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

Route::get('/', [landingController::class, 'home']);
Route::get('/ticket/{event}', [landingController::class, 'ticket']);

Route::get('/register', [UserRegisterController::class, 'index']);
Route::post('/registerUser', [UserRegisterController::class, 'create'])->name('register-user');

Route::get('/login', [UserLoginController::class, 'signIn'])->name('login');
Route::post('/loginUser', [UserLoginController::class, 'loginUser']);

Route::middleware(['auth'])->group(function () {
    Route::get('/detail-ticket/{uid}/{user}', [BuyTicketController::class, 'index']);
    Route::post('/checkout', [BuyTicketController::class, 'checkout']);
    Route::get('/transaksi', [landingController::class, 'listTransaksi']);

    Route::post('/paynow', [TransactionController::class, 'paynow']);

    Route::post('/paynow/callback', [TransactionController::class, 'callback'])->name('midtrans-callback');

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
        Route::get('/ubahEvents/{uid}', [DashboardController::class, 'ubahEvents']);

        Route::get('/event', [DashboardController::class, 'event']);
        Route::get('/event/{addEvent?}', [DashboardController::class, 'event']);



        // ROUTE ADD
        Route::post('/addEvents', [addController::class, 'addEvent']);
        Route::post('/addTalent', [addController::class, 'addTalent']);
        Route::post('/addHarga', [addController::class, 'addHarga']);
        Route::post('/addSlide', [addController::class, 'addSlide']);

        // ROUTE EDIT
        Route::post('/editTalent', [editController::class, 'editTalent']);
        Route::post('/editEvent', [editController::class, 'editEvent']);
        Route::post('/editHarga', [editController::class, 'editHarga']);

        // ROUTE DELETE
        Route::get('/delete/{id}', [DeleteController::class, 'deleteTalent']);
        
    });
    


// ====================
