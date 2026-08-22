<?php

namespace Tests\Feature;

use App\Livewire\Auth\Register;
use App\Mail\RegistrationOtpMail;
use App\Models\User;
use App\Services\Auth\RegistrationOtpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Socialite\Facades\Socialite;
use Livewire\Livewire;
use Tests\TestCase;

class RegistrationOtpTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        cache()->flush();
        session()->start();
        View::share('logo', [(object) ['logo' => '']]);
    }

    public function test_initial_mail_send_failure_does_not_create_pending_registration_or_user(): void
    {
        Mail::swap(new class
        {
            public function to($email)
            {
                return $this;
            }

            public function send($mailable): void
            {
                throw new \RuntimeException('mail transport down');
            }
        });

        Livewire::test(Register::class)
            ->set('name', 'Mail Failure User')
            ->set('email', 'mail-failure@example.test')
            ->set('password', 'Password123')
            ->set('password_confirmation', 'Password123')
            ->call('register')
            ->assertHasErrors(['email']);

        $this->assertNull(session(RegistrationOtpService::SESSION_KEY));
        $this->assertDatabaseMissing('users', ['email' => 'mail-failure@example.test']);
    }

    public function test_user_is_not_created_before_valid_otp(): void
    {
        Mail::fake();

        Livewire::test(Register::class)
            ->set('name', 'Manual User')
            ->set('email', 'manual@example.test')
            ->set('password', 'Password123')
            ->set('password_confirmation', 'Password123')
            ->call('register')
            ->assertSet('showOtpStep', true);

        $pending = $this->pendingRegistration();
        $sentOtp = $this->extractSentOtp();

        $this->assertDatabaseMissing('users', ['email' => 'manual@example.test']);
        $this->assertSame('Manual User', $pending['name']);
        $this->assertSame('manual@example.test', $pending['email']);
        $this->assertNotSame('Password123', $pending['password']);
        $this->assertTrue(Hash::check('Password123', $pending['password']));
        $this->assertNotSame($sentOtp, $pending['otp']);
        $this->assertTrue(Hash::check($sentOtp, $pending['otp']));
    }

    public function test_correct_otp_creates_user_and_logs_them_in(): void
    {
        Mail::fake();

        $component = Livewire::test(Register::class)
            ->set('name', 'Verified User')
            ->set('email', 'verified@example.test')
            ->set('password', 'Password123')
            ->set('password_confirmation', 'Password123')
            ->call('register');

        $otp = $this->extractSentOtp();

        $component->set('otp', $otp)
            ->call('verifyOtp')
            ->assertRedirect('/');

        $user = User::where('email', 'verified@example.test')->first();

        $this->assertNotNull($user);
        $this->assertNotNull($user->email_verified_at);
        $this->assertAuthenticatedAs($user);
        $this->assertNull(session(RegistrationOtpService::SESSION_KEY));
    }

    public function test_wrong_otp_is_rejected(): void
    {
        Mail::fake();

        Livewire::test(Register::class)
            ->set('name', 'Wrong OTP User')
            ->set('email', 'wrong-otp@example.test')
            ->set('password', 'Password123')
            ->set('password_confirmation', 'Password123')
            ->call('register')
            ->set('otp', '123456')
            ->call('verifyOtp')
            ->assertHasErrors(['otp']);

        $this->assertDatabaseMissing('users', ['email' => 'wrong-otp@example.test']);
        $this->assertSame(1, $this->pendingRegistration()['attempts']);
    }

    public function test_expired_otp_is_rejected(): void
    {
        Mail::fake();

        Livewire::test(Register::class)
            ->set('name', 'Expired OTP User')
            ->set('email', 'expired@example.test')
            ->set('password', 'Password123')
            ->set('password_confirmation', 'Password123')
            ->call('register');

        $pending = $this->pendingRegistration();
        $pending['expires_at'] = now()->subSecond();
        session()->put(RegistrationOtpService::SESSION_KEY, $pending);

        Livewire::test(Register::class)
            ->set('otp', $this->extractSentOtp())
            ->call('verifyOtp')
            ->assertHasErrors(['otp']);

        $this->assertDatabaseMissing('users', ['email' => 'expired@example.test']);
    }

    public function test_attempt_limit_blocks_further_verification_until_new_otp_is_requested(): void
    {
        Mail::fake();

        $component = Livewire::test(Register::class)
            ->set('name', 'Attempt User')
            ->set('email', 'attempts@example.test')
            ->set('password', 'Password123')
            ->set('password_confirmation', 'Password123')
            ->call('register');

        $correctOtp = $this->extractSentOtp();

        for ($attempt = 1; $attempt <= RegistrationOtpService::MAX_ATTEMPTS; $attempt++) {
            $component->set('otp', '111111')
                ->call('verifyOtp')
                ->assertHasErrors(['otp']);
        }

        $this->assertSame(RegistrationOtpService::MAX_ATTEMPTS, $this->pendingRegistration()['attempts']);

        $component->set('otp', $correctOtp)
            ->call('verifyOtp')
            ->assertHasErrors(['otp']);

        $this->assertDatabaseMissing('users', ['email' => 'attempts@example.test']);
    }

    public function test_resend_cooldown_prevents_immediate_resend(): void
    {
        Mail::fake();

        Livewire::test(Register::class)
            ->set('name', 'Cooldown User')
            ->set('email', 'cooldown@example.test')
            ->set('password', 'Password123')
            ->set('password_confirmation', 'Password123')
            ->call('register')
            ->call('resendOtp')
            ->assertHasErrors(['otp']);

        Mail::assertSent(RegistrationOtpMail::class, 1);
    }

    public function test_resend_mail_failure_keeps_previous_otp_valid(): void
    {
        Mail::fake();

        $component = Livewire::test(Register::class)
            ->set('name', 'Resend Failure User')
            ->set('email', 'resend-failure@example.test')
            ->set('password', 'Password123')
            ->set('password_confirmation', 'Password123')
            ->call('register');

        $firstOtp = $this->extractSentOtp();
        $pendingRegistration = $this->pendingRegistration();

        $this->travel(61)->seconds();

        Mail::swap(new class
        {
            public function to($email)
            {
                return $this;
            }

            public function send($mailable): void
            {
                throw new \RuntimeException('mail transport down');
            }
        });

        $component->call('resendOtp')
            ->assertHasErrors(['otp']);

        $this->assertSame($pendingRegistration['otp'], $this->pendingRegistration()['otp']);

        $component->set('otp', $firstOtp)
            ->call('verifyOtp')
            ->assertRedirect('/');
    }

    public function test_cooldown_cannot_be_bypassed_by_editing_and_resubmitting_the_same_email(): void
    {
        Mail::fake();

        Livewire::test(Register::class)
            ->set('name', 'Cooldown Bypass User')
            ->set('email', 'cooldown-bypass@example.test')
            ->set('password', 'Password123')
            ->set('password_confirmation', 'Password123')
            ->call('register')
            ->call('editRegistration')
            ->set('email', 'cooldown-bypass@example.test')
            ->set('password', 'Password123')
            ->set('password_confirmation', 'Password123')
            ->call('register')
            ->assertHasErrors(['email']);

        Mail::assertSent(RegistrationOtpMail::class, 1);
    }

    public function test_rate_limit_per_email_is_enforced(): void
    {
        Mail::fake();

        $service = app(RegistrationOtpService::class);

        for ($attempt = 1; $attempt <= RegistrationOtpService::EMAIL_RATE_LIMIT_ATTEMPTS; $attempt++) {
            $service->start('Email Limited User', 'email-limit@example.test', Hash::make('Password123'));
            $service->clear();
            $this->travel(61)->seconds();
        }

        try {
            $service->start('Email Limited User', 'email-limit@example.test', Hash::make('Password123'));
            $this->fail('Expected email rate limit validation exception was not thrown.');
        } catch (ValidationException $exception) {
            $this->assertSame(
                'Terlalu banyak permintaan OTP. Silakan coba lagi beberapa saat.',
                $exception->errors()['email'][0]
            );
        }
    }

    public function test_rate_limit_per_ip_is_enforced(): void
    {
        Mail::fake();

        $service = app(RegistrationOtpService::class);

        for ($attempt = 1; $attempt <= RegistrationOtpService::IP_RATE_LIMIT_ATTEMPTS; $attempt++) {
            $service->start('IP Limited User', "ip-limit-{$attempt}@example.test", Hash::make('Password123'));
            $service->clear();
        }

        try {
            $service->start('IP Limited User', 'ip-limit-overflow@example.test', Hash::make('Password123'));
            $this->fail('Expected IP rate limit validation exception was not thrown.');
        } catch (ValidationException $exception) {
            $this->assertSame(
                'Terlalu banyak permintaan OTP. Silakan coba lagi beberapa saat.',
                $exception->errors()['email'][0]
            );
        }
    }

    public function test_resend_success_invalidates_old_otp_and_activates_new_otp(): void
    {
        Mail::fake();

        $component = Livewire::test(Register::class)
            ->set('name', 'Resend Success User')
            ->set('email', 'resend-success@example.test')
            ->set('password', 'Password123')
            ->set('password_confirmation', 'Password123')
            ->call('register');

        $firstOtp = $this->extractSentOtp();

        $this->travel(61)->seconds();

        $component->call('resendOtp');

        $secondOtp = $this->extractSentOtp(1);

        $this->assertNotSame($firstOtp, $secondOtp);

        $component->set('otp', $firstOtp)
            ->call('verifyOtp')
            ->assertHasErrors(['otp']);

        $component->set('otp', $secondOtp)
            ->call('verifyOtp')
            ->assertRedirect('/');
    }

    public function test_duplicate_email_is_rejected_when_otp_is_verified(): void
    {
        Mail::fake();

        $component = Livewire::test(Register::class)
            ->set('name', 'Duplicate User')
            ->set('email', 'duplicate@example.test')
            ->set('password', 'Password123')
            ->set('password_confirmation', 'Password123')
            ->call('register');

        $otp = $this->extractSentOtp();

        User::factory()->create([
            'uid' => (string) Str::uuid(),
            'email' => 'duplicate@example.test',
            'password' => Hash::make('Different123'),
            'role' => User::USER_ROLE,
            'gambar' => 'default.png',
        ]);

        $component->set('otp', $otp)
            ->call('verifyOtp')
            ->assertHasErrors(['email']);

        $this->assertGuest();
        $this->assertSame(1, User::where('email', 'duplicate@example.test')->count());
    }

    public function test_duplicate_race_during_user_creation_returns_validation_error_instead_of_http_500(): void
    {
        Mail::fake();

        $component = Livewire::test(Register::class)
            ->set('name', 'Race User')
            ->set('email', 'race@example.test')
            ->set('password', 'Password123')
            ->set('password_confirmation', 'Password123')
            ->call('register');

        DB::partialMock()
            ->shouldReceive('transaction')
            ->once()
            ->andThrow($this->duplicateEmailQueryException());

        $component->set('otp', $this->extractSentOtp())
            ->call('verifyOtp')
            ->assertHasErrors(['email']);

        $this->assertGuest();
        $this->assertSame(0, User::withTrashed()->where('email', 'race@example.test')->count());
    }

    public function test_changing_email_invalidates_previous_otp(): void
    {
        Mail::fake();

        $component = Livewire::test(Register::class)
            ->set('name', 'Change Email User')
            ->set('email', 'before@example.test')
            ->set('password', 'Password123')
            ->set('password_confirmation', 'Password123')
            ->call('register');

        $firstOtp = $this->extractSentOtp();

        $component->call('editRegistration')
            ->assertSet('showOtpStep', false)
            ->set('email', 'after@example.test')
            ->set('password', 'Password123')
            ->set('password_confirmation', 'Password123')
            ->call('register')
            ->assertSet('showOtpStep', true);

        $secondOtp = $this->extractSentOtp(1);

        $component->set('otp', $firstOtp)
            ->call('verifyOtp')
            ->assertHasErrors(['otp']);

        $this->assertDatabaseMissing('users', ['email' => 'before@example.test']);

        $component->set('otp', $secondOtp)
            ->call('verifyOtp')
            ->assertRedirect('/');

        $this->assertDatabaseHas('users', ['email' => 'after@example.test']);
        $this->assertDatabaseMissing('users', ['email' => 'before@example.test']);
    }

    public function test_google_callback_flow_is_not_forced_through_registration_otp(): void
    {
        Socialite::shouldReceive('driver')
            ->once()
            ->with('google')
            ->andReturnSelf();
        Socialite::shouldReceive('user')
            ->once()
            ->andReturn((object) [
                'id' => 'google-user-123',
                'name' => 'Google User',
                'email' => 'google-user@example.test',
                'avatar' => 'https://example.test/avatar.png',
            ]);

        $this->get('/auth/google/callback')
            ->assertRedirect('/');

        $user = User::where('email', 'google-user@example.test')->first();

        $this->assertNotNull($user);
        $this->assertSame('google-user-123', $user->google_id);
        $this->assertAuthenticatedAs($user);
        $this->assertNull(session(RegistrationOtpService::SESSION_KEY));
    }

    private function pendingRegistration(): array
    {
        return session(RegistrationOtpService::SESSION_KEY);
    }

    private function extractSentOtp(int $index = 0): string
    {
        $mails = Mail::sent(RegistrationOtpMail::class);
        $mail = $mails[$index];
        preg_match('/\b(\d{6})\b/', $mail->render(), $matches);

        return $matches[1];
    }

    private function duplicateEmailQueryException(): QueryException
    {
        $previous = new \PDOException(
            "SQLSTATE[23000]: Integrity constraint violation: 1062 Duplicate entry 'race@example.test' for key 'users_email_unique'"
        );
        $previous->errorInfo = [
            '23000',
            1062,
            "Duplicate entry 'race@example.test' for key 'users_email_unique'",
        ];

        return new QueryException(
            'mysql',
            'insert into `users` (`email`) values (?)',
            ['race@example.test'],
            $previous
        );
    }
}
