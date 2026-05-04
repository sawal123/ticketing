<?php

namespace App\Http\Controllers;

// namespace Midtrans;

use App\Jobs\sendEmailETransaksi;
use App\Mail\MidtransPaymentNotification;
use App\Models\Cart;
use App\Models\CartVoucher;
use App\Models\Event;
use App\Models\HargaCart;
use App\Models\PaymentGateway;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Voucher;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Midtrans\Config as konfig;
use Midtrans\Notification;
use Midtrans\Snap;

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
        if (! $feePayment) {
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
            // Internet fee dihitung dari subtotal (tiket - diskon)
            $internetFee = round(($feePayment->biaya / 100) * $subtotal);
        }

        // 7. TOTAL AKHIR (Inilah yang dikirim ke Midtrans)
        // Pastikan grossAmount sudah termasuk subtotal, pajak, dan internet_fee (7200 atau 5%)
        $grossAmount = (int) ($subtotal + $nilaiPajak + $internetFee);

        $cart = Cart::where('user_uid', $user_uid)->where('invoice', $invoice)->first();
        if (! $cart) {
            return back()->withErrors(['msg' => 'Cart tidak ditemukan.']);
        }

        // Konfigurasi Midtrans
        konfig::$clientKey = config('services.midtrans.clientKey');
        konfig::$serverKey = config('services.midtrans.serverKey');
        konfig::$isProduction = config('services.midtrans.isProduction');
        konfig::$isSanitized = config('services.midtrans.isSanitized');
        konfig::$is3ds = config('services.midtrans.is3ds');

        // Mapping Payment Method untuk Midtrans
        // Gunakan echannel khusus untuk Mandiri VA agar tidak error
        $paymentMethod = $feePayment->slug.($feePayment->category === 'ewallet' ? '' : '_va');
        if ($feePayment->slug === 'mandiri') {
            $paymentMethod = 'echannel';
        }

        $snapPayload = [
            'transaction_details' => [
                'order_id' => $invoice,
                'gross_amount' => $grossAmount, 
            ],
            'customer_details' => [
                'first_name' => Auth::user()->name,
                'email' => Auth::user()->email,
            ],
            'enabled_payments' => [
                $paymentMethod,
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
            if (! $trans) {
                Transaction::create([
                    'uid' => $cart->uid,
                    'user_uid' => $user_uid,
                    'event_uid' => $event_uid,
                    'amount' => $grossAmount, // Simpan total yang sudah termasuk pajak
                    'status_transaksi' => 'PENDING',
                    'invoice' => $invoice,
                    'payment_type' => $feePayment->slug,
                ]);
            }

            return redirect($paymentUrl);
        } catch (Exception $e) {
            return back()->withErrors(['msg' => 'Gagal membuat transaksi: '.$e->getMessage()]);
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

        $notificationData = new Notification;
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
                    if ($carts->status !== 'SUCCESS') {
                        $transaction->status_transaksi = 'SUCCESS';
                        $transaction->payment_type = $type;
                        $carts->status = 'SUCCESS';
                        if ($voucher !== null) {
                            $voucher->digunakan += 1;
                            $voucher->save();
                        }
                    }
                }
            }
        } elseif ($status === 'settlement') {
            if ($carts->status !== 'SUCCESS') {
                $transaction->status_transaksi = 'SUCCESS';
                $transaction->payment_type = $type;
                $carts->status = 'SUCCESS';

                if ($voucher !== null) {
                    $voucher->digunakan += 1;
                    $voucher->save();
                }
            }
        } elseif ($status === 'pending') {
            $transaction->status_transaksi = 'PENDING';
            $transaction->payment_type = $type;
            $carts->status = 'PENDING';
        } elseif ($status === 'deny') {
            $transaction->payment_type = $type;
            $transaction->status_transaksi = 'CANCELLED';
            $carts->status = 'CANCELLED';
        } elseif ($status === 'expire') {
            $transaction->payment_type = $type;
            $transaction->status_transaksi = 'CANCELLED';
            $carts->status = 'CANCELLED';
        } elseif ($status === 'cancel') {
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
            } elseif ($status === 'settlement') {
                // Mail::to($user->email)->send(new MidtransPaymentNotification($user, $carts, $order_id));
                $send = new sendEmailETransaksi($user, $carts, $order_id);
                dispatch($send);
            } elseif ($status === 'capture' && $fraud == 'challenge') {
                return response()->json([
                    'meta' => [
                        'code' => 200,
                        'message' => 'Midtrans Payment Challenge',
                    ],
                ]);
            } else {
                return response()->json([
                    'meta' => [
                        'code' => 200,
                        'message' => 'Midtrans Payment not Settlement',
                    ],
                ]);
            }

            return response()->json([
                'meta' => [
                    'code' => 200,
                    'message' => 'Midtrans Notification Success',
                ],
            ]);
        }
    }
}
