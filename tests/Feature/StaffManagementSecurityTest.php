<?php

namespace Tests\Feature;

use App\Http\Middleware\GlobalDataMiddleware;
use App\Http\Middleware\LogActivityMiddleware;
use App\Livewire\Dashboard\StaffIndex;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class StaffManagementSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        View::share('logo', [(object) ['logo' => '']]);
        $this->withoutMiddleware([GlobalDataMiddleware::class, LogActivityMiddleware::class]);
        Queue::fake();
    }

    public function test_tenant_only_sees_staff_with_own_parent_uid(): void
    {
        $tenantA = $this->user(['role' => 'penyewa', 'email' => 'tenant-a@example.test']);
        $tenantB = $this->user(['role' => 'penyewa', 'email' => 'tenant-b@example.test']);
        $staffA = $this->staff($tenantA, ['name' => 'Staff Tenant A', 'email' => 'staff-a@example.test']);
        $staffB = $this->staff($tenantB, ['name' => 'Staff Tenant B', 'email' => 'staff-b@example.test']);

        Livewire::actingAs($tenantA)
            ->test(StaffIndex::class)
            ->assertSee($staffA->name)
            ->assertDontSee($staffB->name);
    }

    public function test_tenant_cannot_edit_another_tenants_staff(): void
    {
        $tenantA = $this->user(['role' => 'penyewa', 'email' => 'tenant-a-edit@example.test']);
        $tenantB = $this->user(['role' => 'penyewa', 'email' => 'tenant-b-edit@example.test']);
        $staffB = $this->staff($tenantB, ['name' => 'Original Staff B']);

        $this->actingAs($tenantA)
            ->from('/dashboard/old/staff')
            ->put('/dashboard/old/staff/'.$staffB->uid, [
                'name' => 'Hijacked Staff',
                'email' => 'hijacked@example.test',
                'role' => 'admin',
                'parent_uid' => $tenantA->uid,
            ])
            ->assertNotFound();

        $staffB->refresh();

        $this->assertSame('Original Staff B', $staffB->name);
        $this->assertSame($tenantB->uid, $staffB->parent_uid);
        $this->assertSame(User::STAFF_ROLE, $staffB->role);
    }

    public function test_tenant_cannot_delete_another_tenants_staff(): void
    {
        $tenantA = $this->user(['role' => 'penyewa', 'email' => 'tenant-a-delete@example.test']);
        $tenantB = $this->user(['role' => 'penyewa', 'email' => 'tenant-b-delete@example.test']);
        $staffB = $this->staff($tenantB);

        $this->actingAs($tenantA)
            ->delete(route('dashboard.old.staff.destroy', $staffB->uid))
            ->assertNotFound();

        $this->assertNotSoftDeleted('users', ['id' => $staffB->id]);
    }

    public function test_tenant_can_edit_own_staff_allowed_fields_only(): void
    {
        $tenant = $this->user(['role' => 'penyewa']);
        $otherTenant = $this->user(['role' => 'penyewa']);
        $staff = $this->staff($tenant, [
            'email' => 'own-staff@example.test',
            'nomor' => '0800000000',
            'password' => Hash::make('Oldpass123'),
        ]);
        $oldPassword = $staff->password;

        $this->actingAs($tenant)
            ->from('/dashboard/old/staff')
            ->put('/dashboard/old/staff/'.$staff->uid, [
                'name' => 'Updated Staff',
                'nomor' => '0811111111',
                'email' => 'changed@example.test',
                'role' => 'admin',
                'parent_uid' => $otherTenant->uid,
                'password' => 'Newpass123',
                'email_verified_at' => now()->subDay()->toDateTimeString(),
            ])
            ->assertRedirect('/dashboard/old/staff');

        $staff->refresh();

        $this->assertSame('Updated Staff', $staff->name);
        $this->assertSame('0811111111', $staff->nomor);
        $this->assertSame('own-staff@example.test', $staff->email);
        $this->assertSame(User::STAFF_ROLE, $staff->role);
        $this->assertSame($tenant->uid, $staff->parent_uid);
        $this->assertSame($oldPassword, $staff->password);
    }

    public function test_tenant_can_delete_own_staff(): void
    {
        $tenant = $this->user(['role' => 'penyewa']);
        $staff = $this->staff($tenant);

        $this->actingAs($tenant)
            ->delete(route('dashboard.old.staff.destroy', $staff->uid))
            ->assertRedirect();

        $this->assertSoftDeleted('users', ['id' => $staff->id]);
    }

    public function test_create_staff_cannot_spoof_parent_uid_or_role(): void
    {
        $tenantA = $this->user(['role' => 'penyewa', 'email' => 'tenant-a-create@example.test']);
        $tenantB = $this->user(['role' => 'penyewa', 'email' => 'tenant-b-create@example.test']);

        $this->actingAs($tenantA)
            ->from('/dashboard/old/staff')
            ->post('/dashboard/old/staff', [
                'name' => 'New Staff',
                'email' => 'new-staff@example.test',
                'nomor' => '0812222222',
                'role' => 'admin',
                'parent_uid' => $tenantB->uid,
                'status' => 'active',
            ])
            ->assertRedirect('/dashboard/old/staff');

        $staff = User::where('email', 'new-staff@example.test')->firstOrFail();

        $this->assertSame(User::STAFF_ROLE, $staff->role);
        $this->assertSame($tenantA->uid, $staff->parent_uid);
        $this->assertNull($staff->email_verified_at);
    }

    public function test_existing_user_is_not_converted_to_staff(): void
    {
        $tenant = $this->user(['role' => 'penyewa']);
        $buyer = $this->user([
            'role' => 'user',
            'email' => 'buyer-existing@example.test',
            'parent_uid' => null,
            'password' => Hash::make('Buyerpass123'),
        ]);
        $oldPassword = $buyer->password;

        $this->actingAs($tenant)
            ->from('/dashboard/old/staff')
            ->post('/dashboard/old/staff', [
                'name' => 'Convert Buyer',
                'email' => $buyer->email,
                'role' => 'staff',
                'parent_uid' => $tenant->uid,
            ])
            ->assertRedirect('/dashboard/old/staff')
            ->assertSessionHasErrors('email');

        $buyer->refresh();

        $this->assertSame(User::USER_ROLE, $buyer->role);
        $this->assertNull($buyer->parent_uid);
        $this->assertSame($oldPassword, $buyer->password);
    }

    public function test_staff_email_from_another_tenant_cannot_be_reused_or_moved(): void
    {
        $tenantA = $this->user(['role' => 'penyewa', 'email' => 'tenant-a-reuse@example.test']);
        $tenantB = $this->user(['role' => 'penyewa', 'email' => 'tenant-b-reuse@example.test']);
        $staffB = $this->staff($tenantB, ['email' => 'shared-staff@example.test']);

        $this->actingAs($tenantA)
            ->from('/dashboard/old/staff')
            ->post('/dashboard/old/staff', [
                'name' => 'Move Staff',
                'email' => $staffB->email,
            ])
            ->assertRedirect('/dashboard/old/staff')
            ->assertSessionHasErrors('email');

        $this->assertSame($tenantB->uid, $staffB->fresh()->parent_uid);
        $this->assertSame(1, User::where('email', $staffB->email)->count());
    }

    public function test_staff_cannot_access_dashboard_or_staff_management(): void
    {
        $tenant = $this->user(['role' => 'penyewa']);
        $staff = $this->staff($tenant);

        $this->actingAs($staff)
            ->get('/dashboard')
            ->assertForbidden();

        $this->actingAs($staff)
            ->get('/dashboard/old/staff')
            ->assertForbidden();

        $this->actingAs($staff)
            ->get('/dashboard/staff-index')
            ->assertForbidden();
    }

    public function test_staff_with_parent_uid_can_still_login_to_scanner_api(): void
    {
        $tenant = $this->user(['role' => 'penyewa']);
        $staff = $this->staff($tenant, [
            'email' => 'scanner-staff-management@example.test',
            'password' => Hash::make('Secret123'),
            'email_verified_at' => now(),
        ]);

        $this->postJson('/api/login', [
            'email' => $staff->email,
            'password' => 'Secret123',
        ])->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user.owner_uid', $tenant->uid);
    }

    public function test_get_legacy_staff_delete_does_not_delete_staff(): void
    {
        $tenant = $this->user(['role' => 'penyewa']);
        $staff = $this->staff($tenant);

        $response = $this->actingAs($tenant)
            ->get('/dashboard/old/staff/delete/'.$staff->uid);

        $this->assertContains($response->getStatusCode(), [404, 405]);
        $this->assertNotSoftDeleted('users', ['id' => $staff->id]);
    }

    public function test_delete_legacy_staff_delete_only_deletes_own_staff(): void
    {
        $tenantA = $this->user(['role' => 'penyewa', 'email' => 'tenant-a-legacy-delete@example.test']);
        $tenantB = $this->user(['role' => 'penyewa', 'email' => 'tenant-b-legacy-delete@example.test']);
        $staffA = $this->staff($tenantA);
        $staffB = $this->staff($tenantB);

        $this->actingAs($tenantA)
            ->delete(route('dashboard.old.staff.destroy', $staffB->uid))
            ->assertNotFound();

        $this->assertNotSoftDeleted('users', ['id' => $staffB->id]);

        $this->actingAs($tenantA)
            ->delete(route('dashboard.old.staff.destroy', $staffA->uid))
            ->assertRedirect();

        $this->assertSoftDeleted('users', ['id' => $staffA->id]);
    }

    private function staff(User $tenant, array $overrides = []): User
    {
        return $this->user(array_merge([
            'role' => User::STAFF_ROLE,
            'parent_uid' => $tenant->uid,
            'email_verified_at' => now(),
        ], $overrides));
    }

    private function user(array $overrides = []): User
    {
        return User::factory()->create(array_merge([
            'uid' => (string) Str::uuid(),
            'name' => 'Staff Security User',
            'email' => fake()->unique()->safeEmail(),
            'role' => User::USER_ROLE,
            'parent_uid' => null,
            'gambar' => '-',
            'nomor' => '08123456789',
            'birthday' => '2000-01-01',
            'alamat' => 'Alamat',
            'kota' => 'Jakarta',
            'gender' => 'pria',
            'password' => Hash::make('Password123'),
        ], $overrides));
    }
}
