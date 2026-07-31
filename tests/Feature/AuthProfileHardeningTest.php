<?php

namespace Tests\Feature;

use App\Livewire\Auth\Login;
use App\Mail\ProfileEmailChangeOtpMail;
use App\Models\ProfileEmailChangeOtp;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class AuthProfileHardeningTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        cache()->flush();
        View::share('logo', [(object) ['logo' => '']]);
    }

    public function test_post_logout_logs_user_out(): void
    {
        $user = $this->user();

        $this->actingAs($user)
            ->post('/logout')
            ->assertRedirect('/');

        $this->assertGuest();
    }

    public function test_get_logout_does_not_log_user_out(): void
    {
        $user = $this->user();

        $this->actingAs($user)
            ->get('/logout')
            ->assertStatus(405);

        $this->assertAuthenticatedAs($user);
    }

    public function test_web_login_rate_limit_works(): void
    {
        $user = $this->user(['email' => 'limit@example.test', 'password' => Hash::make('Secret123')]);

        for ($i = 0; $i < 5; $i++) {
            Livewire::test(Login::class)
                ->set('email', $user->email)
                ->set('password', 'wrong-password')
                ->call('login');
        }

        Livewire::test(Login::class)
            ->set('email', $user->email)
            ->set('password', 'Secret123')
            ->call('login')
            ->assertNoRedirect();

        $this->assertGuest();
    }

    public function test_successful_web_login_clears_rate_limiter(): void
    {
        $user = $this->user(['email' => 'clear@example.test', 'password' => Hash::make('Secret123')]);
        $key = 'web-login:'.sha1($user->email.'|127.0.0.1');

        for ($i = 0; $i < 4; $i++) {
            Livewire::test(Login::class)
                ->set('email', $user->email)
                ->set('password', 'wrong-password')
                ->call('login');
        }

        Livewire::test(Login::class)
            ->set('email', $user->email)
            ->set('password', 'Secret123')
            ->call('login')
            ->assertRedirect('/');

        $this->assertFalse(RateLimiter::tooManyAttempts($key, 1));
    }

    public function test_staff_can_login_web_as_regular_buyer(): void
    {
        $staff = $this->user([
            'email' => 'staff-web@example.test',
            'role' => 'staff',
            'password' => Hash::make('Secret123'),
        ]);

        Livewire::test(Login::class)
            ->set('email', $staff->email)
            ->set('password', 'Secret123')
            ->call('login')
            ->assertRedirect('/');

        $this->assertAuthenticatedAs($staff);
    }

    public function test_staff_cannot_access_dashboard(): void
    {
        $staff = $this->user([
            'email' => 'staff-dashboard@example.test',
            'role' => 'staff',
        ]);

        $this->actingAs($staff)
            ->get('/dashboard')
            ->assertForbidden();

        $this->assertAuthenticatedAs($staff);
    }

    public function test_staff_can_access_profile_after_web_login(): void
    {
        Http::fake([
            'https://www.emsifa.com/*' => Http::response([
                ['id' => '31', 'name' => 'DKI Jakarta'],
            ]),
        ]);

        $staff = $this->user([
            'email' => 'staff-profile@example.test',
            'role' => 'staff',
        ]);

        $this->actingAs($staff)
            ->get('/profile')
            ->assertOk();
    }

    public function test_api_scanner_login_rate_limit_works(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/login', [
                'email' => 'scanner-limit@example.test',
                'password' => 'wrong-password',
            ])->assertUnauthorized();
        }

        $this->postJson('/api/login', [
            'email' => 'scanner-limit@example.test',
            'password' => 'wrong-password',
        ])->assertStatus(429);
    }

    public function test_regular_user_role_is_rejected_from_scanner_login(): void
    {
        $user = $this->user(['role' => 'user', 'password' => Hash::make('Secret123')]);

        $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'Secret123',
        ])->assertForbidden();
    }

    public function test_staff_without_parent_uid_is_rejected_from_scanner_login(): void
    {
        $staff = $this->user([
            'role' => 'staff',
            'parent_uid' => null,
            'password' => Hash::make('Secret123'),
        ]);

        $this->postJson('/api/login', [
            'email' => $staff->email,
            'password' => 'Secret123',
        ])->assertForbidden();
    }

    public function test_staff_with_parent_uid_can_login_to_scanner(): void
    {
        $owner = $this->user(['role' => 'penyewa']);
        $staff = $this->user([
            'email' => 'scanner-staff@example.test',
            'role' => 'staff',
            'parent_uid' => $owner->uid,
            'password' => Hash::make('Secret123'),
        ]);

        $this->postJson('/api/login', [
            'email' => $staff->email,
            'password' => 'Secret123',
        ])->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user.owner_uid', $owner->uid);
    }

    public function test_profile_update_cannot_change_email(): void
    {
        $user = $this->user(['email' => 'old@example.test']);

        $this->actingAs($user)
            ->post('/profile/update-profile', $this->profilePayload([
                'email' => 'new@example.test',
            ]))
            ->assertRedirect();

        $this->assertSame('old@example.test', $user->fresh()->email);
    }

    public function test_profile_update_cannot_change_password(): void
    {
        $user = $this->user(['password' => Hash::make('Oldpass123')]);

        $this->actingAs($user)
            ->post('/profile/update-profile', $this->profilePayload([
                'password' => 'Newpass123',
            ]))
            ->assertRedirect();

        $this->assertTrue(Hash::check('Oldpass123', $user->fresh()->password));
    }

    public function test_change_password_requires_current_password(): void
    {
        $user = $this->user(['password' => Hash::make('Oldpass123')]);

        $this->actingAs($user)
            ->post(route('profile.password.update'), [
                'password' => 'Newpass123',
                'password_confirmation' => 'Newpass123',
            ])
            ->assertSessionHasErrors('current_password');

        $this->assertTrue(Hash::check('Oldpass123', $user->fresh()->password));
    }

    public function test_change_password_succeeds_with_current_password(): void
    {
        $user = $this->user(['password' => Hash::make('Oldpass123')]);
        $apiToken = $user->createToken('scanner')->plainTextToken;

        $this->actingAs($user)
            ->post(route('profile.password.update'), [
                'current_password' => 'Oldpass123',
                'password' => 'Newpass123',
                'password_confirmation' => 'Newpass123',
            ])
            ->assertRedirect();

        $user->refresh();

        $this->assertTrue(Hash::check('Newpass123', $user->password));
        $this->assertDatabaseMissing('personal_access_tokens', [
            'token' => hash('sha256', explode('|', $apiToken, 2)[1]),
        ]);
    }

    public function test_request_email_otp_does_not_change_user_email(): void
    {
        Mail::fake();
        $user = $this->user(['email' => 'old@example.test']);

        $this->actingAs($user)
            ->post(route('profile.email.request-otp'), [
                'new_email' => 'new@example.test',
            ])
            ->assertRedirect()
            ->assertSessionHasInput('new_email', 'new@example.test');

        $this->assertSame('old@example.test', $user->fresh()->email);
        $this->assertDatabaseHas('profile_email_change_otps', [
            'user_uid' => $user->uid,
            'new_email' => 'new@example.test',
            'used_at' => null,
        ]);
        Mail::assertSent(ProfileEmailChangeOtpMail::class);
    }

    public function test_wrong_email_otp_is_rejected_and_attempts_increment(): void
    {
        $user = $this->user();
        $otp = $this->otp($user, 'new@example.test', '123456');

        $this->actingAs($user)
            ->post(route('profile.email.verify-otp'), [
                'new_email' => 'new@example.test',
                'otp' => '000000',
            ])
            ->assertSessionHasErrors('otp');

        $this->assertSame(1, $otp->fresh()->attempts);
        $this->assertNotSame('new@example.test', $user->fresh()->email);
    }

    public function test_expired_email_otp_is_rejected(): void
    {
        $user = $this->user();
        $this->otp($user, 'new@example.test', '123456', now()->subMinute());

        $this->actingAs($user)
            ->post(route('profile.email.verify-otp'), [
                'new_email' => 'new@example.test',
                'otp' => '123456',
            ])
            ->assertSessionHasErrors('otp');

        $this->assertNotSame('new@example.test', $user->fresh()->email);
    }

    public function test_valid_email_otp_changes_email_and_sets_verified_at(): void
    {
        $user = $this->user(['email_verified_at' => null]);
        $this->otp($user, 'new@example.test', '123456');

        $this->actingAs($user)
            ->post(route('profile.email.verify-otp'), [
                'new_email' => 'new@example.test',
                'otp' => '123456',
            ])
            ->assertRedirect();

        $user->refresh();

        $this->assertSame('new@example.test', $user->email);
        $this->assertNotNull($user->email_verified_at);
    }

    public function test_email_otp_cannot_be_used_twice(): void
    {
        $user = $this->user();
        $otp = $this->otp($user, 'new@example.test', '123456');

        $this->actingAs($user)
            ->post(route('profile.email.verify-otp'), [
                'new_email' => 'new@example.test',
                'otp' => '123456',
            ]);

        $this->actingAs($user)
            ->post(route('profile.email.verify-otp'), [
                'new_email' => 'new@example.test',
                'otp' => '123456',
            ])
            ->assertSessionHasErrors('otp');

        $this->assertNotNull($otp->fresh()->used_at);
    }

    public function test_new_email_already_used_is_rejected(): void
    {
        Mail::fake();
        $user = $this->user(['email' => 'old@example.test']);
        $this->user(['email' => 'taken@example.test']);

        $this->actingAs($user)
            ->post(route('profile.email.request-otp'), [
                'new_email' => 'taken@example.test',
            ])
            ->assertSessionHasErrors('new_email');

        $this->assertDatabaseCount('profile_email_change_otps', 0);
        Mail::assertNothingSent();
    }

    private function user(array $overrides = []): User
    {
        return User::factory()->create(array_merge([
            'uid' => (string) Str::uuid(),
            'name' => 'Hardening User',
            'email' => fake()->unique()->safeEmail(),
            'role' => 'user',
            'gambar' => '-',
            'nomor' => '08123456789',
            'birthday' => '2000-01-01',
            'alamat' => 'Alamat awal',
            'kota' => 'Jakarta',
            'gender' => 'pria',
            'password' => Hash::make('Password123'),
        ], $overrides));
    }

    private function profilePayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Nama Baru',
            'nomor' => '0811111111',
            'gender' => 'pria',
            'birthday' => '2000-01-01',
            'kota' => 'Jakarta',
            'alamat' => 'Alamat baru',
        ], $overrides);
    }

    private function otp(User $user, string $newEmail, string $plainOtp, $expiresAt = null): ProfileEmailChangeOtp
    {
        return ProfileEmailChangeOtp::create([
            'user_uid' => $user->uid,
            'current_email' => $user->email,
            'new_email' => $newEmail,
            'otp_hash' => hash('sha256', $plainOtp),
            'purpose' => ProfileEmailChangeOtp::PURPOSE,
            'expires_at' => $expiresAt ?? now()->addMinutes(10),
            'last_sent_at' => now(),
        ]);
    }
}
