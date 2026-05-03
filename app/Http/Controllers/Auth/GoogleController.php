<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class GoogleController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function redirectToGoogle()
    {
        return Socialite::driver('google')->redirect();
    }

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function handleGoogleCallback()
    {
        try {
            $user = Socialite::driver('google')->user();
            $finduser = User::where('google_id', $user->id)->first();

            if ($finduser) {
                $finduser->update([
                    'google_token' => $user->token,
                    'google_refresh_token' => $user->refreshToken,
                ]);

                Auth::login($finduser);
                return redirect()->intended('/');
            } else {
                // Check if user with same email exists
                $existingUser = User::where('email', $user->email)->first();

                if ($existingUser) {
                    $existingUser->update([
                        'google_id' => $user->id,
                        'google_token' => $user->token,
                        'google_refresh_token' => $user->refreshToken,
                    ]);
                    Auth::login($existingUser);
                } else {
                    $newUser = User::create([
                        'uid' => (string) Str::uuid(),
                        'name' => $user->name,
                        'email' => $user->email,
                        'google_id' => $user->id,
                        'google_token' => $user->token,
                        'google_refresh_token' => $user->refreshToken,
                        'password' => Hash::make(Str::random(16)),
                        'role' => User::USER_ROLE,
                        'birthday' => now()->format('Y-m-d'),
                        'gender' => 'Other',
                        'gambar' => $user->avatar,
                    ]);

                    Auth::login($newUser);
                }

                return redirect()->intended('/');
            }
        } catch (Exception $e) {
            return redirect('login')->with('error', 'Something went wrong while logging in with Google.');
        }
    }
}
