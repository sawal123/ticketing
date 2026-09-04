<?php

namespace Tests\Feature;

use App\Http\Middleware\GlobalDataMiddleware;
use App\Http\Middleware\LogActivityMiddleware;
use App\Models\MarketingGuideAccess;
use App\Models\User;
use App\Services\MarketingGuide\MarketingGuideAccessService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Tests\TestCase;

class MarketingGuideSecurityTest extends TestCase
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

    public function test_valid_token_returns_ok_with_security_headers_and_tracking(): void
    {
        $creator = $this->user();
        $created = $this->service->create($creator, now()->addDay(), 'Security Partner');
        $token = $created['token'];
        $access = $created['access'];

        $response = $this->get(route('marketing-guide.show', ['token' => $token]));

        $response->assertOk();
        $response->assertSee('Cara Kerja Gotik', false);
        $this->assertSecureGuideHeaders($response);
        $response->assertSee('name="referrer" content="no-referrer"', false);

        $access->refresh();
        $this->assertSame(1, (int) $access->access_count);
        $this->assertNotNull($access->last_accessed_at);
        $this->assertTrue($access->last_accessed_at->equalTo(now()));

        $html = $response->getContent();
        $this->assertStringNotContainsString($token, $html);
        $this->assertStringNotContainsString($access->token_hash, $html);
        $this->assertStringNotContainsString('localStorage', $html);
        $this->assertStringNotContainsString('sessionStorage', $html);
        $this->assertStringNotContainsString('rel="canonical"', $html);
    }

    public function test_expired_token_returns_gone_with_security_headers_without_tracking(): void
    {
        $creator = $this->user();
        $created = $this->service->create($creator, now()->addMinutes(10), 'Expired Partner');
        $token = $created['token'];

        Carbon::setTestNow(now()->addHour());

        $response = $this->get(route('marketing-guide.show', ['token' => $token]));

        $response->assertStatus(410);
        $response->assertSee('Akses panduan telah berakhir', false);
        $this->assertSecureGuideHeaders($response);
        $response->assertSee('name="referrer" content="no-referrer"', false);

        $access = $created['access']->fresh();
        $this->assertSame(0, (int) $access->access_count);
        $this->assertNull($access->last_accessed_at);

        $html = $response->getContent();
        $this->assertStringNotContainsString($token, $html);
        $this->assertStringNotContainsString($access->token_hash, $html);
    }

    public function test_revoked_and_invalid_tokens_return_not_found_without_tracking(): void
    {
        $creator = $this->user();
        $created = $this->service->create($creator, now()->addDay(), 'Revoked Partner');
        $this->service->revoke($created['access']);

        $revoked = $this->get(route('marketing-guide.show', ['token' => $created['token']]));
        $revoked->assertNotFound();
        $revoked->assertDontSee('Cara Kerja Gotik', false);
        $revoked->assertDontSee('Akses panduan telah berakhir', false);

        $this->assertSame(0, (int) $created['access']->fresh()->access_count);
        $this->assertNull($created['access']->fresh()->last_accessed_at);

        $this->get(route('marketing-guide.show', ['token' => $this->service->generateToken()]))
            ->assertNotFound();

        $this->get(route('marketing-guide.show', ['token' => 'not-a-valid-token']))
            ->assertNotFound();
    }

    public function test_access_revoked_during_record_access_does_not_render_guide_or_increment(): void
    {
        $creator = $this->user();
        $created = $this->service->create($creator, now()->addDay(), 'Race Revoked');
        $token = $created['token'];

        $this->partialMock(MarketingGuideAccessService::class, function ($mock) {
            $mock->shouldReceive('recordAccess')
                ->once()
                ->andReturnUsing(function (MarketingGuideAccess $access) {
                    $access->forceFill([
                        'revoked_at' => now(),
                    ])->save();

                    return $access->refresh();
                });
        });

        $response = $this->get(route('marketing-guide.show', ['token' => $token]));

        $response->assertNotFound();
        $response->assertDontSee('Cara Kerja Gotik', false);

        $access = $created['access']->fresh();
        $this->assertNotNull($access->revoked_at);
        $this->assertSame(0, (int) $access->access_count);
        $this->assertNull($access->last_accessed_at);
    }

    public function test_access_expired_during_record_access_does_not_render_guide_or_increment(): void
    {
        $creator = $this->user();
        $created = $this->service->create($creator, now()->addDay(), 'Race Expired');
        $token = $created['token'];

        $this->partialMock(MarketingGuideAccessService::class, function ($mock) {
            $mock->shouldReceive('recordAccess')
                ->once()
                ->andReturnUsing(function (MarketingGuideAccess $access) {
                    $access->forceFill([
                        'expires_at' => now()->subSecond(),
                    ])->save();

                    return $access->refresh();
                });
        });

        $response = $this->get(route('marketing-guide.show', ['token' => $token]));

        $response->assertStatus(410);
        $response->assertSee('Akses panduan telah berakhir', false);
        $response->assertDontSee('Cara Kerja Gotik', false);
        $this->assertSecureGuideHeaders($response);

        $access = $created['access']->fresh();
        $this->assertSame(0, (int) $access->access_count);
        $this->assertNull($access->last_accessed_at);
    }

    public function test_record_access_lock_skips_increment_when_row_no_longer_valid(): void
    {
        $creator = $this->user();
        $created = $this->service->create($creator, now()->addDay());
        $access = $created['access'];

        $this->service->revoke($access);
        $result = $this->service->recordAccess($access->fresh());

        $this->assertTrue($result->isRevoked());
        $this->assertSame(0, (int) $result->access_count);
        $this->assertNull($result->last_accessed_at);

        $expired = $this->service->create($creator, now()->addMinutes(5));
        Carbon::setTestNow(now()->addHour());
        $expiredResult = $this->service->recordAccess($expired['access']->fresh());

        $this->assertTrue($expiredResult->isExpired());
        $this->assertSame(0, (int) $expiredResult->access_count);
        $this->assertNull($expiredResult->last_accessed_at);
    }

    public function test_marketing_guide_route_is_rate_limited_to_thirty_per_minute(): void
    {
        $token = $this->service->generateToken();
        $uri = route('marketing-guide.show', ['token' => $token], false);

        for ($i = 1; $i <= 30; $i++) {
            $this->get($uri)->assertNotFound();
        }

        $this->get($uri)->assertStatus(429);
    }

    private function assertSecureGuideHeaders($response): void
    {
        $cacheControl = (string) $response->headers->get('Cache-Control');
        $this->assertStringContainsString('private', $cacheControl);
        $this->assertStringContainsString('no-store', $cacheControl);
        $this->assertStringContainsString('no-cache', $cacheControl);
        $this->assertStringContainsString('must-revalidate', $cacheControl);
        $this->assertStringContainsString('max-age=0', $cacheControl);

        $response->assertHeader('Pragma', 'no-cache');
        $response->assertHeader('Referrer-Policy', 'no-referrer');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'DENY');
        $response->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
        $response->assertHeader('X-Robots-Tag', 'noindex, nofollow, noarchive');
    }

    private function user(array $overrides = []): User
    {
        return User::factory()->create(array_merge([
            'uid' => (string) Str::uuid(),
            'name' => 'Guide Creator',
            'email' => 'guide-security-'.Str::random(8).'@example.test',
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
