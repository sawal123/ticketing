<?php

namespace Tests\Feature;

use App\Http\Middleware\GlobalDataMiddleware;
use App\Http\Middleware\LogActivityMiddleware;
use App\Livewire\Dashboard\SettingsIndex;
use App\Models\Bank;
use App\Models\ProfileEmailChangeOtp;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class AccountBankSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([GlobalDataMiddleware::class, LogActivityMiddleware::class]);
        View::share('logo', [(object) ['logo' => '']]);
    }

    public function test_legacy_profile_ignores_sensitive_account_fields(): void
    {
        $oldPassword = Hash::make('Password123');
        $tenant = $this->user([
            'email' => 'tenant@example.test',
            'email_verified_at' => now()->subYear(),
            'password' => $oldPassword,
            'remember_token' => 'old-token',
            'google_id' => null,
        ]);
        $other = $this->user(['role' => 'penyewa']);

        $this->actingAs($tenant)
            ->post('/dashboard/old/editProfile', $this->legacyProfilePayload([
                'uid' => $other->uid,
                'email' => 'attacker@example.test',
                'role' => 'admin',
                'parent_uid' => $other->uid,
                'email_verified_at' => now()->addYear()->toDateTimeString(),
                'google_id' => 'google-attacker',
                'google_token' => 'token-attacker',
                'remember_token' => 'remember-attacker',
                'password' => 'Injected123',
            ]))
            ->assertRedirect();

        $tenant->refresh();

        $this->assertSame('Nama Aman', $tenant->name);
        $this->assertSame('tenant@example.test', $tenant->email);
        $this->assertSame('penyewa', $tenant->role);
        $this->assertNull($tenant->parent_uid);
        $this->assertNull($tenant->google_id);
        $this->assertSame('old-token', $tenant->remember_token);
        $this->assertSame($oldPassword, $tenant->password);
        $this->assertTrue($tenant->email_verified_at->isSameDay(now()->subYear()));
    }

    public function test_user_cannot_update_another_profile_with_spoofed_uid(): void
    {
        $tenantA = $this->user(['email' => 'tenant-a@example.test']);
        $tenantB = $this->user(['email' => 'tenant-b@example.test', 'name' => 'Tenant B']);

        $this->actingAs($tenantA)
            ->post('/dashboard/old/editProfile', $this->legacyProfilePayload([
                'uid' => $tenantB->uid,
                'nama' => 'Tenant A Updated',
                'email' => 'tenant-b-hijack@example.test',
            ]))
            ->assertRedirect();

        $this->assertSame('Tenant A Updated', $tenantA->fresh()->name);
        $this->assertSame('Tenant B', $tenantB->fresh()->name);
        $this->assertSame('tenant-b@example.test', $tenantB->fresh()->email);
    }

    public function test_livewire_settings_profile_ignores_email_mutation(): void
    {
        $tenant = $this->user(['email' => 'settings@example.test']);

        Livewire::actingAs($tenant)
            ->test(SettingsIndex::class)
            ->set('email', 'changed@example.test')
            ->set('name', 'Settings Updated')
            ->set('nomor', '081222333444')
            ->call('updateProfile')
            ->assertHasNoErrors();

        $tenant->refresh();

        $this->assertSame('settings@example.test', $tenant->email);
        $this->assertSame('Settings Updated', $tenant->name);
    }

    public function test_email_can_only_change_with_valid_otp(): void
    {
        $tenant = $this->user(['email' => 'old@example.test', 'email_verified_at' => null]);
        $this->otp($tenant, 'new@example.test', '123456');

        $this->actingAs($tenant)
            ->post(route('profile.email.verify-otp'), [
                'new_email' => 'new@example.test',
                'otp' => '123456',
            ])
            ->assertRedirect();

        $tenant->refresh();

        $this->assertSame('new@example.test', $tenant->email);
        $this->assertNotNull($tenant->email_verified_at);
    }

    public function test_wrong_email_otp_does_not_change_email(): void
    {
        $tenant = $this->user(['email' => 'old@example.test']);
        $this->otp($tenant, 'new@example.test', '123456');

        $this->actingAs($tenant)
            ->post(route('profile.email.verify-otp'), [
                'new_email' => 'new@example.test',
                'otp' => '000000',
            ])
            ->assertSessionHasErrors('otp');

        $this->assertSame('old@example.test', $tenant->fresh()->email);
    }

    public function test_existing_email_and_google_account_are_rejected_for_email_otp(): void
    {
        Mail::fake();
        $this->user(['email' => 'taken@example.test']);
        $tenant = $this->user([
            'email' => 'google@example.test',
            'google_id' => 'google-id',
        ]);

        $this->actingAs($tenant)
            ->post(route('profile.email.request-otp'), [
                'new_email' => 'taken@example.test',
            ])
            ->assertSessionHasErrors('new_email');

        $this->assertDatabaseCount('profile_email_change_otps', 0);
        Mail::assertNothingSent();
    }

    public function test_legacy_password_requires_current_password(): void
    {
        $tenant = $this->user(['password' => Hash::make('Oldpass123')]);

        $this->actingAs($tenant)
            ->post('/dashboard/old/updatePassword', [
                'current_password' => 'Wrongpass123',
                'new_password' => 'Newpass123',
                'new_password_confirmation' => 'Newpass123',
            ])
            ->assertSessionHasErrors('current_password');

        $this->assertTrue(Hash::check('Oldpass123', $tenant->fresh()->password));
    }

    public function test_legacy_password_requires_confirmation_and_strength(): void
    {
        $tenant = $this->user(['password' => Hash::make('Oldpass123')]);

        $this->actingAs($tenant)
            ->post('/dashboard/old/updatePassword', [
                'current_password' => 'Oldpass123',
                'new_password' => 'weakpass',
                'new_password_confirmation' => 'different',
            ])
            ->assertSessionHasErrors(['new_password']);

        $this->assertTrue(Hash::check('Oldpass123', $tenant->fresh()->password));
    }

    public function test_legacy_password_success_revokes_tokens_and_ignores_spoofed_uid(): void
    {
        $tenant = $this->user([
            'password' => Hash::make('Oldpass123'),
            'remember_token' => 'old-token',
        ]);
        $other = $this->user(['password' => Hash::make('Otherpass123')]);
        $apiToken = $tenant->createToken('scanner')->plainTextToken;

        $this->actingAs($tenant)
            ->post('/dashboard/old/updatePassword', [
                'uid' => $other->uid,
                'current_password' => 'Oldpass123',
                'new_password' => 'Newpass123',
                'new_password_confirmation' => 'Newpass123',
            ])
            ->assertRedirect();

        $tenant->refresh();

        $this->assertTrue(Hash::check('Newpass123', $tenant->password));
        $this->assertFalse(Hash::check('Newpass123', $other->fresh()->password));
        $this->assertNotSame('old-token', $tenant->remember_token);
        $this->assertDatabaseMissing('personal_access_tokens', [
            'token' => hash('sha256', explode('|', $apiToken, 2)[1]),
        ]);
    }

    public function test_legacy_bank_requires_current_password_and_keeps_existing_data_on_failure(): void
    {
        $tenant = $this->user(['password' => Hash::make('Password123')]);
        $bank = $this->bank($tenant, [
            'nama' => 'Nama Lama',
            'bank' => 'BCA',
            'norek' => '111222333',
        ]);

        $this->actingAs($tenant)
            ->post('/dashboard/old/editRekening', [
                'current_password' => 'Wrongpass123',
                'nama' => 'Nama Baru',
                'bank' => 'Mandiri',
                'norek' => '999888777',
            ])
            ->assertSessionHasErrors('current_password');

        $bank->refresh();

        $this->assertSame('Nama Lama', $bank->nama);
        $this->assertSame('BCA', $bank->bank);
        $this->assertSame('111222333', $bank->norek);
    }

    public function test_legacy_bank_updates_only_authenticated_tenant_and_ignores_spoofed_owner_fields(): void
    {
        $tenantA = $this->user(['password' => Hash::make('Password123')]);
        $tenantB = $this->user(['password' => Hash::make('Password123')]);
        $bankA = $this->bank($tenantA);
        $bankB = $this->bank($tenantB, ['nama' => 'Tenant B', 'bank' => 'BNI', 'norek' => '444555666']);

        $this->actingAs($tenantA)
            ->post('/dashboard/old/editRekening', [
                'uid' => $tenantB->uid,
                'uid_user' => $tenantB->uid,
                'status' => 'verified',
                'verifikasi' => '1',
                'current_password' => 'Password123',
                'nama' => 'Tenant A Baru',
                'bank' => 'Mandiri',
                'norek' => '123456789',
            ])
            ->assertRedirect();

        $bankA->refresh();
        $bankB->refresh();

        $this->assertSame($tenantA->uid, $bankA->uid);
        $this->assertSame($tenantA->uid, $bankA->uid_user);
        $this->assertSame('Tenant A Baru', $bankA->nama);
        $this->assertSame('Mandiri', $bankA->bank);
        $this->assertSame('123456789', $bankA->norek);
        $this->assertSame('Tenant B', $bankB->nama);
        $this->assertSame('BNI', $bankB->bank);
        $this->assertSame('444555666', $bankB->norek);
    }

    public function test_bank_account_number_must_be_numeric(): void
    {
        $tenant = $this->user(['password' => Hash::make('Password123')]);
        $bank = $this->bank($tenant, ['norek' => '111222333']);

        $this->actingAs($tenant)
            ->post('/dashboard/old/editRekening', [
                'current_password' => 'Password123',
                'nama' => 'Nama Baru',
                'bank' => 'Mandiri',
                'norek' => 'ABC123',
            ])
            ->assertSessionHasErrors('norek');

        $this->assertSame('111222333', $bank->fresh()->norek);
    }

    public function test_livewire_bank_requires_current_password(): void
    {
        $tenant = $this->user(['password' => Hash::make('Password123')]);

        Livewire::actingAs($tenant)
            ->test(SettingsIndex::class)
            ->set('nama_rekening', 'Nama Baru')
            ->set('bank_name', 'BCA')
            ->set('nomor_rekening', '123456789')
            ->set('bank_current_password', 'Wrongpass123')
            ->call('saveBank')
            ->assertHasErrors(['bank_current_password']);

        $this->assertDatabaseCount('banks', 0);
    }

    public function test_user_can_delete_own_bank_with_correct_password(): void
    {
        $tenant = $this->user(['password' => Hash::make('Password123')]);
        $bank = $this->bank($tenant);

        Livewire::actingAs($tenant)
            ->test(SettingsIndex::class)
            ->call('confirmDeleteBank', $bank->id)
            ->set('deleteBankPassword', 'Password123')
            ->call('deleteBank')
            ->assertHasNoErrors()
            ->assertSet('deleteBankPassword', null);

        $this->assertSoftDeleted('banks', [
            'id' => $bank->id,
        ]);
        $this->assertNull(Bank::query()->find($bank->id));
    }

    public function test_user_cannot_delete_bank_with_empty_password(): void
    {
        $tenant = $this->user(['password' => Hash::make('Password123')]);
        $bank = $this->bank($tenant);

        Livewire::actingAs($tenant)
            ->test(SettingsIndex::class)
            ->call('confirmDeleteBank', $bank->id)
            ->set('deleteBankPassword', '')
            ->call('deleteBank')
            ->assertHasErrors('deleteBankPassword')
            ->assertSet('deleteBankPassword', null);

        $this->assertDatabaseHas('banks', ['id' => $bank->id]);
    }

    public function test_user_cannot_delete_bank_with_wrong_password(): void
    {
        $tenant = $this->user(['password' => Hash::make('Password123')]);
        $bank = $this->bank($tenant);

        Livewire::actingAs($tenant)
            ->test(SettingsIndex::class)
            ->call('confirmDeleteBank', $bank->id)
            ->set('deleteBankPassword', 'Wrongpass123')
            ->call('deleteBank')
            ->assertHasErrors('deleteBankPassword')
            ->assertSet('deleteBankPassword', null);

        $this->assertDatabaseHas('banks', ['id' => $bank->id]);
    }

    public function test_user_cannot_delete_other_tenant_bank_even_with_correct_password(): void
    {
        $tenantA = $this->user(['password' => Hash::make('Password123')]);
        $tenantB = $this->user(['password' => Hash::make('Password123')]);
        $bankB = $this->bank($tenantB);

        Livewire::actingAs($tenantA)
            ->test(SettingsIndex::class)
            ->set('deletingBankId', $bankB->id)
            ->set('deleteBankPassword', 'Password123')
            ->call('deleteBank')
            ->assertHasErrors('deleteBankPassword')
            ->assertSet('deleteBankPassword', null);

        $this->assertDatabaseHas('banks', ['id' => $bankB->id]);
    }

    public function test_staff_cannot_edit_bank_route(): void
    {
        $owner = $this->user(['role' => 'penyewa']);
        $staff = $this->user([
            'role' => 'staff',
            'parent_uid' => $owner->uid,
            'password' => Hash::make('Password123'),
        ]);
        $bank = $this->bank($owner, ['nama' => 'Owner Name']);

        $this->actingAs($staff)
            ->post('/dashboard/old/editRekening', [
                'current_password' => 'Password123',
                'nama' => 'Staff Name',
                'bank' => 'BCA',
                'norek' => '123456789',
            ])
            ->assertForbidden();

        $this->assertSame('Owner Name', $bank->fresh()->nama);
    }

    private function user(array $overrides = []): User
    {
        return User::factory()->create(array_merge([
            'uid' => (string) Str::uuid(),
            'name' => 'Tenant User',
            'email' => fake()->unique()->safeEmail(),
            'role' => 'penyewa',
            'parent_uid' => null,
            'gambar' => '-',
            'nomor' => '08123456789',
            'birthday' => '2000-01-01',
            'alamat' => 'Alamat awal',
            'kota' => 'Jakarta',
            'gender' => 'pria',
            'password' => Hash::make('Password123'),
        ], $overrides));
    }

    private function bank(User $tenant, array $overrides = []): Bank
    {
        return Bank::create(array_merge([
            'uid' => $tenant->uid,
            'uid_user' => $tenant->uid,
            'nama' => 'Nama Rekening',
            'bank' => 'BCA',
            'norek' => '123456789',
        ], $overrides));
    }

    private function legacyProfilePayload(array $overrides = []): array
    {
        return array_merge([
            'nama' => 'Nama Aman',
            'nomor' => '081111111111',
            'date' => '1999-01-01',
            'gender' => 'pria',
            'provinsi' => 'Jakarta',
            'alamat' => 'Alamat aman',
        ], $overrides);
    }

    private function otp(User $user, string $newEmail, string $plainOtp): ProfileEmailChangeOtp
    {
        return ProfileEmailChangeOtp::create([
            'user_uid' => $user->uid,
            'current_email' => $user->email,
            'new_email' => $newEmail,
            'otp_hash' => hash('sha256', $plainOtp),
            'purpose' => ProfileEmailChangeOtp::PURPOSE,
            'expires_at' => now()->addMinutes(10),
            'last_sent_at' => now(),
        ]);
    }
}
