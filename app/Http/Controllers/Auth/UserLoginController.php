<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Jobs\ForgotPassword as JobsForgotPassword;
use App\Models\ForgotPassword as ModelsForgotPassword;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class UserLoginController extends Controller
{
    private const GENERIC_RESET_MESSAGE = 'Jika email terdaftar, link reset password akan dikirim.';

    public function signIn()
    {
        if (Auth::user()) {
            return redirect('/');
        } else {
            return view('frontend.page.auth.signin', [
                'title' => 'Login',
            ]);
        }

    }

    public function loginUser(Request $request)
    {
        $user = $request->only('email', 'password');
        $email = Str::lower(trim((string) $request->email));
        $rateLimitKey = 'web-login:'.sha1($email.'|'.$request->ip());

        if (RateLimiter::tooManyAttempts($rateLimitKey, 5)) {
            return redirect('/login')->with('error', 'Terlalu banyak percobaan login. Silakan coba lagi beberapa saat.');
        }

        if (Auth::attempt($user)) {
            RateLimiter::clear($rateLimitKey);

            if (Auth::user()->role === 'admin') {
                return redirect('/admin');
            } else {
                return redirect('/');
            }
        } else {
            RateLimiter::hit($rateLimitKey, 60);

            return redirect('/login')->with('error', 'Email atau password salah.');
        }
    }

    public function forgot()
    {
        return view('frontend.page.auth.forgot-password', [
            'title' => 'Forgot Password',
        ]);
    }

    public function email(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $email = Str::lower(trim($request->email));
        $rateLimitKey = 'forgot-password:'.sha1($email.'|'.$request->ip());

        if (RateLimiter::tooManyAttempts($rateLimitKey, 3)) {
            return redirect()->back()->with('success', self::GENERIC_RESET_MESSAGE);
        }

        RateLimiter::hit($rateLimitKey, 600);

        $user = User::where('email', $email)->first();

        if (! $user) {
            return redirect()->back()->with('success', self::GENERIC_RESET_MESSAGE);
        }

        $recentActiveToken = ModelsForgotPassword::query()
            ->where('email', $email)
            ->active()
            ->where('created_at', '>=', now()->subSeconds(60))
            ->latest()
            ->first();

        if ($recentActiveToken) {
            return redirect()->back()->with('success', self::GENERIC_RESET_MESSAGE);
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

        return redirect()->back()->with('success', self::GENERIC_RESET_MESSAGE);
    }

    public function resetPassword($data)
    {
        $user = User::where('uid', $data)->first();
        if ($user) {
            return view('frontend.page.auth.reset-password', [
                'title' => 'Reset Password',
                'data' => $data,
            ]);
        } else {
            abort('403');
        }
    }

    public function newPassword(Request $request)
    {
        return redirect('/login')->with('error', 'Link reset password tidak valid atau sudah kedaluwarsa.');
    }
}
