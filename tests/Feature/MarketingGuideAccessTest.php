<?php

namespace Tests\Feature;

use App\Http\Middleware\GlobalDataMiddleware;
use App\Http\Middleware\LogActivityMiddleware;
use App\Models\User;
use App\Services\MarketingGuide\MarketingGuideAccessService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

class MarketingGuideAccessTest extends TestCase
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

    public function test_valid_token_opens_marketing_guide_and_records_access(): void
    {
        $creator = $this->user();
        $created = $this->service->create($creator, now()->addDay(), 'Partner Demo');
        $token = $created['token'];
        $access = $created['access'];

        $response = $this->get(route('marketing-guide.show', ['token' => $token]));

        $response->assertOk();
        $response->assertSee('Cara Kerja Gotik', false);
        $response->assertSee('GOTIK', false);
        $response->assertHeader('X-Robots-Tag', 'noindex, nofollow, noarchive');
        $response->assertSee('name="robots" content="noindex, nofollow, noarchive"', false);

        $access->refresh();

        $this->assertSame(1, (int) $access->access_count);
        $this->assertNotNull($access->last_accessed_at);
        $this->assertTrue($access->last_accessed_at->equalTo(now()));
    }

    public function test_plain_token_is_not_stored_in_database(): void
    {
        $creator = $this->user();
        $created = $this->service->create($creator, now()->addDays(2), 'Acme');
        $token = $created['token'];
        $access = $created['access'];

        $row = DB::table('marketing_guide_accesses')->where('id', $access->id)->first();

        $this->assertNotNull($row);
        $this->assertSame(hash('sha256', $token), $row->token_hash);
        $this->assertNotSame($token, $row->token_hash);
        $this->assertSame(64, strlen($row->token_hash));

        foreach ((array) $row as $value) {
            if (is_string($value)) {
                $this->assertStringNotContainsString($token, $value);
            }
        }

        $this->assertDatabaseMissing('marketing_guide_accesses', [
            'id' => $access->id,
            'token_hash' => $token,
        ]);
    }

    public function test_wrong_token_returns_not_found(): void
    {
        $creator = $this->user();
        $this->service->create($creator, now()->addDay());

        $wrong = $this->service->generateToken();

        $this->get(route('marketing-guide.show', ['token' => $wrong]))
            ->assertNotFound();
    }

    public function test_revoked_token_returns_not_found(): void
    {
        $creator = $this->user();
        $created = $this->service->create($creator, now()->addDay());
        $this->service->revoke($created['access']);

        $this->get(route('marketing-guide.show', ['token' => $created['token']]))
            ->assertNotFound();

        $this->assertSame(0, (int) $created['access']->fresh()->access_count);
        $this->assertNull($created['access']->fresh()->last_accessed_at);
    }

    public function test_expired_token_shows_expired_state_and_blocks_guide(): void
    {
        $creator = $this->user();
        $created = $this->service->create($creator, now()->addMinutes(30));
        $token = $created['token'];

        Carbon::setTestNow(now()->addHour());

        $response = $this->get(route('marketing-guide.show', ['token' => $token]));

        $response->assertStatus(410);
        $response->assertSee('Akses panduan telah berakhir', false);
        $response->assertSee('Link ini memiliki masa berlaku terbatas', false);
        $response->assertDontSee('Cara Kerja Gotik', false);
        $response->assertHeader('X-Robots-Tag', 'noindex, nofollow, noarchive');

        $access = $created['access']->fresh();
        $this->assertSame(0, (int) $access->access_count);
        $this->assertNull($access->last_accessed_at);
    }

    public function test_expires_at_strictly_limits_access(): void
    {
        $creator = $this->user();
        $created = $this->service->create($creator, Carbon::parse('2026-09-04 12:00:00')->addMinutes(5));
        $token = $created['token'];

        Carbon::setTestNow('2026-09-04 12:04:59');
        $this->get(route('marketing-guide.show', ['token' => $token]))->assertOk();

        Carbon::setTestNow('2026-09-04 12:05:01');
        $this->get(route('marketing-guide.show', ['token' => $token]))
            ->assertStatus(410)
            ->assertSee('Akses panduan telah berakhir', false);
    }

    public function test_valid_access_increments_access_count_and_sets_last_accessed_at(): void
    {
        $creator = $this->user();
        $created = $this->service->create($creator, now()->addDay());
        $token = $created['token'];
        $access = $created['access'];

        $this->assertSame(0, (int) $access->access_count);
        $this->assertNull($access->last_accessed_at);

        Carbon::setTestNow('2026-09-04 12:10:00');
        $this->get(route('marketing-guide.show', ['token' => $token]))->assertOk();
        $access->refresh();
        $this->assertSame(1, (int) $access->access_count);
        $firstAccessedAt = $access->last_accessed_at->copy();

        Carbon::setTestNow('2026-09-04 12:20:00');
        $this->get(route('marketing-guide.show', ['token' => $token]))->assertOk();
        $access->refresh();

        $this->assertSame(2, (int) $access->access_count);
        $this->assertTrue($access->last_accessed_at->equalTo(Carbon::parse('2026-09-04 12:20:00')));
        $this->assertTrue($access->last_accessed_at->greaterThan($firstAccessedAt));
    }

    public function test_response_has_noindex_protection(): void
    {
        $creator = $this->user();
        $created = $this->service->create($creator, now()->addDay());

        $valid = $this->get(route('marketing-guide.show', ['token' => $created['token']]));
        $valid->assertOk();
        $valid->assertHeader('X-Robots-Tag', 'noindex, nofollow, noarchive');
        $valid->assertSee('noindex, nofollow, noarchive', false);

        $expired = $this->service->create($creator, now()->addMinute());
        Carbon::setTestNow(now()->addMinutes(5));
        $expiredResponse = $this->get(route('marketing-guide.show', ['token' => $expired['token']]));
        $expiredResponse->assertStatus(410);
        $expiredResponse->assertHeader('X-Robots-Tag', 'noindex, nofollow, noarchive');
        $expiredResponse->assertSee('noindex, nofollow, noarchive', false);
    }

    public function test_route_without_token_does_not_open_marketing_guide(): void
    {
        $this->get('/guide')->assertNotFound();
        $this->get('/guide/')->assertNotFound();

        $this->get('/marketing-guide')->assertNotFound();
        $this->get('/marketing-guide/index')->assertNotFound();

        $home = $this->get('/');
        if ($home->status() === 200) {
            $home->assertDontSee('name="robots" content="noindex, nofollow, noarchive"', false);
        }
    }

    public function test_token_hash_is_not_exposed_in_response(): void
    {
        $creator = $this->user();
        $created = $this->service->create($creator, now()->addDay());
        $access = $created['access'];

        $response = $this->get(route('marketing-guide.show', ['token' => $created['token']]));
        $response->assertOk();
        $this->assertStringNotContainsString($access->getRawOriginal('token_hash') ?? $access->token_hash, $response->getContent());
        $this->assertArrayNotHasKey('token_hash', $access->toArray());
    }

    public function test_service_revoke_marks_revoked_at(): void
    {
        $creator = $this->user();
        $created = $this->service->create($creator, now()->addDay());

        $revoked = $this->service->revoke($created['access']);

        $this->assertNotNull($revoked->revoked_at);
        $this->assertTrue($revoked->isRevoked());
        $this->assertFalse($revoked->isValid());
        $this->assertSame(MarketingGuideAccessService::STATUS_REVOKED, $this->service->resolveStatus($revoked));
    }

    public function test_created_by_foreign_key_references_users_uid(): void
    {
        $this->assertTrue(Schema::hasTable('marketing_guide_accesses'));
        $this->assertTrue(Schema::hasColumn('marketing_guide_accesses', 'created_by'));
        $this->assertTrue($this->hasForeignKey('marketing_guide_accesses', 'created_by', 'users', 'uid'));

        $this->expectException(QueryException::class);

        DB::table('marketing_guide_accesses')->insert([
            'token_hash' => hash('sha256', 'invalid-creator-fk-token'),
            'recipient_name' => null,
            'expires_at' => now()->addDay(),
            'revoked_at' => null,
            'last_accessed_at' => null,
            'access_count' => 0,
            'created_by' => 'missing-user-uid-'.Str::uuid(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_force_deleting_creator_cascades_marketing_guide_access_rows(): void
    {
        $creator = $this->user();
        $created = $this->service->create($creator, now()->addDay(), 'Cascade Check');
        $accessId = $created['access']->id;

        $this->assertDatabaseHas('marketing_guide_accesses', [
            'id' => $accessId,
            'created_by' => $creator->uid,
        ]);

        $creator->forceDelete();

        $this->assertDatabaseMissing('marketing_guide_accesses', [
            'id' => $accessId,
        ]);
    }

    private function hasForeignKey(string $table, string $column, string $foreignTable, string $foreignColumn): bool
    {
        $database = DB::getDatabaseName();

        $row = DB::table('information_schema.KEY_COLUMN_USAGE')
            ->where('TABLE_SCHEMA', $database)
            ->where('TABLE_NAME', $table)
            ->where('COLUMN_NAME', $column)
            ->where('REFERENCED_TABLE_NAME', $foreignTable)
            ->where('REFERENCED_COLUMN_NAME', $foreignColumn)
            ->first();

        return $row !== null;
    }

    private function user(array $overrides = []): User
    {
        return User::factory()->create(array_merge([
            'uid' => (string) Str::uuid(),
            'name' => 'Guide Creator',
            'email' => 'guide-creator-'.Str::random(8).'@example.test',
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
