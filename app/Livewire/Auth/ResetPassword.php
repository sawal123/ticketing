<?php

namespace App\Livewire\Auth;

use App\Models\ForgotPassword as ModelsForgotPassword;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Livewire\Component;
use RuntimeException;

class ResetPassword extends Component
{
    private const INVALID_LINK_MESSAGE = 'Link reset password tidak valid atau sudah kedaluwarsa.';

    public $token;

    public $email;

    public $password;

    public $password_confirmation;

    public bool $invalidLink = false;

    protected $rules = [
        'password' => 'required|min:8|confirmed|regex:/^(?=.*[A-Za-z])(?=.*\d).+$/',
    ];

    protected $messages = [
        'password.regex' => 'Password harus mengandung huruf dan angka.',
    ];

    public function mount($token)
    {
        $this->token = $token;
        $this->email = Str::lower(trim((string) request()->query('email')));
        $this->invalidLink = $this->resolveResetToken() === null;
    }

    public function resetPassword()
    {
        $this->validate();

        $reset = $this->resolveResetToken();

        if (! $reset) {
            $this->invalidLink = true;
            session()->flash('error', self::INVALID_LINK_MESSAGE);

            return;
        }

        try {
            DB::transaction(function () use ($reset): void {
                $lockedReset = ModelsForgotPassword::query()
                    ->whereKey($reset->id)
                    ->lockForUpdate()
                    ->first();

                if (! $lockedReset || ! $lockedReset->isUsable()) {
                    throw new RuntimeException(self::INVALID_LINK_MESSAGE);
                }

                $user = User::query()
                    ->where('uid', $lockedReset->uid_user)
                    ->where('email', $lockedReset->email)
                    ->lockForUpdate()
                    ->first();

                if (! $user) {
                    throw new RuntimeException(self::INVALID_LINK_MESSAGE);
                }

                $user->forceFill([
                    'password' => Hash::make($this->password),
                    'remember_token' => Str::random(60),
                ])->save();

                $user->tokens()->delete();

                $lockedReset->forceFill(['used_at' => now()])->save();
            });
        } catch (RuntimeException) {
            $this->invalidLink = true;
            session()->flash('error', self::INVALID_LINK_MESSAGE);

            return;
        }

        session()->flash('success', 'Password Anda telah berhasil diperbarui. Silakan login kembali.');

        return redirect()->route('login');
    }

    private function resolveResetToken(): ?ModelsForgotPassword
    {
        if (! is_string($this->token) || ! is_string($this->email) || $this->token === '' || $this->email === '') {
            return null;
        }

        $reset = ModelsForgotPassword::query()
            ->where('email', $this->email)
            ->where('token_hash', hash('sha256', $this->token))
            ->latest()
            ->first();

        if (! $reset || ! $reset->isUsable()) {
            return null;
        }

        return $reset;
    }

    public function render()
    {
        return view('livewire.auth.reset-password')->layout('layouts.auth', ['title' => 'Reset Password']);
    }
}
