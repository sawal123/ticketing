<?php

namespace App\Services\Auth;

use App\Mail\RegistrationOtpMail;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Throwable;
use Illuminate\Validation\ValidationException;

class RegistrationOtpService
{
    public const SESSION_KEY = 'auth.registration_otp';
    public const OTP_LENGTH = 6;
    public const OTP_TTL_MINUTES = 5;
    public const RESEND_COOLDOWN_SECONDS = 60;
    public const MAX_ATTEMPTS = 5;
    public const EMAIL_RATE_LIMIT_ATTEMPTS = 5;
    public const IP_RATE_LIMIT_ATTEMPTS = 20;
    public const SEND_RATE_LIMIT_DECAY_SECONDS = 600;

    public function start(string $name, string $email, string $hashedPassword): array
    {
        $normalizedEmail = $this->normalizeEmail($email);

        $this->assertEmailUnique($normalizedEmail);
        $this->ensureCanSend($normalizedEmail, 'email');

        [$pendingRegistration, $plainOtp] = $this->makePendingRegistration(
            trim($name),
            $normalizedEmail,
            $hashedPassword
        );

        $this->sendOtpMail($pendingRegistration['name'], $normalizedEmail, $plainOtp, 'email');
        $this->storePendingRegistration($pendingRegistration);
        $this->recordSuccessfulSend($normalizedEmail);

        return $pendingRegistration;
    }

    public function resend(): array
    {
        $pendingRegistration = $this->requirePendingRegistration();
        $this->assertEmailUnique($pendingRegistration['email']);
        $this->ensureCanSend($pendingRegistration['email'], 'otp');

        [$replacementRegistration, $plainOtp] = $this->makePendingRegistration(
            $pendingRegistration['name'],
            $pendingRegistration['email'],
            $pendingRegistration['password']
        );

        $this->sendOtpMail(
            $replacementRegistration['name'],
            $replacementRegistration['email'],
            $plainOtp,
            'otp'
        );
        $this->storePendingRegistration($replacementRegistration);
        $this->recordSuccessfulSend($replacementRegistration['email']);

        return $replacementRegistration;
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

        if (! is_array($pendingRegistration) || empty($pendingRegistration['email'])) {
            return 0;
        }

        $cooldownKey = $this->cooldownRateLimitKey($pendingRegistration['email']);

        if (! RateLimiter::tooManyAttempts($cooldownKey, 1)) {
            return 0;
        }

        return RateLimiter::availableIn($cooldownKey);
    }

    public function clear(): void
    {
        session()->forget(self::SESSION_KEY);
    }

    private function ensureCanSend(string $email, string $errorKey): void
    {
        $cooldownKey = $this->cooldownRateLimitKey($email);

        if (RateLimiter::tooManyAttempts($cooldownKey, 1)) {
            throw ValidationException::withMessages([
                $errorKey => 'Kode OTP baru dapat dikirim ulang setelah cooldown selesai.',
            ]);
        }

        if (RateLimiter::tooManyAttempts($this->emailRateLimitKey($email), self::EMAIL_RATE_LIMIT_ATTEMPTS)) {
            throw ValidationException::withMessages([
                $errorKey => 'Terlalu banyak permintaan OTP. Silakan coba lagi beberapa saat.',
            ]);
        }

        if (RateLimiter::tooManyAttempts($this->ipRateLimitKey(), self::IP_RATE_LIMIT_ATTEMPTS)) {
            throw ValidationException::withMessages([
                $errorKey => 'Terlalu banyak permintaan OTP. Silakan coba lagi beberapa saat.',
            ]);
        }
    }

    private function assertEmailUnique(string $email): void
    {
        if (User::withTrashed()->where('email', $email)->exists()) {
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

    private function makePendingRegistration(string $name, string $email, string $hashedPassword): array
    {
        $plainOtp = $this->generateOtp();

        return [[
            'name' => $name,
            'email' => $email,
            'password' => $hashedPassword,
            'otp' => Hash::make($plainOtp),
            'expires_at' => now()->addMinutes(self::OTP_TTL_MINUTES),
            'attempts' => 0,
            'sent_at' => now(),
        ], $plainOtp];
    }

    private function sendOtpMail(string $name, string $email, string $plainOtp, string $errorKey): void
    {
        try {
            Mail::to($email)->send(new RegistrationOtpMail($name, $plainOtp));
        } catch (Throwable $throwable) {
            report($throwable);

            throw ValidationException::withMessages([
                $errorKey => 'Gagal mengirim kode OTP. Silakan coba lagi.',
            ]);
        }
    }

    private function storePendingRegistration(array $pendingRegistration): void
    {
        session()->put(self::SESSION_KEY, $pendingRegistration);
    }

    private function recordSuccessfulSend(string $email): void
    {
        RateLimiter::hit($this->cooldownRateLimitKey($email), self::RESEND_COOLDOWN_SECONDS);
        RateLimiter::hit($this->emailRateLimitKey($email), self::SEND_RATE_LIMIT_DECAY_SECONDS);
        RateLimiter::hit($this->ipRateLimitKey(), self::SEND_RATE_LIMIT_DECAY_SECONDS);
    }

    private function normalizeEmail(string $email): string
    {
        return Str::lower(trim($email));
    }

    private function cooldownRateLimitKey(string $email): string
    {
        return 'register-otp-cooldown:'.sha1($this->normalizeEmail($email));
    }

    private function emailRateLimitKey(string $email): string
    {
        return 'register-otp-email:'.sha1($this->normalizeEmail($email));
    }

    private function ipRateLimitKey(): string
    {
        return 'register-otp-ip:'.sha1((string) (request()->ip() ?? 'unknown'));
    }
}
