<?php

namespace App\Http\Controllers;

use App\Jobs\sendEmailETransaksi;
use App\Models\Cart;
use App\Models\HargaCart;
use App\Models\PaymentGateway;
use App\Models\Transaction;
use App\Models\User;
use App\Services\Tickets\TicketPricingService;
use App\Services\Tickets\TicketReservationService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Midtrans\Config as konfig;
use Midtrans\Snap;

class TransactionController extends Controller
{
    public function paynow(
        Request $request,
        TicketPricingService $pricingService,
        TicketReservationService $reservationService
    ) {
        $request->merge([
            'cart_uid' => $request->input('cart_uid', $request->input('cartUid')),
            'payment_gateway_id' => $request->input('payment_gateway_id', $request->input('payment_id')),
        ]);

        $request->validate([
            'cart_uid' => 'required|string',
            'payment_gateway_id' => 'required|integer|exists:payment_gateways,id',
        ]);

        $cartUid = $request->input('cart_uid');
        $lock = Cache::lock('paynow:'.$cartUid, 30);

        if (! $lock->get()) {
            return back()->withErrors(['msg' => 'Pembayaran sedang diproses. Mohon tunggu sebentar.']);
        }

        try {
            $paymentContext = DB::transaction(function () use ($cartUid, $request, $pricingService, $reservationService) {
                $cart = Cart::where('uid', $cartUid)
                    ->where('user_uid', Auth::user()->uid)
                    ->lockForUpdate()
                    ->first();

                if (! $cart) {
                    throw ValidationException::withMessages(['cart_uid' => 'Cart tidak ditemukan.']);
                }

                if (! in_array($cart->status, Cart::ACTIVE_RESERVATION_STATUSES, true)) {
                    throw ValidationException::withMessages(['cart_uid' => 'Cart tidak dapat dibayar pada status saat ini.']);
                }

                if ($cart->isReservationExpired()) {
                    $reservationService->releaseLockedCart($cart, Cart::STATUS_EXPIRED);

                    return [
                        'expired' => true,
                        'message' => 'Reservation sudah expired. Silakan checkout ulang.',
                    ];
                }

                if ($cart->hasActivePaymentLink()) {
                    return ['redirect_url' => $cart->link, 'expired' => false];
                }

                $gateway = PaymentGateway::where('id', $request->input('payment_gateway_id'))
                    ->where('is_active', '1')
                    ->first();

                if (! $gateway) {
                    throw ValidationException::withMessages(['payment_gateway_id' => 'Metode pembayaran tidak tersedia.']);
                }

                if (HargaCart::where('uid', $cart->uid)->count() === 0) {
                    throw ValidationException::withMessages(['cart_uid' => 'Cart kosong atau tidak valid.']);
                }

                $pricing = $pricingService->calculateCart($cart, $gateway);

                $cart->status = Cart::STATUS_PENDING;
                $cart->payment_type = $gateway->slug;
                $cart->payment_gateway_id = $gateway->id;
                $cart->internet_fee = $pricing['internet_fee'];
                $cart->pajak = $pricing['tax_amount'];
                $cart->pajak_persen = $pricing['tax_percent'];
                $cart->gross_amount = $pricing['gross_amount'];
                $cart->payment_link_expires_at = $cart->expires_at;
                $cart->save();

                Transaction::updateOrCreate(
                    ['invoice' => $cart->invoice],
                    [
                        'uid' => $cart->uid,
                        'user_uid' => $cart->user_uid,
                        'event_uid' => $cart->event_uid,
                        'amount' => (string) $pricing['gross_amount'],
                        'gross_amount' => $pricing['gross_amount'],
                        'status_transaksi' => Cart::STATUS_PENDING,
                        'payment_type' => $gateway->slug,
                    ]
                );

                return [
                    'redirect_url' => null,
                    'expired' => false,
                    'cart_uid' => $cart->uid,
                    'invoice' => $cart->invoice,
                    'gross_amount' => $pricing['gross_amount'],
                    'payment_slug' => $gateway->slug,
                    'payment_category' => $gateway->category,
                    'expires_at' => $cart->expires_at,
                    'customer_name' => Auth::user()->name,
                    'customer_email' => Auth::user()->email,
                ];
            }, 3);

            if ($paymentContext['expired'] ?? false) {
                return back()->withErrors(['msg' => $paymentContext['message']]);
            }

            if ($paymentContext['redirect_url']) {
                return redirect($paymentContext['redirect_url']);
            }

            $paymentUrl = $this->createMidtransPaymentUrl($paymentContext);

            $paymentUrl = DB::transaction(function () use ($paymentContext, $paymentUrl) {
                $cart = Cart::where('uid', $paymentContext['cart_uid'])
                    ->where('user_uid', Auth::user()->uid)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($cart->hasActivePaymentLink()) {
                    return $cart->link;
                }

                $cart->link = $paymentUrl;
                $cart->save();

                return $paymentUrl;
            }, 3);

            return redirect($paymentUrl);
        } catch (ValidationException $exception) {
            return back()->withErrors(['msg' => collect($exception->errors())->flatten()->first()]);
        } catch (Exception $exception) {
            Log::error('Gagal membuat transaksi Midtrans', [
                'cart_uid' => $cartUid,
                'user_uid' => Auth::user()->uid ?? null,
                'error' => $exception->getMessage(),
            ]);

            return back()->withErrors(['msg' => 'Gagal membuat transaksi pembayaran. Silakan coba lagi.']);
        } finally {
            optional($lock)->release();
        }
    }

    public function callback(Request $request, TicketReservationService $reservationService)
    {
        $payload = $request->all();
        $orderId = (string) ($payload['order_id'] ?? '');
        $status = (string) ($payload['transaction_status'] ?? '');
        $paymentType = (string) ($payload['payment_type'] ?? '');
        $fraudStatus = (string) ($payload['fraud_status'] ?? '');
        $statusCode = (string) ($payload['status_code'] ?? '');
        $grossAmount = $this->normalizeGrossAmount($payload['gross_amount'] ?? null);
        $midtransTransactionId = $payload['transaction_id'] ?? null;

        if (! $this->isValidCallbackPayload($payload)) {
            return response()->json(['message' => 'Invalid notification payload'], 400);
        }

        if (! $this->validSignature($orderId, $statusCode, (string) $payload['gross_amount'], (string) $payload['signature_key'])) {
            Log::warning('Invalid Midtrans signature', ['order_id' => $orderId]);

            return response()->json(['message' => 'Invalid signature'], 403);
        }

        $transaction = Transaction::where('invoice', $orderId)->first();
        $cart = Cart::where('invoice', $orderId)->first();

        if (! $cart || ! $transaction) {
            Log::warning('Midtrans callback order_id tidak ditemukan', ['order_id' => $orderId]);

            return response()->json(['message' => 'Order not found'], 404);
        }

        $expectedGrossAmount = (int) ($cart->gross_amount ?: $transaction->gross_amount ?: $transaction->amount);
        if ($expectedGrossAmount !== $grossAmount) {
            Log::warning('Midtrans gross amount mismatch', [
                'order_id' => $orderId,
                'expected' => $expectedGrossAmount,
                'actual' => $grossAmount,
            ]);

            return response()->json(['message' => 'Gross amount mismatch'], 400);
        }

        $shouldSendEmail = false;

        DB::transaction(function () use (
            $orderId,
            $status,
            $paymentType,
            $fraudStatus,
            $midtransTransactionId,
            $reservationService,
            &$shouldSendEmail
        ) {
            $cart = Cart::where('invoice', $orderId)->lockForUpdate()->firstOrFail();

            if ($cart->status === Cart::STATUS_SUCCESS) {
                return;
            }

            if ($this->isSuccessfulMidtransStatus($status, $fraudStatus)) {
                $shouldSendEmail = $reservationService->settleLockedCart($cart, $paymentType, $midtransTransactionId);

                return;
            }

            if ($status === 'pending' || ($status === 'capture' && $fraudStatus === 'challenge')) {
                $reservationService->markPendingLockedCart($cart, $paymentType);

                return;
            }

            if (in_array($status, ['deny', 'expire', 'cancel'], true)) {
                if (in_array($cart->status, Cart::ACTIVE_RESERVATION_STATUSES, true)) {
                    $reservationService->releaseLockedCart(
                        $cart,
                        $status === 'expire' ? Cart::STATUS_EXPIRED : Cart::STATUS_CANCELLED
                    );
                }

                Transaction::where('invoice', $orderId)->update([
                    'status_transaksi' => $status === 'expire' ? Cart::STATUS_EXPIRED : Cart::STATUS_CANCELLED,
                    'payment_type' => $paymentType,
                ]);
            }
        }, 3);

        if ($shouldSendEmail) {
            $cart = Cart::where('invoice', $orderId)->first();
            $user = $cart ? User::where('uid', $cart->user_uid)->first() : null;

            if ($cart && $user) {
                dispatch(new sendEmailETransaksi($user, $cart, $orderId));
            }
        }

        return response()->json([
            'meta' => [
                'code' => 200,
                'message' => 'Midtrans Notification Processed',
            ],
        ]);
    }

    protected function createMidtransPaymentUrl(array $context): string
    {
        konfig::$clientKey = config('services.midtrans.clientKey');
        konfig::$serverKey = config('services.midtrans.serverKey');
        konfig::$isProduction = config('services.midtrans.isProduction');
        konfig::$isSanitized = config('services.midtrans.isSanitized');
        konfig::$is3ds = config('services.midtrans.is3ds');

        $paymentMethod = $context['payment_slug'].($context['payment_category'] === 'ewallet' ? '' : '_va');
        if ($context['payment_slug'] === 'mandiri') {
            $paymentMethod = 'echannel';
        }

        $duration = max(1, now()->diffInMinutes($context['expires_at'], false));

        $snapPayload = [
            'transaction_details' => [
                'order_id' => $context['invoice'],
                'gross_amount' => $context['gross_amount'],
            ],
            'customer_details' => [
                'first_name' => $context['customer_name'],
                'email' => $context['customer_email'],
            ],
            'enabled_payments' => [
                $paymentMethod,
            ],
            'expiry' => [
                'start_time' => now()->format('Y-m-d H:i:s O'),
                'unit' => 'minute',
                'duration' => $duration,
            ],
        ];

        return Snap::createTransaction($snapPayload)->redirect_url;
    }

    protected function isValidCallbackPayload(array $payload): bool
    {
        $allowedStatuses = ['capture', 'settlement', 'pending', 'deny', 'expire', 'cancel'];

        return filled($payload['order_id'] ?? null)
            && filled($payload['status_code'] ?? null)
            && filled($payload['gross_amount'] ?? null)
            && filled($payload['signature_key'] ?? null)
            && in_array((string) ($payload['transaction_status'] ?? ''), $allowedStatuses, true);
    }

    protected function validSignature(string $orderId, string $statusCode, string $grossAmount, string $signature): bool
    {
        $serverKey = (string) config('services.midtrans.serverKey');
        $expected = hash('sha512', $orderId.$statusCode.$grossAmount.$serverKey);

        return hash_equals($expected, $signature);
    }

    protected function normalizeGrossAmount($grossAmount): int
    {
        return (int) round((float) $grossAmount);
    }

    protected function isSuccessfulMidtransStatus(string $status, string $fraudStatus): bool
    {
        return $status === 'settlement'
            || ($status === 'capture' && in_array($fraudStatus, ['', 'accept'], true));
    }
}
