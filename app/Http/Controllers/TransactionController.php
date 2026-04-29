<?php

namespace App\Http\Controllers;
// namespace Midtrans;

use Exception;
use Midtrans\Snap;
use App\Models\Cart;
use App\Models\User;
use App\Models\Event;
use Midtrans\CoreApi;
use App\Models\Voucher;
use App\Models\HargaCart;
use Midtrans\Notification;
use App\Models\CartVoucher;
use App\Models\Transaction;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\PaymentGateway;
use Midtrans\Config as konfig;
use App\Jobs\sendEmailTrnsaksi;
use App\Jobs\sendEmailETransaksi;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use App\Mail\MidtransPaymentNotification;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
// use Midtrans\CallbackService;

class TransactionController extends Controller
{
    public function paynow(Request $request)
    {
        $cart_uid = $request->cartUid;
        $payment_id = $request->payment_id;
        $user_uid = $request->person;
        $event_uid = $request->event;
        $invoice = $request->invoice;

        // 1. Ambil Data Payment Gateway
        $feePayment = PaymentGateway::find($payment_id);
        if (!$feePayment) {
            return back()->withErrors(['msg' => 'Metode pembayaran tidak ditemukan.']);
        }

        // 2. Ambil Data Event untuk mendapatkan % Pajak (Fee)
        $event = Event::where('uid', $event_uid)->first();
        $pajakPersen = $event->fee ?? 0;

        // dd($pajakPersen . ' - ' . $user_uid . ' - ' . $event_uid . ' - ' . $cart_uid . ' - ' . $payment_id);
        // 3. Ambil Item di Cart
        $hargaItems = HargaCart::where('uid', $cart_uid)->get();
        if ($hargaItems->isEmpty()) {
            return back()->withErrors(['msg' => 'Cart kosong atau tidak valid.']);
        }

        // 4. Hitung Total Harga Tiket & Total Diskon
        $totalTiket = 0;
        $totalDiskon = 0;
        foreach ($hargaItems as $item) {
            $totalTiket += ((int) $item->quantity * (int) $item->harga_ticket);
            $totalDiskon += (int) $item->disc; // Mengambil diskon yang tersimpan di HargaCart
        }

        // 5. Hitung Pajak (Pajak dihitung dari: Total Tiket - Diskon)
        $subtotal = $totalTiket - $totalDiskon;
        $nilaiPajak = ($pajakPersen / 100) * $subtotal;

        // 6. Hitung Internet Fee (Biaya Admin Payment Gateway)
        $internetFee = 0;
        if ($feePayment->biaya_type === 'rupiah') {
            $internetFee = $feePayment->biaya;
        } else {
            $internetFee = round(($feePayment->biaya / 100) * $totalTiket);
        }

        // 7. TOTAL AKHIR (Inilah yang dikirim ke Midtrans)
        $grossAmount = (int) ($subtotal + $nilaiPajak + $internetFee);

        $cart = Cart::where('user_uid', $user_uid)->where('invoice', $invoice)->first();
        if (!$cart) {
            return back()->withErrors(['msg' => 'Cart tidak ditemukan.']);
        }

        // Konfigurasi Midtrans
        konfig::$clientKey = config('services.midtrans.clientKey');
        konfig::$serverKey = config('services.midtrans.serverKey');
        konfig::$isProduction = config('services.midtrans.isProduction');
        konfig::$isSanitized = config('services.midtrans.isSanitized');
        konfig::$is3ds = config('services.midtrans.is3ds');

        $snapPayload = [
            'transaction_details' => [
                'order_id'     => $invoice,
                'gross_amount' => $grossAmount, // Nilai Akhir yang sudah termasuk Pajak
            ],
            'customer_details' => [
                'first_name' => Auth::user()->name,
                'email'      => Auth::user()->email
            ],
            'enabled_payments' => [
                $feePayment->slug . ($feePayment->category === 'ewallet' ? '' : '_va')
            ],
        ];

        try {
            $paymentUrl = Snap::createTransaction($snapPayload)->redirect_url;

            // Simpan ke database
            $cart->link = $paymentUrl;
            $cart->status = 'PENDING';
            $cart->payment_type = $feePayment->slug;
            $cart->internet_fee = $internetFee;
            $cart->pajak = $nilaiPajak;
            $cart->pajak_persen = $pajakPersen;
            $cart->save();



            $trans = Transaction::where('invoice', $invoice)->first();
            if (!$trans) {
                Transaction::create([
                    'uid'              => $cart->uid,
                    'user_uid'         => $user_uid,
                    'event_uid'        => $event_uid,
                    'amount'           => $grossAmount, // Simpan total yang sudah termasuk pajak
                    'status_transaksi' => 'PENDING',
                    'invoice'          => $invoice,
                    'payment_type'     => $feePayment->slug,
                ]);
            }

            return redirect($paymentUrl);
        } catch (\Exception $e) {
            return back()->withErrors(['msg' => 'Gagal membuat transaksi: ' . $e->getMessage()]);
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
                    $transaction->status_transaksi = 'PENDING';
                    $carts->status = 'PENDING';
                } else {
                    $transaction->status_transaksi = 'SUCCESS';
                    $transaction->payment_type = $type;
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
            $carts->status = 'SUCCESS';

            if ($voucher !== null) {
                $voucher->digunakan += 1;
                $voucher->save();
            }
        } else if ($status === 'pending') {
            $transaction->status_transaksi = 'PENDING';
            $transaction->payment_type = $type;
            $carts->status = 'PENDING';
        } else if ($status === 'deny') {
            $transaction->payment_type = $type;
            $transaction->status_transaksi = 'CANCELLED';
            $carts->status = 'CANCELLED';
        } else if ($status === 'expire') {
            $transaction->payment_type = $type;
            $transaction->status_transaksi = 'CANCELLED';
            $carts->status = 'CANCELLED';
        } else if ($status === 'cancel') {
            $transaction->payment_type = $type;
            $transaction->status_transaksi = 'CANCELLED';
            $carts->status = 'CANCELLED';
        }

        $carts->save();
        $transaction->save();


        // Kirimkan email
        if ($transaction) {
            if ($status === 'capture' && $fraud == 'accept') {
                // Mail::to($user->email)->send(new MidtransPaymentNotification($user, $carts, $order_id));
                $send = new sendEmailETransaksi($user, $carts, $order_id);
                dispatch($send);
                //
            } else if ($status === 'settlement') {
                // Mail::to($user->email)->send(new MidtransPaymentNotification($user, $carts, $order_id));
                $send = new sendEmailETransaksi($user, $carts, $order_id);
                dispatch($send);
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
