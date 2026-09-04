<?php

namespace Tests\Feature;

use App\Http\Middleware\GlobalDataMiddleware;
use App\Http\Middleware\LogActivityMiddleware;
use App\Models\User;
use App\Services\MarketingGuide\MarketingGuideAccessService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Tests\TestCase;

class MarketingGuideAccessUiTest extends TestCase
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

    public function test_valid_token_shows_guide_with_dynamic_access_data(): void
    {
        $creator = $this->user();
        $expiresAt = Carbon::parse('2026-09-11 18:30:00');
        $created = $this->service->create($creator, $expiresAt, 'PT ABC');
        $token = $created['token'];
        $access = $created['access'];

        $response = $this->get(route('marketing-guide.show', ['token' => $token]));

        $response->assertOk();
        $response->assertSee('Cara Kerja Gotik', false);
        $response->assertSee('Panduan Privat', false);
        $response->assertSee('Panduan ini disiapkan untuk PT ABC', false);
        $response->assertSee('Akses tersedia hingga 11 September 2026', false);
        $response->assertDontSee('11 September 2026 • Jakarta Convention Center', false);
        $response->assertHeader('X-Robots-Tag', 'noindex, nofollow, noarchive');
        $response->assertSee('name="robots" content="noindex, nofollow, noarchive"', false);

        $access->refresh();
        $this->assertSame(1, (int) $access->access_count);
        $this->assertNotNull($access->last_accessed_at);
        $this->assertTrue($access->last_accessed_at->equalTo(now()));

        $html = $response->getContent();
        $this->assertStringNotContainsString($access->token_hash, $html);
        $this->assertStringNotContainsString($token, $html);
        $this->assertStringNotContainsString('localStorage', $html);
        $this->assertStringNotContainsString('sessionStorage', $html);
        $this->assertStringNotContainsString('rel="canonical"', $html);
        $this->assertDoesNotMatchRegularExpression('/href=["\']\/guide["\']/', $html);
        $this->assertDoesNotMatchRegularExpression('/href=["\']\/guide\/["\']/', $html);
    }

    public function test_recipient_name_is_hidden_when_null_or_blank(): void
    {
        $creator = $this->user();

        $withoutRecipient = $this->service->create($creator, now()->addDays(3), null);
        $blankRecipient = $this->service->create($creator, now()->addDays(4), '   ');

        $responseNull = $this->get(route('marketing-guide.show', ['token' => $withoutRecipient['token']]));
        $responseNull->assertOk();
        $responseNull->assertDontSee('Panduan ini disiapkan untuk', false);
        $responseNull->assertDontSee('disiapkan untuk -', false);
        $responseNull->assertSee('Akses tersedia hingga', false);

        $responseBlank = $this->get(route('marketing-guide.show', ['token' => $blankRecipient['token']]));
        $responseBlank->assertOk();
        $responseBlank->assertDontSee('Panduan ini disiapkan untuk', false);
    }

    public function test_expiry_date_comes_from_database_not_static_dummy(): void
    {
        $creator = $this->user();
        $created = $this->service->create(
            $creator,
            Carbon::parse('2026-10-05 09:00:00'),
            'Partner Demo'
        );

        $response = $this->get(route('marketing-guide.show', ['token' => $created['token']]));

        $response->assertOk();
        $response->assertSee('Akses tersedia hingga 5 Oktober 2026', false);
        $response->assertDontSee('Akses tersedia hingga 11 September 2026', false);
    }

    public function test_invalid_token_returns_not_found(): void
    {
        $this->get(route('marketing-guide.show', ['token' => $this->service->generateToken()]))
            ->assertNotFound();
    }

    public function test_revoked_token_returns_not_found_without_special_page(): void
    {
        $creator = $this->user();
        $created = $this->service->create($creator, now()->addDay(), 'Revoked Partner');
        $this->service->revoke($created['access']);

        $response = $this->get(route('marketing-guide.show', ['token' => $created['token']]));

        $response->assertNotFound();
        $response->assertDontSee('Akses panduan telah berakhir', false);
        $response->assertDontSee('Cara Kerja Gotik', false);
        $this->assertSame(0, (int) $created['access']->fresh()->access_count);
    }

    public function test_expired_token_returns_gone_with_branded_expired_ui(): void
    {
        $creator = $this->user();
        $created = $this->service->create($creator, now()->addMinutes(20), 'Expired Partner');
        $token = $created['token'];

        Carbon::setTestNow(now()->addHour());

        $response = $this->get(route('marketing-guide.show', ['token' => $token]));

        $response->assertStatus(410);
        $response->assertSee('Akses panduan telah berakhir', false);
        $response->assertSee('Link ini memiliki masa berlaku terbatas. Silakan hubungi tim Gotik untuk mendapatkan akses baru.', false);
        $response->assertSee('GOTIK', false);
        $response->assertDontSee('Cara Kerja Gotik', false);
        $response->assertDontSee('Panduan ini disiapkan untuk', false);
        $response->assertHeader('X-Robots-Tag', 'noindex, nofollow, noarchive');
        $response->assertSee('name="robots" content="noindex, nofollow, noarchive"', false);

        $access = $created['access']->fresh();
        $this->assertSame(0, (int) $access->access_count);
        $this->assertNull($access->last_accessed_at);

        $html = $response->getContent();
        $this->assertStringNotContainsString($access->token_hash, $html);
        $this->assertStringNotContainsString($token, $html);
    }

    public function test_valid_access_still_records_access_metrics(): void
    {
        $creator = $this->user();
        $created = $this->service->create($creator, now()->addDay());
        $token = $created['token'];
        $access = $created['access'];

        Carbon::setTestNow('2026-09-04 12:15:00');
        $this->get(route('marketing-guide.show', ['token' => $token]))->assertOk();
        $access->refresh();
        $this->assertSame(1, (int) $access->access_count);
        $this->assertTrue($access->last_accessed_at->equalTo(Carbon::parse('2026-09-04 12:15:00')));

        Carbon::setTestNow('2026-09-04 12:45:00');
        $this->get(route('marketing-guide.show', ['token' => $token]))->assertOk();
        $access->refresh();
        $this->assertSame(2, (int) $access->access_count);
        $this->assertTrue($access->last_accessed_at->equalTo(Carbon::parse('2026-09-04 12:45:00')));
    }

    public function test_no_public_guide_route_without_token(): void
    {
        $this->get('/guide')->assertNotFound();
        $this->get('/guide/')->assertNotFound();

        $namedRoutes = collect(Route::getRoutes())->map(fn ($route) => $route->getName())->filter();
        $this->assertTrue($namedRoutes->contains('marketing-guide.show'));
        $this->assertFalse($namedRoutes->contains('marketing-guide.index'));
        $this->assertFalse($namedRoutes->contains('marketing-guide.public'));
    }

    private function user(array $overrides = []): User
    {
        return User::factory()->create(array_merge([
            'uid' => (string) Str::uuid(),
            'name' => 'Guide Creator',
            'email' => 'guide-ui-'.Str::random(8).'@example.test',
            'role' => 'admin',
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
