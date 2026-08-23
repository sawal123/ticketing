<?php

namespace App\Http\Controllers;

use App\Jobs\sendEmailETransaksi;
use App\Models\Cart;
use App\Models\EventPaymentGateway;
use App\Models\HargaCart;
use App\Models\Transaction;
use App\Services\Payments\CheckoutPaymentOtpService;
use App\Models\User;
use App\Services\Tickets\TicketPricingService;
use App\Services\Tickets\TicketReservationService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Midtrans\Config as konfig;
use Midtrans\Snap;

class TransactionController extends Controller
{
    private const SUPPORTED_MIDTRANS_PAYMENT_CODES = [
        'bca_va',
        'bni_va',
        'bri_va',
        'cimb_va',
        'danamon_va',
        'bsi_va',
        'permata_va',
        'echannel',
        'dana',
        'ovo',
        'gopay',
        'shopeepay',
        'other_qris',
        'credit_card',
        'alfamart',
        'indomaret',
    ];

    private const LEGACY_MIDTRANS_PAYMENT_CODE_MAP = [
        'bca' => 'bca_va',
        'bni' => 'bni_va',
        'bri' => 'bri_va',
        'cimb' => 'cimb_va',
        'danamon' => 'danamon_va',
        'bsi' => 'bsi_va',
        'permata' => 'permata_va',
        'mandiri' => 'echannel',
        'dana' => 'dana',
        'ovo' => 'ovo',
        'gopay' => 'gopay',
        'shopeepay' => 'shopeepay',
    ];

    private const RECIPIENT_EMAIL_OPTION_ACCOUNT = 'use_account_email';

    private const RECIPIENT_EMAIL_OPTION_OTHER = 'other_email';

    public function paynow(
        Request $request,
        TicketPricingService $pricingService,
        TicketReservationService $reservationService,
        CheckoutPaymentOtpService $checkoutPaymentOtpService
    ) {
        $request->merge([
            'cart_uid' => $request->input('cart_uid', $request->input('cartUid')),
            'payment_gateway_id' => $request->input('payment_gateway_id', $request->input('payment_id')),
        ]);

        $request->validate([
            'cart_uid' => 'required|string',
            'payment_gateway_id' => 'required|integer',
        ]);

        $cartUid = $request->input('cart_uid');
        $lock = Cache::lock('paynow:'.$cartUid, 30);

        if (! $lock->get()) {
            return back()->withErrors(['msg' => 'Pembayaran sedang diproses. Mohon tunggu sebentar.']);
        }

        try {
            $paymentContext = DB::transaction(function () use ($cartUid, $request, $pricingService, $reservationService, $checkoutPaymentOtpService) {
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

                if ($cart->recipientSnapshotLocked()) {
                    $this->assertRecipientSnapshotExists($cart);
                } elseif ($cart->hasRecipientSnapshot() && ! $this->requestIncludesRecipientSnapshot($request)) {
                    // Existing carts can continue using their stored snapshot when paynow is posted directly.
                } else {
                    $recipientSnapshot = $this->resolveRecipientSnapshot($request);
                    $cart->ticket_holder_name = $recipientSnapshot['ticket_holder_name'];
                    $cart->ticket_recipient_email = $recipientSnapshot['ticket_recipient_email'];
                }

                $event = $cart->event;

                if (! $event) {
                    throw ValidationException::withMessages(['cart_uid' => 'Event tidak tersedia.']);
                }

                if ($event->payment_otp_enabled) {
                    $checkoutPaymentOtpService->assertOtpEligible($cart, Auth::user(), $event);
                    $checkoutPaymentOtpService->assertVerifiedOtp($cart, Auth::user(), $event);
                }

                $eventGateway = EventPaymentGateway::query()
                    ->where('event_id', $event->id)
                    ->where('payment_gateway_id', $request->input('payment_gateway_id'))
                    ->where('is_active', true)
                    ->whereHas('paymentGateway', function ($query) {
                        $query->where('is_active', true);
                    })
                    ->with('paymentGateway')
                    ->first();

                if (! $eventGateway || ! $eventGateway->paymentGateway) {
                    throw ValidationException::withMessages(['payment_gateway_id' => 'Metode pembayaran tidak tersedia.']);
                }

                $gateway = $eventGateway->paymentGateway;
                $midtransPaymentCode = $this->resolveMidtransPaymentCode($gateway);

                if (! $midtransPaymentCode) {
                    throw ValidationException::withMessages([
                        'payment_gateway_id' => 'Metode pembayaran belum dikonfigurasi dengan benar.',
                    ]);
                }

                if (HargaCart::where('uid', $cart->uid)->count() === 0) {
                    throw ValidationException::withMessages(['cart_uid' => 'Cart kosong atau tidak valid.']);
                }

                $pricing = $pricingService->calculateCart($cart, $gateway);

                if (! $pricing['payment_gateway_available']) {
                    throw ValidationException::withMessages(['payment_gateway_id' => 'Metode pembayaran tidak tersedia.']);
                }

                $cart->status = Cart::STATUS_PENDING;
                $cart->payment_type = $gateway->slug;
                $cart->payment_gateway_id = $gateway->id;
                $cart->payment_fee_mode = $pricing['payment_fee_mode'];
                $cart->payment_fee_fixed = $pricing['payment_fee_fixed'];
                $cart->payment_fee_percent = $pricing['payment_fee_percent'];
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
                    'midtrans_payment_code' => $midtransPaymentCode,
                    'event_uid' => $event->uid,
                    'expires_at' => $cart->expires_at,
                    'customer_name' => Auth::user()->name,
                    'customer_email' => Auth::user()->email,
                    'otp_required' => (bool) $event->payment_otp_enabled,
                ];
            }, 3);

            if ($paymentContext['expired'] ?? false) {
                return back()->withErrors(['msg' => $paymentContext['message']]);
            }

            if ($paymentContext['redirect_url']) {
                return redirect($paymentContext['redirect_url']);
            }

            $paymentUrl = $this->createMidtransPaymentUrl($paymentContext);

            $paymentUrl = DB::transaction(function () use ($paymentContext, $paymentUrl, $checkoutPaymentOtpService) {
                $cart = Cart::where('uid', $paymentContext['cart_uid'])
                    ->where('user_uid', Auth::user()->uid)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($cart->hasActivePaymentLink()) {
                    return $cart->link;
                }

                $cart->link = $paymentUrl;
                $cart->save();

                if ($paymentContext['otp_required'] ?? false) {
                    $event = $cart->event;

                    if ($event) {
                        $checkoutPaymentOtpService->consumeVerifiedOtp($cart, Auth::user(), $event);
                    }
                }

                return $paymentUrl;
            }, 3);

            return redirect($paymentUrl);
        } catch (ValidationException $exception) {
            return back()
                ->withInput($this->recipientInput($request))
                ->withErrors(['msg' => collect($exception->errors())->flatten()->first()]);
        } catch (Exception $exception) {
            Log::error('Gagal membuat transaksi Midtrans', [
                'cart_uid' => $cartUid,
                'user_uid' => Auth::user()->uid ?? null,
                'error' => $exception->getMessage(),
            ]);

            return back()
                ->withInput($this->recipientInput($request))
                ->withErrors(['msg' => 'Gagal membuat transaksi pembayaran. Silakan coba lagi.']);
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
                dispatch(new sendEmailETransaksi($user, $cart));
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
                $context['midtrans_payment_code'],
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

    protected function resolveMidtransPaymentCode($gateway): ?string
    {
        if ($gateway->midtrans_code !== null) {
            $explicitCode = trim((string) $gateway->midtrans_code);

            return in_array($explicitCode, self::SUPPORTED_MIDTRANS_PAYMENT_CODES, true)
                ? $explicitCode
                : null;
        }

        $slug = Str::lower((string) $gateway->slug);

        return self::LEGACY_MIDTRANS_PAYMENT_CODE_MAP[$slug] ?? null;
    }

    protected function resolveRecipientSnapshot(Request $request): array
    {
        $validated = $request->validate([
            'ticket_holder_name' => 'required|string|max:255',
            'ticket_recipient_email_option' => ['required', Rule::in([
                self::RECIPIENT_EMAIL_OPTION_ACCOUNT,
                self::RECIPIENT_EMAIL_OPTION_OTHER,
            ])],
            'ticket_recipient_other_email' => 'nullable|email|max:255|required_if:ticket_recipient_email_option,'.self::RECIPIENT_EMAIL_OPTION_OTHER,
        ]);

        $ticketRecipientEmail = $validated['ticket_recipient_email_option'] === self::RECIPIENT_EMAIL_OPTION_ACCOUNT
            ? Auth::user()->email
            : trim((string) $validated['ticket_recipient_other_email']);

        return [
            'ticket_holder_name' => trim((string) $validated['ticket_holder_name']),
            'ticket_recipient_email' => $ticketRecipientEmail,
        ];
    }

    protected function assertRecipientSnapshotExists(Cart $cart): void
    {
        if ($cart->hasRecipientSnapshot()) {
            return;
        }

        throw ValidationException::withMessages([
            'cart_uid' => 'Informasi pemegang tiket belum lengkap. Silakan checkout ulang.',
        ]);
    }

    protected function requestIncludesRecipientSnapshot(Request $request): bool
    {
        return $request->hasAny([
            'ticket_holder_name',
            'ticket_recipient_email_option',
            'ticket_recipient_other_email',
        ]);
    }

    protected function recipientInput(Request $request): array
    {
        return $request->only([
            'ticket_holder_name',
            'ticket_recipient_email_option',
            'ticket_recipient_other_email',
        ]);
    }
}
