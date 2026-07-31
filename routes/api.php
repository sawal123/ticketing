<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ConfirmController;
use App\Http\Controllers\Api\LandingController;
use App\Http\Controllers\Api\ListTicketController;
use App\Http\Controllers\Api\MidtransController;
use App\Http\Controllers\Api\SlideController;
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

Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:scanner-login');

Route::middleware(['auth:sanctum', 'roles:penyewa,staff'])->group(function () {
    Route::get('/verfikasi/{data?}', [ConfirmController::class, 'verfikasi']);

    // Route::post('/ticket/verify', [ConfirmController::class, 'prosesVerifikasiTiket']);
    Route::post('/ticket/search', [ConfirmController::class, 'checkTicketByGateToken']);
    Route::post('/ticket/confirm', [ConfirmController::class, 'confirmTicketStatus']);
    Route::middleware('throttle:gate-manual')->group(function () {
        Route::post('/ticket/manual/search', [ConfirmController::class, 'checkTicketByManualCode']);
        Route::post('/ticket/manual/confirm', [ConfirmController::class, 'confirmTicketByManualCode']);
    });

    Route::get('/event/{uid}/verified-tickets', [ListTicketController::class, 'listTiketVerifikasi']);
    Route::get('/ticket/{uid}/detail', [ListTicketController::class, 'showTicketDetail']);
    Route::get('/listEvent', [ConfirmController::class, 'listEvent']);
    Route::post('/logout', [AuthController::class, 'logout']);
});
Route::get('/slide/{data?}', [SlideController::class, 'slide']);
Route::get('/landing', [LandingController::class, 'getLandingData']);

// Midtrans callback is registered in routes/web.php at /api/callback without the API throttle limiter.
Route::get('/finishMidtrans', [MidtransController::class, 'finish']);
Route::get('/pendingMidtrans', [MidtransController::class, 'pending']);
