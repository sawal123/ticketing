<?php

namespace App\Services\Payments;

use App\Mail\CheckoutPaymentOtpMail;
use App\Models\Cart;
use App\Models\CheckoutPaymentOtp;
use App\Models\Event;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class CheckoutPaymentOtpService
{
    public const OTP_LENGTH = 6;
    public const OTP_TTL_MINUTES = 5;
    public const RESEND_COOLDOWN_SECONDS = 60;
    public const MAX_ATTEMPTS = 5;

    public function assertOtpEligible(Cart $cart, User $user, ?Event $event = null): Event
    {
        if ($cart->user_uid !== $user->uid) {
            throw ValidationException::withMessages(['cart_uid' => 'Cart tidak ditemukan.']);
        }

        $event = $event ?? $cart->event;

        if (! $event) {
            throw ValidationException::withMessages(['cart_uid' => 'Event tidak tersedia.']);
        }

        if (! $event->payment_otp_enabled) {
            throw ValidationException::withMessages(['cart_uid' => 'Verifikasi OTP email tidak aktif untuk event ini.']);
        }

        if (! in_array($cart->status, Cart::ACTIVE_RESERVATION_STATUSES, true)) {
            throw ValidationException::withMessages(['cart_uid' => 'Cart tidak dapat diproses pada status saat ini.']);
        }

        if ($cart->isReservationExpired()) {
            throw ValidationException::withMessages(['cart_uid' => 'Reservation sudah expired. Silakan checkout ulang.']);
        }

        return $event;
    }

    public function getLatestOtp(Cart $cart, User $user, Event $event): ?CheckoutPaymentOtp
    {
        return CheckoutPaymentOtp::query()
            ->where('cart_uid', $cart->uid)
            ->where('user_uid', $user->uid)
            ->where('event_uid', $event->uid)
            ->latest('id')
            ->first();
    }

    public function issueOtp(Cart $cart, User $user, Event $event): array
    {
        $latestOtp = $this->getLatestOtp($cart, $user, $event);

        if (! $latestOtp) {
            [$otp, $plainCode] = $this->createOtp($cart, $user, $event, false);
            Mail::to($user->email)->send(new CheckoutPaymentOtpMail($user, $event, $plainCode));

            return [
                'otp' => $otp,
                'sent' => true,
                'verified' => false,
                'status' => 'sent',
                'message' => 'Kode OTP pembayaran telah dikirim ke email Anda.',
                'resend_available_in' => self::RESEND_COOLDOWN_SECONDS,
            ];
        }

        if ($latestOtp->isVerified() && ! $latestOtp->isConsumed() && ! $latestOtp->isExpired() && $latestOtp->canAttempt()) {
            return [
                'otp' => $latestOtp,
                'sent' => false,
                'verified' => true,
                'status' => 'verified',
                'message' => 'OTP sudah terverifikasi untuk cart ini.',
                'resend_available_in' => $this->cooldownRemaining($latestOtp),
            ];
        }

        if (! $latestOtp->isConsumed() && ! $latestOtp->isExpired() && $latestOtp->canAttempt()) {
            return [
                'otp' => $latestOtp,
                'sent' => false,
                'verified' => false,
                'status' => 'active',
                'message' => 'Kode OTP masih aktif.',
                'resend_available_in' => $this->cooldownRemaining($latestOtp),
            ];
        }

        return [
            'otp' => $latestOtp,
            'sent' => false,
            'verified' => false,
            'status' => 'requires_resend',
            'message' => 'Kode OTP sudah tidak dapat digunakan. Silakan kirim ulang OTP baru.',
            'resend_available_in' => $this->cooldownRemaining($latestOtp),
        ];
    }

    public function resendOtp(Cart $cart, User $user, Event $event): array
    {
        $latestOtp = $this->getLatestOtp($cart, $user, $event);

        if ($latestOtp && $this->cooldownRemaining($latestOtp) > 0) {
            throw ValidationException::withMessages([
                'otp' => 'Kode OTP baru dapat dikirim ulang setelah cooldown selesai.',
            ]);
        }

        [$otp, $plainCode] = $this->createOtp($cart, $user, $event, true);
        Mail::to($user->email)->send(new CheckoutPaymentOtpMail($user, $event, $plainCode));

        return [
            'otp' => $otp,
            'sent' => true,
            'verified' => false,
            'resend_available_in' => self::RESEND_COOLDOWN_SECONDS,
        ];
    }

    public function verifyOtp(Cart $cart, User $user, Event $event, string $plainCode): CheckoutPaymentOtp
    {
        $result = DB::transaction(function () use ($cart, $user, $event, $plainCode) {
            $otp = CheckoutPaymentOtp::query()
                ->where('cart_uid', $cart->uid)
                ->where('user_uid', $user->uid)
                ->where('event_uid', $event->uid)
                ->latest('id')
                ->lockForUpdate()
                ->first();

            if (! $otp || $otp->isConsumed() || $otp->isExpired()) {
                return [
                    'otp' => null,
                    'error' => 'Kode OTP sudah tidak valid. Silakan kirim ulang OTP baru.',
                ];
            }

            if ($otp->isVerified()) {
                return [
                    'otp' => $otp,
                    'error' => null,
                ];
            }

            if (! $otp->canAttempt()) {
                return [
                    'otp' => null,
                    'error' => 'Batas percobaan OTP telah habis. Silakan kirim ulang OTP baru.',
                ];
            }

            if (! $otp->matchesCode($plainCode)) {
                $otp->attempts = min(self::MAX_ATTEMPTS, (int) $otp->attempts + 1);
                $otp->save();

                return [
                    'otp' => null,
                    'error' => $otp->attempts >= self::MAX_ATTEMPTS
                        ? 'Batas percobaan OTP telah habis. Silakan kirim ulang OTP baru.'
                        : 'Kode OTP yang Anda masukkan tidak valid.',
                ];
            }

            $otp->verified_at = now();
            $otp->save();

            return [
                'otp' => $otp,
                'error' => null,
            ];
        }, 3);

        if ($result['error'] !== null) {
            throw ValidationException::withMessages(['otp' => $result['error']]);
        }

        return $result['otp'];
    }

    public function assertVerifiedOtp(Cart $cart, User $user, Event $event): CheckoutPaymentOtp
    {
        $otp = $this->getLatestOtp($cart, $user, $event);

        if (
            ! $otp
            || $otp->isConsumed()
            || $otp->isExpired()
            || ! $otp->isVerified()
            || ! $otp->canAttempt()
        ) {
            throw ValidationException::withMessages([
                'cart_uid' => 'Verifikasi OTP email diperlukan sebelum pembayaran.',
            ]);
        }

        return $otp;
    }

    public function consumeVerifiedOtp(Cart $cart, User $user, Event $event): void
    {
        $otp = $this->getLatestOtp($cart, $user, $event);

        if (! $otp || $otp->isConsumed() || ! $otp->isVerified() || $otp->isExpired()) {
            return;
        }

        $otp->consumed_at = now();
        $otp->save();
    }

    public function cooldownRemaining(CheckoutPaymentOtp $otp): int
    {
        if (! $otp->sent_at) {
            return 0;
        }

        return max(0, now()->diffInSeconds($otp->sent_at->copy()->addSeconds(self::RESEND_COOLDOWN_SECONDS), false));
    }

    private function createOtp(Cart $cart, User $user, Event $event, bool $invalidateExisting): array
    {
        if ($invalidateExisting) {
            CheckoutPaymentOtp::query()
                ->where('cart_uid', $cart->uid)
                ->where('user_uid', $user->uid)
                ->where('event_uid', $event->uid)
                ->whereNull('consumed_at')
                ->update([
                    'expires_at' => now(),
                    'updated_at' => now(),
                ]);
        }

        $plainCode = str_pad((string) random_int(0, 999999), self::OTP_LENGTH, '0', STR_PAD_LEFT);

        $otp = CheckoutPaymentOtp::create([
            'cart_uid' => $cart->uid,
            'user_uid' => $user->uid,
            'event_uid' => $event->uid,
            'code_hash' => Hash::make($plainCode),
            'expires_at' => now()->addMinutes(self::OTP_TTL_MINUTES),
            'attempts' => 0,
            'sent_at' => now(),
            'verified_at' => null,
            'consumed_at' => null,
        ]);

        return [$otp, $plainCode];
    }
}
