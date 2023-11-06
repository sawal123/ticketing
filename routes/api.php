<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ConfirmController;
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

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('/login',[AuthController::class, 'login']);

Route::get('/slide/{data?}', [SlideController::class, 'slide']);
Route::get('/confirm/{data?}',[ConfirmController::class, 'cekData']);
Route::get('/listEvent', [ConfirmController::class, 'listEvent']);
Route::get('/verfikasi/{data?}',[ConfirmController::class, 'verfikasi']);
Route::put('/status/{data}', [ConfirmController::class, 'upKonfirmasi']);


Route::post('/callback', [TransactionController::class, 'callback']);


Route::get('/finishMidtrans', [SlideController::class, 'finishMidtrans']);

