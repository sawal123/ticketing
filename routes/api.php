<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ConfirmController;
use App\Http\Controllers\Api\LandingController;
use App\Http\Controllers\Api\ListTicketController;
use App\Http\Controllers\Api\MidtransController;
use App\Http\Controllers\Api\SlideController;
use App\Http\Controllers\Dashboard\DashboardController;
use App\Http\Controllers\TransactionController;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/


Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::put('/status/{data}', [ConfirmController::class, 'upKonfirmasi']);
    Route::get('/confirm/{data}', [ConfirmController::class, 'cekData']);



    Route::get('/verfikasi/{data?}', [ConfirmController::class, 'verfikasi']);

    // Route::post('/ticket/verify', [ConfirmController::class, 'prosesVerifikasiTiket']);
    Route::post('/ticket/search', [ConfirmController::class, 'checkTicketByInvoice']);
    // 2. Update status (Saat klik tombol Verifikasi)
    Route::post('/ticket/confirm/{uid}', [ConfirmController::class, 'confirmTicketStatus']);

    Route::get('/event/{uid}/verified-tickets', [ListTicketController::class, 'listTiketVerifikasi']);
    Route::get('/ticket/{uid}/detail', [ListTicketController::class, 'showTicketDetail']);
    Route::get('/listEvent', [ConfirmController::class, 'listEvent']);
    Route::post('/logout', [AuthController::class, 'logout']);
});
Route::get('/slide/{data?}', [SlideController::class, 'slide']);
Route::get('/landing', [LandingController::class, 'getLandingData']);



Route::post('/callback', [TransactionController::class, 'callback']);
Route::get('/finishMidtrans', [MidtransController::class, 'finish']);
Route::get('/pendingMidtrans', [MidtransController::class, 'pending']);
