<?php

namespace Tests\Feature;

use App\Livewire\Auth\ForgotPassword;
use App\Livewire\Auth\ResetPassword;
use App\Mail\ForgotPassword as ForgotPasswordMail;
use App\Models\ForgotPassword as ForgotPasswordToken;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class ResetPasswordSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        RateLimiter::clear('forgot-password:'.sha1('reset@example.test|127.0.0.1'));
        View::share('logo', [(object) ['logo' => '']]);
    }

    public function test_user_can_request_reset_password_and_email_contains_token_link_not_uid(): void
    {
        Mail::fake();
        $user = $this->user(['uid' => 'known-user-uid', 'email' => 'reset@example.test']);

        Livewire::test(ForgotPassword::class)
            ->set('email', $user->email)
            ->call('submit')
            ->assertSet('email', '');

        $reset = ForgotPasswordToken::first();

        $this->assertNotNull($reset);
        $this->assertSame($user->email, $reset->email);
        $this->assertNotSame($user->uid, $reset->token_hash);
        $this->assertSame(64, strlen($reset->token_hash));
        $this->assertTrue($reset->expires_at->between(now()->addMinutes(29), now()->addMinutes(31)));

        Mail::assertSent(ForgotPasswordMail::class, function (ForgotPasswordMail $mail) use ($user) {
            $html = $mail->render();

            return str_contains($html, '/reset-password/')
                && str_contains($html, 'email=reset%40example.test')
                && ! str_contains($html, '/reset-password/'.$user->uid);
        });
    }

    public function test_unknown_email_shows_generic_message_and_does_not_create_email_or_job(): void
    {
        Mail::fake();

        Livewire::test(ForgotPassword::class)
            ->set('email', 'unknown@example.test')
            ->call('submit')
            ->assertSet('email', '');

        $this->assertDatabaseCount('forgot_passwords', 0);
        Mail::assertNothingSent();
    }

    public function test_valid_reset_token_can_change_password_and_revoke_sanctum_tokens(): void
    {
        $user = $this->user();
        $apiToken = $user->createToken('scanner')->plainTextToken;
        $plainToken = Str::random(80);
        $reset = $this->resetToken($user, $plainToken);

        $this->resetPassword($plainToken, $user->email, 'Newpass123')
            ->assertRedirect(route('login', absolute: false));

        $user->refresh();
        $reset->refresh();

        $this->assertTrue(Hash::check('Newpass123', $user->password));
        $this->assertNotNull($user->remember_token);
        $this->assertNotNull($reset->used_at);
        $this->assertDatabaseMissing('personal_access_tokens', [
            'token' => hash('sha256', explode('|', $apiToken, 2)[1]),
        ]);
    }

    public function test_reset_token_cannot_be_used_twice(): void
    {
        $user = $this->user();
        $plainToken = Str::random(80);
        $this->resetToken($user, $plainToken);

        $this->resetPassword($plainToken, $user->email, 'Firstpass1');

        $this->resetPassword($plainToken, $user->email, 'Secondpass2')
            ->assertNoRedirect()
            ->assertSet('invalidLink', true);

        $this->assertFalse(Hash::check('Secondpass2', $user->fresh()->password));
    }

    public function test_token_marked_used_before_submit_cannot_reset_password(): void
    {
        $user = $this->user();
        $plainToken = Str::random(80);
        $reset = $this->resetToken($user, $plainToken);
        $reset->forceFill(['used_at' => now()])->save();

        $this->resetPassword($plainToken, $user->email, 'Newpass123')
            ->assertNoRedirect()
            ->assertSet('invalidLink', true);

        $this->assertFalse(Hash::check('Newpass123', $user->fresh()->password));
    }

    public function test_expired_token_cannot_be_used(): void
    {
        $user = $this->user();
        $plainToken = Str::random(80);
        $this->resetToken($user, $plainToken, now()->subMinute());

        $this->resetPassword($plainToken, $user->email, 'Newpass123')
            ->assertNoRedirect()
            ->assertSet('invalidLink', true);

        $this->assertFalse(Hash::check('Newpass123', $user->fresh()->password));
    }

    public function test_wrong_token_cannot_be_used(): void
    {
        $user = $this->user();
        $this->resetToken($user, Str::random(80));

        $this->resetPassword(Str::random(80), $user->email, 'Newpass123')
            ->assertNoRedirect()
            ->assertSet('invalidLink', true);

        $this->assertFalse(Hash::check('Newpass123', $user->fresh()->password));
    }

    public function test_password_without_number_or_letter_is_rejected(): void
    {
        $user = $this->user();
        $plainToken = Str::random(80);
        $this->resetToken($user, $plainToken);

        $this->resetPassword($plainToken, $user->email, 'Password')
            ->assertHasErrors(['password']);

        $this->resetPassword($plainToken, $user->email, '12345678')
            ->assertHasErrors(['password']);
    }

    public function test_old_password_cannot_login_and_new_password_can_login_after_reset(): void
    {
        $user = $this->user(['password' => Hash::make('Oldpass123')]);
        $plainToken = Str::random(80);
        $this->resetToken($user, $plainToken);

        $this->resetPassword($plainToken, $user->email, 'Newpass123');

        $this->assertFalse(Auth::attempt(['email' => $user->email, 'password' => 'Oldpass123']));
        $this->assertTrue(Auth::attempt(['email' => $user->email, 'password' => 'Newpass123']));
    }

    public function test_reset_password_does_not_work_with_legacy_user_uid_only(): void
    {
        $user = $this->user(['uid' => 'legacy-user-uid']);

        $this->get(route('password.reset', ['token' => $user->uid]))
            ->assertOk()
            ->assertSee('Link reset password tidak valid atau sudah kedaluwarsa.');

        $this->resetPassword($user->uid, $user->email, 'Newpass123')
            ->assertNoRedirect()
            ->assertSet('invalidLink', true);

        $this->assertFalse(Hash::check('Newpass123', $user->fresh()->password));
    }

    public function test_forgot_password_rate_limit_allows_three_requests_per_ten_minutes(): void
    {
        Mail::fake();
        $user = $this->user(['email' => 'reset@example.test']);

        for ($i = 0; $i < 3; $i++) {
            $this->travel(61)->seconds();

            Livewire::test(ForgotPassword::class)
                ->set('email', $user->email)
                ->call('submit')
                ->assertSet('email', '');
        }

        $this->travel(61)->seconds();

        Livewire::test(ForgotPassword::class)
            ->set('email', $user->email)
            ->call('submit')
            ->assertSet('email', '');

        Mail::assertSent(ForgotPasswordMail::class, 3);
        $this->assertDatabaseCount('forgot_passwords', 3);
    }

    private function user(array $overrides = []): User
    {
        return User::factory()->create(array_merge([
            'uid' => (string) Str::uuid(),
            'name' => 'Reset User',
            'email' => 'reset@example.test',
            'role' => 'user',
            'gambar' => '-',
            'nomor' => '-',
            'birthday' => '2000-01-01',
            'alamat' => '-',
            'kota' => '-',
            'gender' => 'pria',
            'password' => Hash::make('Oldpass123'),
        ], $overrides));
    }

    private function resetToken(User $user, string $plainToken, $expiresAt = null): ForgotPasswordToken
    {
        return ForgotPasswordToken::create([
            'uid' => Str::random(10),
            'uid_user' => $user->uid,
            'email' => $user->email,
            'token_hash' => hash('sha256', $plainToken),
            'expires_at' => $expiresAt ?? now()->addMinutes(30),
        ]);
    }

    private function resetPassword(string $plainToken, string $email, string $password)
    {
        return Livewire::test(ResetPassword::class, ['token' => $plainToken])
            ->set('email', $email)
            ->set('invalidLink', false)
            ->set('password', $password)
            ->set('password_confirmation', $password)
            ->call('resetPassword');
    }
}
