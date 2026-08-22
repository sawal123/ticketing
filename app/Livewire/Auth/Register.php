<?php

namespace App\Livewire\Auth;

use App\Models\User;
use App\Services\Auth\RegistrationOtpService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class Register extends Component
{
    private const REGISTRATION_FIELDS = ['name', 'email', 'password', 'password_confirmation'];

    public $name;
    public $email;
    public $password;
    public $password_confirmation;
    public $otp = '';
    public $showOtpStep = false;

    protected $messages = [
        'password.regex' => 'Password harus mengandung huruf dan angka.',
        'password_confirmation.same' => 'Konfirmasi password tidak cocok.',
        'otp.required' => 'Kode OTP wajib diisi.',
        'otp.digits' => 'Kode OTP harus terdiri dari 6 digit.',
    ];

    public function mount(RegistrationOtpService $registrationOtpService): void
    {
        $this->syncOtpState($registrationOtpService);
    }

    public function updated($propertyName)
    {
        if ($this->showOtpStep && in_array($propertyName, self::REGISTRATION_FIELDS, true)) {
            $this->invalidatePendingRegistration();

            return;
        }

        if ($propertyName === 'otp') {
            $this->validateOnly($propertyName, $this->otpRules(), $this->messages);

            return;
        }

        if (! $this->showOtpStep) {
            $this->validateOnly($propertyName, $this->registrationRules(), $this->messages);
        }
    }

    public function register(RegistrationOtpService $registrationOtpService)
    {
        $validated = $this->validate($this->registrationRules(), $this->messages);

        $registrationOtpService->start(
            $validated['name'],
            $validated['email'],
            Hash::make($validated['password'])
        );

        $this->syncOtpState($registrationOtpService);
        session()->flash('status', 'Kode OTP telah dikirim ke email Anda.');
    }

    public function verifyOtp(RegistrationOtpService $registrationOtpService)
    {
        $this->validate($this->otpRules(), $this->messages);

        try {
            $pendingRegistration = $registrationOtpService->verify($this->otp);
        } catch (ValidationException $exception) {
            if (array_key_exists('email', $exception->errors())) {
                $this->showOtpStep = false;
            }

            throw $exception;
        }

        try {
            $user = DB::transaction(function () use ($pendingRegistration) {
                if (User::where('email', $pendingRegistration['email'])->exists()) {
                    throw ValidationException::withMessages([
                        'email' => 'Email sudah terdaftar.',
                    ]);
                }

                return User::create([
                    'uid' => (string) Str::uuid(),
                    'name' => $pendingRegistration['name'],
                    'email' => $pendingRegistration['email'],
                    'password' => $pendingRegistration['password'],
                    'role' => User::USER_ROLE,
                    'birthday' => now()->format('Y-m-d'),
                    'gender' => 'Other',
                    'gambar' => 'default.png',
                    'kota' => '-',
                    'alamat' => '-',
                ]);
            });
        } catch (ValidationException $exception) {
            $this->showOtpStep = false;

            throw $exception;
        }

        $registrationOtpService->clear();
        $this->reset(['otp', 'password', 'password_confirmation']);
        $this->showOtpStep = false;

        Auth::login($user);
        session()->regenerate();

        return redirect('/');
    }

    public function resendOtp(RegistrationOtpService $registrationOtpService): void
    {
        $registrationOtpService->resend();
        $this->syncOtpState($registrationOtpService);

        session()->flash('status', 'Kode OTP baru telah dikirim ke email Anda.');
    }

    public function editRegistration(RegistrationOtpService $registrationOtpService): void
    {
        $registrationOtpService->clear();
        $this->showOtpStep = false;
        $this->reset('otp');
        $this->resetValidation();
        session()->flash('status', 'Silakan perbarui data registrasi Anda dan kirim OTP baru.');
    }

    public function getResendCooldownProperty(): int
    {
        return app(RegistrationOtpService::class)->cooldownRemaining();
    }

    public function render()
    {
        return view('livewire.auth.register')->layout('layouts.auth', ['title' => 'Sign Up']);
    }

    private function registrationRules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:8|regex:/^(?=.*[a-zA-Z])(?=.*\d).+$/',
            'password_confirmation' => 'required|same:password',
        ];
    }

    private function otpRules(): array
    {
        return [
            'otp' => 'required|digits:6',
        ];
    }

    private function syncOtpState(RegistrationOtpService $registrationOtpService): void
    {
        $pendingRegistration = $registrationOtpService->getPendingRegistration();

        if (! $pendingRegistration) {
            $this->showOtpStep = false;

            return;
        }

        $this->showOtpStep = true;
        $this->name = $pendingRegistration['name'];
        $this->email = $pendingRegistration['email'];
        $this->password = '';
        $this->password_confirmation = '';
        $this->otp = '';
    }

    private function invalidatePendingRegistration(): void
    {
        app(RegistrationOtpService::class)->clear();
        $this->showOtpStep = false;
        $this->reset('otp', 'password', 'password_confirmation');
        $this->resetValidation();

        session()->flash('status', 'Perubahan data registrasi membatalkan OTP sebelumnya. Silakan kirim OTP baru.');
    }
}
