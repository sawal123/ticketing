<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Services\Payments\CheckoutPaymentOtpService;
use App\Services\Tickets\CartOwnershipService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class CheckoutPaymentOtpController extends Controller
{
    public function __construct(
        private CheckoutPaymentOtpService $otpService,
        private CartOwnershipService $ownershipService
    ) {}

    public function send(Request $request): JsonResponse
    {
        $cart = $this->resolveCart($request);
        $user = Auth::user();
        $recipientEmail = $this->resolveGuestEmail($request, $cart);
        $recipientName = $cart->isGuest()
            ? trim((string) $request->input('ticket_holder_name', $cart->ticket_holder_name))
            : null;
        $event = $this->otpService->assertOtpEligible($cart, $user, $cart->event, $recipientEmail);
        $result = $this->otpService->issueOtp($cart, $user, $event, $recipientEmail, $recipientName ?: null);

        return response()->json([
            'message' => $result['message'],
            'status' => $result['status'],
            'verified' => $result['verified'],
            'resend_available_in' => $result['resend_available_in'],
        ]);
    }

    public function resend(Request $request): JsonResponse
    {
        $cart = $this->resolveCart($request);
        $user = Auth::user();
        $recipientEmail = $this->resolveGuestEmail($request, $cart);
        $recipientName = $cart->isGuest()
            ? trim((string) $request->input('ticket_holder_name', $cart->ticket_holder_name))
            : null;
        $event = $this->otpService->assertOtpEligible($cart, $user, $cart->event, $recipientEmail);
        $result = $this->otpService->resendOtp($cart, $user, $event, $recipientEmail, $recipientName ?: null);

        return response()->json([
            'message' => 'Kode OTP baru telah dikirim ke email Anda.',
            'status' => 'resent',
            'verified' => false,
            'resend_available_in' => $result['resend_available_in'],
        ]);
    }

    public function verify(Request $request): JsonResponse
    {
        $request->validate([
            'otp' => ['required', 'regex:/^\d{6}$/'],
        ]);

        $cart = $this->resolveCart($request);
        $user = Auth::user();
        $recipientEmail = $this->resolveGuestEmail($request, $cart);
        $event = $this->otpService->assertOtpEligible($cart, $user, $cart->event, $recipientEmail);
        $this->otpService->verifyOtp($cart, $user, $event, (string) $request->input('otp'), $recipientEmail);

        return response()->json([
            'message' => 'Verifikasi OTP berhasil.',
            'status' => 'verified',
            'verified' => true,
        ]);
    }

    private function resolveCart(Request $request): Cart
    {
        $request->merge([
            'cart_uid' => $request->input('cart_uid', $request->input('cartUid')),
        ]);

        $request->validate([
            'cart_uid' => 'required|string',
        ]);

        $cart = $this->ownershipService->findOwnedCart($request, (string) $request->input('cart_uid'));

        if (! $cart) {
            throw ValidationException::withMessages(['cart_uid' => 'Cart tidak ditemukan.']);
        }

        return $cart;
    }

    private function resolveGuestEmail(Request $request, Cart $cart): ?string
    {
        if (! $cart->isGuest()) {
            return null;
        }

        $email = trim((string) $request->input('ticket_recipient_email', $cart->ticket_recipient_email));

        if ($email === '' || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            throw ValidationException::withMessages([
                'ticket_recipient_email' => 'Masukkan alamat email aktif yang valid terlebih dahulu.',
            ]);
        }

        return strtolower($email);
    }
}
