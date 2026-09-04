<?php

namespace Tests\Feature;

use App\Http\Middleware\GlobalDataMiddleware;
use App\Http\Middleware\LogActivityMiddleware;
use App\Livewire\Admin\MarketingGuideIndex;
use App\Models\MarketingGuideAccess;
use App\Models\User;
use App\Services\MarketingGuide\MarketingGuideAccessService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class AdminMarketingGuideLinksTest extends TestCase
{
    use RefreshDatabase;

    private MarketingGuideAccessService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            GlobalDataMiddleware::class,
            LogActivityMiddleware::class,
        ]);

        $this->service = app(MarketingGuideAccessService::class);
        Carbon::setTestNow('2026-09-04 12:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_admin_can_open_management_page(): void
    {
        $admin = $this->user('admin');

        $this->actingAs($admin)
            ->get(route('admin.marketing-guide'))
            ->assertOk()
            ->assertSee('Marketing Guide', false)
            ->assertSee('Buat Link', false);
    }

    public function test_non_admin_cannot_open_management_page(): void
    {
        $tenant = $this->user('penyewa');
        $user = $this->user('user');

        $this->actingAs($tenant)
            ->get(route('admin.marketing-guide'))
            ->assertRedirect('/');

        $this->actingAs($user)
            ->get(route('admin.marketing-guide'))
            ->assertRedirect('/');

        auth()->logout();
        $this->assertGuest();

        $this->get(route('admin.marketing-guide'))
            ->assertRedirect(route('login'));
    }

    public function test_admin_can_generate_link_with_hash_only_storage_and_working_url(): void
    {
        $admin = $this->user('admin');

        $component = Livewire::actingAs($admin)
            ->test(MarketingGuideIndex::class)
            ->set('recipient_name', 'PT Acme')
            ->set('duration_days', 7)
            ->call('generateLink')
            ->assertHasNoErrors()
            ->assertSet('generatedUrl', fn ($url) => is_string($url) && str_contains($url, '/guide/'));

        $url = $component->get('generatedUrl');
        $access = MarketingGuideAccess::query()->latest('id')->first();

        $this->assertNotNull($access);
        $this->assertSame('PT Acme', $access->recipient_name);
        $this->assertSame(64, strlen($access->getAttributes()['token_hash']));
        $this->assertSame(0, (int) $access->access_count);

        $this->assertMatchesRegularExpression('#/guide/([A-Za-z0-9_-]{43})$#', $url);
        preg_match('#/guide/([A-Za-z0-9_-]{43})$#', $url, $matches);
        $plainToken = $matches[1];

        $row = DB::table('marketing_guide_accesses')->where('id', $access->id)->first();
        $this->assertSame(hash('sha256', $plainToken), $row->token_hash);
        $this->assertNotSame($plainToken, $row->token_hash);
        foreach ((array) $row as $value) {
            if (is_string($value)) {
                $this->assertStringNotContainsString($plainToken, $value);
            }
        }

        $this->get($url)
            ->assertOk()
            ->assertSee('Cara Kerja Gotik', false);
    }

    public function test_admin_can_extend_expires_at_without_clearing_revoked_at(): void
    {
        $admin = $this->user('admin');
        $created = $this->service->create($admin, now()->addDays(3), 'Extend Target');
        $access = $created['access'];
        $originalExpiry = $access->expires_at->copy();

        Livewire::actingAs($admin)
            ->test(MarketingGuideIndex::class)
            ->call('openExtendModal', $access->id)
            ->set('extend_days', 7)
            ->call('extendLink')
            ->assertHasNoErrors();

        $access->refresh();
        $this->assertTrue($access->expires_at->equalTo($originalExpiry->copy()->addDays(7)));
        $this->assertNull($access->revoked_at);
        $this->assertSame('Active', $this->service->displayStatus($access));

        $this->service->revoke($access);
        $revokedAt = $access->fresh()->revoked_at->copy();
        $expiryBefore = $access->fresh()->expires_at->copy();

        Livewire::actingAs($admin)
            ->test(MarketingGuideIndex::class)
            ->call('openExtendModal', $access->id)
            ->set('extend_days', 3)
            ->call('extendLink')
            ->assertHasNoErrors();

        $access->refresh();
        $this->assertNotNull($access->revoked_at);
        $this->assertTrue($access->revoked_at->equalTo($revokedAt));
        $this->assertTrue($access->expires_at->equalTo($expiryBefore->copy()->addDays(3)));
        $this->assertSame('Revoked', $this->service->displayStatus($access));
        $this->assertFalse($access->isValid());
    }

    public function test_revoke_makes_link_unusable_and_status_labels_are_correct(): void
    {
        $admin = $this->user('admin');
        $active = $this->service->create($admin, now()->addDay(), 'Active One');
        $expired = $this->service->create($admin, now()->addMinutes(5), 'Expired One');
        $revoked = $this->service->create($admin, now()->addDay(), 'Revoked One');

        $this->assertSame('Active', $this->service->displayStatus($active['access']));

        Carbon::setTestNow(now()->addHour());
        $this->assertSame('Expired', $this->service->displayStatus($expired['access']->fresh()));

        Livewire::actingAs($admin)
            ->test(MarketingGuideIndex::class)
            ->call('revokeLink', $revoked['access']->id)
            ->assertHasNoErrors();

        $revokedAccess = $revoked['access']->fresh();
        $this->assertNotNull($revokedAccess->revoked_at);
        $this->assertSame('Revoked', $this->service->displayStatus($revokedAccess));

        $this->get(route('marketing-guide.show', ['token' => $revoked['token']]))
            ->assertNotFound();

        $this->get(route('marketing-guide.show', ['token' => $expired['token']]))
            ->assertStatus(410)
            ->assertSee('Akses panduan telah berakhir', false);

        $this->get(route('marketing-guide.show', ['token' => $active['token']]))
            ->assertOk();
    }

    public function test_regenerate_creates_new_token_without_recovering_old_plain_token(): void
    {
        $admin = $this->user('admin');
        $old = $this->service->create($admin, now()->addDay(), 'Old Recipient');
        $oldId = $old['access']->id;
        $oldToken = $old['token'];

        $component = Livewire::actingAs($admin)
            ->test(MarketingGuideIndex::class)
            ->call('regenerateLink', $oldId)
            ->assertHasNoErrors();

        $newUrl = $component->get('generatedUrl');
        $this->assertIsString($newUrl);
        $this->assertStringNotContainsString($oldToken, $newUrl);

        preg_match('#/guide/([A-Za-z0-9_-]{43})$#', $newUrl, $matches);
        $newToken = $matches[1] ?? null;
        $this->assertNotNull($newToken);
        $this->assertNotSame($oldToken, $newToken);

        $this->assertDatabaseCount('marketing_guide_accesses', 2);
        $newAccess = MarketingGuideAccess::query()->where('id', '!=', $oldId)->first();
        $this->assertSame('Old Recipient', $newAccess->recipient_name);
        $this->assertSame(hash('sha256', $newToken), $newAccess->getAttributes()['token_hash']);

        $this->get(route('marketing-guide.show', ['token' => $newToken]))->assertOk();
        $this->get(route('marketing-guide.show', ['token' => $oldToken]))->assertOk();
    }

    public function test_plain_token_never_persisted_after_admin_generate(): void
    {
        $admin = $this->user('admin');

        $component = Livewire::actingAs($admin)
            ->test(MarketingGuideIndex::class)
            ->set('duration_days', 1)
            ->call('generateLink');

        preg_match('#/guide/([A-Za-z0-9_-]{43})$#', $component->get('generatedUrl'), $matches);
        $token = $matches[1];

        $serialized = serialize(MarketingGuideAccess::query()->first()->toArray());
        $this->assertStringNotContainsString($token, $serialized);

        $json = MarketingGuideAccess::query()->first()->toJson();
        $this->assertStringNotContainsString($token, $json);
        $this->assertStringNotContainsString('token_hash', $json);
    }

    private function user(string $role = 'admin', array $overrides = []): User
    {
        return User::factory()->create(array_merge([
            'uid' => (string) Str::uuid(),
            'name' => 'MG2 '.$role,
            'email' => 'mg2-'.$role.'-'.Str::random(8).'@example.test',
            'role' => $role,
            'gambar' => '-',
            'nomor' => '-',
            'alamat' => '-',
            'kota' => '-',
            'gender' => 'pria',
            'birthday' => '2000-01-01',
            'password' => 'Password123',
        ], $overrides));
    }
}
