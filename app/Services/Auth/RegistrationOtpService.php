<?php

namespace App\Services\Auth;

use App\Mail\RegistrationOtpMail;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class RegistrationOtpService
{
    public const SESSION_KEY = 'auth.registration_otp';
    public const OTP_LENGTH = 6;
    public const OTP_TTL_MINUTES = 5;
    public const RESEND_COOLDOWN_SECONDS = 60;
    public const MAX_ATTEMPTS = 5;
    public const SEND_RATE_LIMIT_ATTEMPTS = 5;
    public const SEND_RATE_LIMIT_DECAY_SECONDS = 600;

    public function start(string $name, string $email, string $hashedPassword): array
    {
        $normalizedEmail = $this->normalizeEmail($email);

        $this->assertEmailUnique($normalizedEmail);
        $this->ensureCanSend($normalizedEmail);

        $plainOtp = $this->generateOtp();

        $pendingRegistration = [
            'name' => trim($name),
            'email' => $normalizedEmail,
            'password' => $hashedPassword,
            'otp' => Hash::make($plainOtp),
            'expires_at' => now()->addMinutes(self::OTP_TTL_MINUTES),
            'attempts' => 0,
            'sent_at' => now(),
        ];

        session()->put(self::SESSION_KEY, $pendingRegistration);
        RateLimiter::hit($this->sendRateLimitKey($normalizedEmail), self::SEND_RATE_LIMIT_DECAY_SECONDS);

        Mail::to($normalizedEmail)->send(new RegistrationOtpMail($pendingRegistration['name'], $plainOtp));

        return $pendingRegistration;
    }

    public function resend(): array
    {
        $pendingRegistration = $this->requirePendingRegistration();

        if ($this->cooldownRemaining($pendingRegistration) > 0) {
            throw ValidationException::withMessages([
                'otp' => 'Kode OTP baru dapat dikirim ulang setelah cooldown selesai.',
            ]);
        }

        $this->assertEmailUnique($pendingRegistration['email']);
        $this->ensureCanSend($pendingRegistration['email']);

        $plainOtp = $this->generateOtp();
        $pendingRegistration['otp'] = Hash::make($plainOtp);
        $pendingRegistration['expires_at'] = now()->addMinutes(self::OTP_TTL_MINUTES);
        $pendingRegistration['attempts'] = 0;
        $pendingRegistration['sent_at'] = now();

        session()->put(self::SESSION_KEY, $pendingRegistration);
        RateLimiter::hit($this->sendRateLimitKey($pendingRegistration['email']), self::SEND_RATE_LIMIT_DECAY_SECONDS);

        Mail::to($pendingRegistration['email'])->send(new RegistrationOtpMail($pendingRegistration['name'], $plainOtp));

        return $pendingRegistration;
    }

    public function verify(string $plainOtp): array
    {
        $pendingRegistration = $this->requirePendingRegistration();

        if ($this->isExpired($pendingRegistration)) {
            throw ValidationException::withMessages([
                'otp' => 'Kode OTP sudah kedaluwarsa. Silakan kirim ulang OTP baru.',
            ]);
        }

        if ((int) $pendingRegistration['attempts'] >= self::MAX_ATTEMPTS) {
            throw ValidationException::withMessages([
                'otp' => 'Batas percobaan OTP telah habis. Silakan kirim ulang OTP baru.',
            ]);
        }

        if (! Hash::check($plainOtp, $pendingRegistration['otp'])) {
            $pendingRegistration['attempts'] = min(self::MAX_ATTEMPTS, (int) $pendingRegistration['attempts'] + 1);
            session()->put(self::SESSION_KEY, $pendingRegistration);

            throw ValidationException::withMessages([
                'otp' => $pendingRegistration['attempts'] >= self::MAX_ATTEMPTS
                    ? 'Batas percobaan OTP telah habis. Silakan kirim ulang OTP baru.'
                    : 'Kode OTP yang Anda masukkan tidak valid.',
            ]);
        }

        $this->assertEmailUnique($pendingRegistration['email']);

        return $pendingRegistration;
    }

    public function getPendingRegistration(): ?array
    {
        $pendingRegistration = session()->get(self::SESSION_KEY);

        if (! is_array($pendingRegistration)) {
            return null;
        }

        return $pendingRegistration;
    }

    public function cooldownRemaining(?array $pendingRegistration = null): int
    {
        $pendingRegistration ??= $this->getPendingRegistration();

        if (! is_array($pendingRegistration) || ! isset($pendingRegistration['sent_at'])) {
            return 0;
        }

        return max(0, now()->diffInSeconds($pendingRegistration['sent_at']->copy()->addSeconds(self::RESEND_COOLDOWN_SECONDS), false));
    }

    public function clear(): void
    {
        session()->forget(self::SESSION_KEY);
    }

    private function ensureCanSend(string $email): void
    {
        $rateLimitKey = $this->sendRateLimitKey($email);

        if (RateLimiter::tooManyAttempts($rateLimitKey, self::SEND_RATE_LIMIT_ATTEMPTS)) {
            throw ValidationException::withMessages([
                'email' => 'Terlalu banyak permintaan OTP. Silakan coba lagi beberapa saat.',
            ]);
        }
    }

    private function assertEmailUnique(string $email): void
    {
        if (User::where('email', $email)->exists()) {
            throw ValidationException::withMessages([
                'email' => 'Email sudah terdaftar.',
            ]);
        }
    }

    private function isExpired(array $pendingRegistration): bool
    {
        return ! isset($pendingRegistration['expires_at']) || now()->greaterThan($pendingRegistration['expires_at']);
    }

    private function requirePendingRegistration(): array
    {
        $pendingRegistration = $this->getPendingRegistration();

        if (! $pendingRegistration) {
            throw ValidationException::withMessages([
                'email' => 'Silakan isi form registrasi terlebih dahulu.',
            ]);
        }

        return $pendingRegistration;
    }

    private function generateOtp(): string
    {
        return str_pad((string) random_int(0, 999999), self::OTP_LENGTH, '0', STR_PAD_LEFT);
    }

    private function normalizeEmail(string $email): string
    {
        return Str::lower(trim($email));
    }

    private function sendRateLimitKey(string $email): string
    {
        return 'register-otp:'.sha1($this->normalizeEmail($email).'|'.request()->ip());
    }
}
