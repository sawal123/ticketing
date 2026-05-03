<?php

namespace App\Livewire\Auth;

use App\Models\User;
use App\Models\ForgotPassword as ModelsForgotPassword;
use App\Jobs\ForgotPassword as JobsForgotPassword;
use Illuminate\Support\Str;
use Livewire\Component;

class ForgotPassword extends Component
{
    public $email;

    protected $rules = [
        'email' => 'required|email',
    ];

    public function submit()
    {
        $this->validate();

        $user = User::where('email', $this->email)->first();

        if ($user) {
            $add = new ModelsForgotPassword([
                'uid' => Str::random(10),
                'uid_user' => $user->uid,
            ]);
            $add->save();

            dispatch(new JobsForgotPassword($user, $this->email));

            session()->flash('success', 'Periksa Email Anda (atau folder Spam) untuk instruksi reset password.');
            $this->email = '';
        } else {
            session()->flash('error', 'Email tidak terdaftar dalam sistem kami.');
        }
    }

    public function render()
    {
        return view('livewire.auth.forgot-password')->layout('layouts.auth', ['title' => 'Lupa Password']);
    }
}
