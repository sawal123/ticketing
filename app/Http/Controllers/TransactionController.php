<?php

namespace App\Http\Controllers;
// namespace Midtrans;

use Exception;
use Midtrans\Snap;
use Midtrans\Notification;
use App\Models\Cart;
use App\Models\CartVoucher;
use App\Models\User;
use App\Models\Event;
use App\Models\Transaction;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Midtrans\Config as konfig;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use App\Mail\MidtransPaymentNotification;
use App\Models\Voucher;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
// use Midtrans\CallbackService;

class TransactionController extends Controller
{
    public function paynow(Request $request)
    {
        $user_uid = $request->person;
        $event_uid = $request->event;
        $total = $request->amount;
        $invoice = $request->invoice;
        $kode = Str::random(10);

        $cart = Cart::where('user_uid', $user_uid)->where('invoice', $invoice)->first();
        // dd($invoice);
        $pay = Transaction::create([
            'uid' => $cart->uid,
            'user_uid' => $user_uid,
            'event_uid' => $event_uid,
            'amount' => $total,
            'status_transaksi' => 'UNPAID',
            'invoice' => $invoice
        ]);

        // $cart->status = 'UNPAID';
        // $cart->save();


        // Konfigurasi midtrans
        konfig::$clientKey = config('services.midtrans.clientKey');
        konfig::$serverKey = config('services.midtrans.serverKey');
        konfig::$isProduction = config('services.midtrans.isProduction');
        konfig::$isSanitized = config('services.midtrans.isSanitized');
        konfig::$is3ds = config('services.midtrans.is3ds');

        // Buat array untuk dikirim ke midtrans
        $midtrans = array(
            'transaction_details' => [
                'order_id' =>  $invoice,
                'gross_amount' => (int) $total,
            ],
            'customer_details' => [
                'first_name'    => Auth::user()->name,
                'email'         => Auth::user()->email
            ],
            'enabled_payments' => [
                'gopay', 'permata_va', 'bank_transfer'
            ],
            'vtweb' => []
        );
        try {
            // Ambil halaman payment midtrans
            $paymentUrl = Snap::createTransaction($midtrans)->redirect_url;
            $cart->link = $paymentUrl . '#';
            $cart->save();
            // Redirect ke halaman midtrans
            return redirect($paymentUrl);
        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }

    public function callback(Request $request)
    {
        //   error_reporting(0);
        // Set konfigurasi midtrans
        konfig::$clientKey = config('services.midtrans.clientKey');
        konfig::$serverKey = config('services.midtrans.serverKey');
        konfig::$isProduction = config('services.midtrans.isProduction');
        konfig::$isSanitized = config('services.midtrans.isSanitized');
        konfig::$is3ds = config('services.midtrans.is3ds');

            $notificationData = new Notification();
            // $notificationData = $notif->getResponse();

            $status = $notificationData->transaction_status;
            $type = $notificationData->payment_type;
            $fraud = $notificationData->fraud_status;
            $order_id = $notificationData->order_id;
            

            $transaction = Transaction::where('invoice', $order_id)->first();
            $carts = Cart::where('invoice', $order_id)->first();
            $cVoucher = CartVoucher::where('uid', $carts->uid)->first();
            $voucher = null;
            if ($cVoucher) {
                $voucher = Voucher::where('code', $cVoucher->code)->first();
            }
            $user = User::where('uid', $carts->user_uid)->first();

            // Handle notification status midtrans
            if ($status === 'capture') {
                if ($type === 'credit_card') {
                    if ($fraud === 'challenge') {
                        $transaction->payment_type = $type;
                        $carts->payment_type = $type;
                        $transaction->status_transaksi = 'PENDING';
                        $carts->status = 'PENDING';
                    } else {
                        $transaction->status_transaksi = 'SUCCESS';
                        $transaction->payment_type = $type;
                        $carts->payment_type = $type;
                        $carts->status = 'SUCCESS';
                        if ($voucher !== null) {
                            $voucher->digunakan += 1;
                              $voucher->save();
                        }
                    }
                }
            } else if ($status === 'settlement') {
                $transaction->status_transaksi = 'SUCCESS';
                $transaction->payment_type = $type;
                $carts->payment_type = $type;
                $carts->status = 'SUCCESS';

                if ($voucher !== null) {
                    $voucher->digunakan += 1;
                      $voucher->save();
                }
                
            } else if ($status === 'pending') {
                $transaction->status_transaksi = 'PENDING';
                $transaction->payment_type = $type;
                $carts->payment_type = $type;
                $carts->link = $carts->link . '/' . $type;
                $carts->status = 'PENDING';
            } else if ($status === 'deny') {
                $transaction->payment_type = $type;
                $carts->payment_type = $type;
                $transaction->status_transaksi = 'CANCELLED';
                $carts->status = 'CANCELLED';
            } else if ($status === 'expire') {
                $transaction->payment_type = $type;
                $carts->payment_type = $type;
                $transaction->status_transaksi = 'CANCELLED';
                $carts->status = 'CANCELLED';
            } else if ($status === 'cancel') {
                $transaction->payment_type = $type;
                $carts->payment_type = $type;
                $transaction->status_transaksi = 'CANCELLED';
                $carts->status = 'CANCELLED';
            }

            $carts->save();
            $transaction->save();


            // Kirimkan email
            if ($transaction) {
                if ($status === 'capture' && $fraud == 'accept') {
                    Mail::to($user->email)->send(new MidtransPaymentNotification($user, $carts, $order_id));
                    //
                } else if ($status === 'settlement') {
                    Mail::to($user->email)->send(new MidtransPaymentNotification($user, $carts, $order_id));
                } else if ($status === 'capture' && $fraud == 'challenge') {
                    return response()->json([
                        'meta' => [
                            'code' => 200,
                            'message' => 'Midtrans Payment Challenge'
                        ]
                    ]);
                } else {
                    return response()->json([
                        'meta' => [
                            'code' => 200,
                            'message' => 'Midtrans Payment not Settlement'
                        ]
                    ]);
                }

                return response()->json([
                    'meta' => [
                        'code' => 200,
                        'message' => 'Midtrans Notification Success'
                    ]
                ]);
            }

    }
}
