<?php

namespace App\Livewire\Auth;

use App\Jobs\ForgotPassword as JobsForgotPassword;
use App\Models\ForgotPassword as ModelsForgotPassword;
use App\Models\User;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Livewire\Component;

class ForgotPassword extends Component
{
    private const GENERIC_MESSAGE = 'Jika email terdaftar, link reset password akan dikirim.';

    public $email;

    protected $rules = [
        'email' => 'required|email',
    ];

    public function submit()
    {
        $this->validate();

        $email = Str::lower(trim($this->email));
        $rateLimitKey = 'forgot-password:'.sha1($email.'|'.request()->ip());

        if (RateLimiter::tooManyAttempts($rateLimitKey, 3)) {
            $this->finishWithGenericMessage();

            return;
        }

        RateLimiter::hit($rateLimitKey, 600);

        $user = User::where('email', $email)->first();

        if (! $user) {
            $this->finishWithGenericMessage();

            return;
        }

        $recentActiveToken = ModelsForgotPassword::query()
            ->where('email', $email)
            ->active()
            ->where('created_at', '>=', now()->subSeconds(60))
            ->latest()
            ->first();

        if ($recentActiveToken) {
            $this->finishWithGenericMessage();

            return;
        }

        ModelsForgotPassword::query()
            ->where('email', $email)
            ->active()
            ->update(['used_at' => now()]);

        $token = Str::random(80);
        ModelsForgotPassword::create([
            'uid' => Str::random(10),
            'uid_user' => $user->uid,
            'email' => $email,
            'token_hash' => hash('sha256', $token),
            'expires_at' => now()->addMinutes(30),
        ]);

        $resetUrl = route('password.reset', [
            'token' => $token,
            'email' => $email,
        ]);

        dispatch(new JobsForgotPassword($user, $email, $resetUrl));

        $this->finishWithGenericMessage();
    }

    private function finishWithGenericMessage(): void
    {
        session()->flash('success', self::GENERIC_MESSAGE);
        $this->email = '';
    }

    public function render()
    {
        return view('livewire.auth.forgot-password')->layout('layouts.auth', ['title' => 'Lupa Password']);
    }
}
