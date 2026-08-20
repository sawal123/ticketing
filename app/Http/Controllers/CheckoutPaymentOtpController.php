<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Services\Payments\CheckoutPaymentOtpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class CheckoutPaymentOtpController extends Controller
{
    public function __construct(private CheckoutPaymentOtpService $otpService) {}

    public function send(Request $request): JsonResponse
    {
        $cart = $this->resolveCart($request);
        $user = Auth::user();
        $event = $this->otpService->assertOtpEligible($cart, $user, $cart->event);
        $result = $this->otpService->issueOtp($cart, $user, $event);

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
        $event = $this->otpService->assertOtpEligible($cart, $user, $cart->event);
        $result = $this->otpService->resendOtp($cart, $user, $event);

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
        $event = $this->otpService->assertOtpEligible($cart, $user, $cart->event);
        $this->otpService->verifyOtp($cart, $user, $event, (string) $request->input('otp'));

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

        $cart = Cart::where('uid', $request->input('cart_uid'))
            ->where('user_uid', Auth::user()->uid)
            ->first();

        if (! $cart) {
            throw ValidationException::withMessages(['cart_uid' => 'Cart tidak ditemukan.']);
        }

        return $cart;
    }
}
