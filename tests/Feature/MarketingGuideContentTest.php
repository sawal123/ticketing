<?php

namespace Tests\Feature;

use App\Models\MarketingGuideBlock;
use App\Models\MarketingGuideSection;
use App\Models\MarketingGuideVersion;
use App\Models\User;
use App\Services\MarketingGuide\MarketingGuideAccessService;
use App\Services\MarketingGuide\MarketingGuideContentService;
use Database\Seeders\MarketingGuideContentSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

class MarketingGuideContentTest extends TestCase
{
    use RefreshDatabase;

    private MarketingGuideContentService $service;

    protected function setUp(): void
    {
        parent::setUp();

        \Carbon\Carbon::setTestNow('2026-09-04 12:00:00');
        $this->withoutMiddleware([
            \App\Http\Middleware\GlobalDataMiddleware::class,
            \App\Http\Middleware\LogActivityMiddleware::class,
        ]);

        $this->service = app(MarketingGuideContentService::class);
    }

    public function test_integration_public_rendering(): void
    {
        $creator = User::create([
            'uid' => (string) Str::uuid(),
            'name' => 'Integration Creator',
            'email' => 'integration-'.Str::random(8).'@example.test',
            'role' => 'admin',
            'password' => 'Password123',
        ]);
        $access = app(MarketingGuideAccessService::class)->create($creator, now()->addDay(), 'Fixture Partner');
        $token = $access['token'];

        $published = $this->makeVersion(['key' => 'guide.integration.published']);
        $first = $this->makeSection($published, 'ordered_first', 2);
        $second = $this->makeSection($published, 'ordered_second', 1);
        $this->makeBlock($first, 2, MarketingGuideBlock::TYPE_TEXT, ['intro' => 'First block sentinel']);
        $this->makeBlock($first, 1, MarketingGuideBlock::TYPE_TEXT, ['intro' => 'Second block sentinel']);
        $this->makeBlock($second, 1, MarketingGuideBlock::TYPE_TEXT, ['intro' => 'Second section sentinel']);
        $this->makeSection($published, 'inactive_section', 3, false);
        $this->makeBlock($first, 3, MarketingGuideBlock::TYPE_TEXT, ['intro' => 'inactive block sentinel'], false);

        $draft = $this->makeVersion(['key' => 'guide.draft.integration', 'status' => MarketingGuideVersion::STATUS_DRAFT, 'published_at' => null]);
        $draftSection = $this->makeSection($draft, 'draft_section', 1);
        $this->makeBlock($draftSection, 1, MarketingGuideBlock::TYPE_TEXT, ['intro' => 'draft sentinel']);

        $response = $this->get(route('marketing-guide.show', ['token' => $token]));
        $response->assertOk();
        $html = $response->getContent();
        $this->assertStringNotContainsString('draft sentinel', $html);
        $this->assertStringNotContainsString('inactive_section', $html);
        $this->assertStringNotContainsString('inactive block sentinel', $html);
        $this->assertTrue(strpos($html, 'Second section sentinel') < strpos($html, 'Second block sentinel'));
        $this->assertTrue(strpos($html, 'Second block sentinel') < strpos($html, 'First block sentinel'));

        foreach (['primary' => 'btn-primary', 'secondary' => 'btn-secondary', 'cta' => 'btn-cta', 'invalid' => 'btn-cta'] as $variant => $class) {
            $cta = $this->makeSection($published, 'cta_'.$variant, 10 + count(MarketingGuideSection::all()));
            $this->makeBlock($cta, 1, MarketingGuideBlock::TYPE_CTA, ['title' => 'CTA '.$variant, 'cta' => ['label' => 'CTA '.$variant, 'href' => '#', 'variant' => $variant]]);
        }
        $ctaHtml = $this->get(route('marketing-guide.show', ['token' => $token]))->getContent();
        foreach (['primary' => 'btn-primary', 'secondary' => 'btn-secondary', 'cta' => 'btn-cta', 'invalid' => 'btn-cta'] as $variant => $class) {
            $this->assertMatchesRegularExpression('/CTA '.preg_quote($variant, '/').'.*class="[^"]*'.preg_quote($class, '/').'/s', $ctaHtml);
        }

        MarketingGuideVersion::query()->update(['status' => MarketingGuideVersion::STATUS_DRAFT, 'published_at' => null]);
        $this->get(route('marketing-guide.show', ['token' => $token]))->assertOk()->assertSee('Cara Kerja Gotik', false);
        $this->get(route('marketing-guide.show', ['token' => 'invalid-token']))->assertNotFound();
    }

    public function test_version_sections_and_blocks_tables_exist(): void
    {
        $this->assertTrue(Schema::hasTable('marketing_guide_versions'));
        $this->assertTrue(Schema::hasTable('marketing_guide_sections'));
        $this->assertTrue(Schema::hasTable('marketing_guide_blocks'));
    }

    public function test_version_section_and_block_relationships_are_wired(): void
    {
        $version = MarketingGuideVersion::create([
            'key' => 'guide.v1',
            'number' => 1,
            'title' => 'Panduan Marketing Gotik v1',
            'status' => MarketingGuideVersion::STATUS_PUBLISHED,
            'published_at' => now(),
        ]);

        $section = MarketingGuideSection::create([
            'version_id' => $version->id,
            'key' => 'hero',
            'title' => 'Hero',
            'position' => 1,
            'is_active' => true,
        ]);

        $block = MarketingGuideBlock::create([
            'section_id' => $section->id,
            'type' => MarketingGuideBlock::TYPE_TEXT,
            'position' => 1,
            'data' => ['intro' => 'halo'],
            'is_active' => true,
        ]);

        $this->assertSame($version->id, $section->version->id);
        $this->assertSame($section->id, $block->section->id);
        $this->assertCount(1, $version->sections);
        $this->assertCount(1, $section->blocks);
    }

    public function test_publisher_and_nav_group_columns_round_trip(): void
    {
        $version = MarketingGuideVersion::create([
            'key' => 'guide.v2',
            'number' => 2,
            'title' => 'Panduan v2',
            'status' => MarketingGuideVersion::STATUS_PUBLISHED,
            'published_at' => now(),
            'published_by_uid' => null,
        ]);

        $section = MarketingGuideSection::create([
            'version_id' => $version->id,
            'key' => 'pengenalan',
            'title' => 'Pengenalan',
            'slug' => 'pengenalan',
            'nav_group' => 'MENGENAL GOTIK',
            'position' => 1,
            'is_active' => true,
        ]);

        $this->assertSame('MENGENAL GOTIK', $section->fresh()->nav_group);
        $this->assertNull($version->fresh()->published_by_uid);
        $this->assertNull($version->fresh()->publisher);
    }

    public function test_blocks_are_ordered_by_position(): void
    {
        $version = $this->makeVersion();
        $section = $this->makeSection($version, 'workflow');

        $this->makeBlock($section, 3, MarketingGuideBlock::TYPE_TEXT, ['intro' => 'tiga']);
        $this->makeBlock($section, 1, MarketingGuideBlock::TYPE_TEXT, ['intro' => 'satu']);
        $this->makeBlock($section, 2, MarketingGuideBlock::TYPE_TEXT, ['intro' => 'dua']);

        $ordered = $section->blocks()->pluck('position')->all();

        $this->assertSame([1, 2, 3], $ordered);
    }

    public function test_active_sections_filter_excludes_inactive_rows(): void
    {
        $version = $this->makeVersion();
        $this->makeSection($version, 'active-1', 1, true);
        $this->makeSection($version, 'active-2', 2, true);
        $this->makeSection($version, 'inactive', 3, false);

        $active = $this->service->activeSectionsForVersion($version)->pluck('key')->all();

        $this->assertSame(['active-1', 'active-2'], $active);
    }

    public function test_active_blocks_filter_excludes_inactive_rows(): void
    {
        $version = $this->makeVersion();
        $section = $this->makeSection($version, 'flow');

        $this->makeBlock($section, 1, MarketingGuideBlock::TYPE_TEXT, ['intro' => 'visible'], true);
        $this->makeBlock($section, 2, MarketingGuideBlock::TYPE_TEXT, ['intro' => 'hidden'], false);

        $positions = $this->service->activeBlocksForSection($section)->pluck('position')->all();

        $this->assertSame([1], $positions);
    }

    public function test_block_data_is_cast_to_array(): void
    {
        $version = $this->makeVersion();
        $section = $this->makeSection($version, 'cards');

        $block = $this->makeBlock(
            $section,
            1,
            MarketingGuideBlock::TYPE_CARDS,
            ['columns' => 2, 'cards' => [['title' => 'A']]],
        );

        $this->assertIsArray($block->data);
        $this->assertSame(2, $block->data['columns']);
        $this->assertSame('A', $block->data['cards'][0]['title']);
    }

    public function test_service_rejects_invalid_block_type(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->service->assertValidBlockType('not-a-real-type');
    }

    public function test_seeder_is_idempotent_and_populates_expected_shape(): void
    {
        $this->seed(MarketingGuideContentSeeder::class);
        $firstSnapshot = $this->snapshot();
        $this->seed(MarketingGuideContentSeeder::class);
        $secondSnapshot = $this->snapshot();

        $this->assertSame($firstSnapshot['counts'], $secondSnapshot['counts']);

        $version = MarketingGuideVersion::query()
            ->where('key', MarketingGuideContentSeeder::VERSION_KEY)
            ->firstOrFail();
        $this->assertSame(MarketingGuideVersion::STATUS_PUBLISHED, $version->status);

        $sections = $this->service->activeSectionsForVersion($version);
        $this->assertSame(14, $sections->count());
        $this->assertSame(range(1, 14), $sections->pluck('position')->all());

        $expectedKeys = [
            'pengenalan', 'cara_kerja', 'menjadi_penyelenggara', 'setup_event',
            'tiket_harga', 'cara_pembeli_beli', 'pembayaran', 'dashboard_transaksi',
            'qr_ticket', 'scanner_checkin', 'laporan', 'penarikan_dana',
            'faq', 'cta_hubungi',
        ];
        $this->assertSame($expectedKeys, $sections->pluck('key')->all());

        $expectedSlugs = [
            'pengenalan', 'cara-kerja', 'menjadi-penyelenggara', 'setup-event',
            'tiket-harga', 'cara-pembeli-beli', 'pembayaran', 'dashboard-transaksi',
            'qr-ticket', 'scanner-checkin', 'laporan', 'penarikan-dana',
            'faq', 'hubungi',
        ];
        $this->assertSame($expectedSlugs, $sections->pluck('slug')->all());

        foreach (MarketingGuideBlock::all() as $block) {
            $this->assertContains($block->type, MarketingGuideBlock::TYPES);
        }
    }

    public function test_seeder_marks_new_sections_with_all_six_nav_groups(): void
    {
        $this->seed(MarketingGuideContentSeeder::class);

        $expectedGroups = [
            'MENGENAL GOTIK' => ['pengenalan', 'cara_kerja'],
            'MEMULAI EVENT' => ['menjadi_penyelenggara', 'setup_event', 'tiket_harga'],
            'PENJUALAN' => ['cara_pembeli_beli', 'pembayaran', 'dashboard_transaksi'],
            'HARI-H EVENT' => ['qr_ticket', 'scanner_checkin'],
            'KEUANGAN' => ['laporan', 'penarikan_dana'],
            'LAINNYA' => ['faq', 'cta_hubungi'],
        ];

        $version = $this->service->currentVersion();
        $this->assertNotNull($version);

        foreach ($expectedGroups as $group => $keys) {
            $actual = $version->sections()
                ->where('nav_group', $group)
                ->orderBy('position')
                ->pluck('key')
                ->all();
            $this->assertSame($keys, $actual, "nav_group={$group} should map to those section keys");
        }
    }

    public function test_seeder_does_not_reset_published_at_on_rerun(): void
    {
        $firstAt = now()->subDays(7);
        MarketingGuideVersion::query()->updateOrCreate(
            ['key' => MarketingGuideContentSeeder::VERSION_KEY],
            [
                'number' => 1,
                'title' => 'Panduan Marketing Gotik v1',
                'status' => MarketingGuideVersion::STATUS_PUBLISHED,
                'published_at' => $firstAt,
            ],
        );

        $this->seed(MarketingGuideContentSeeder::class);

        $version = MarketingGuideVersion::query()
            ->where('key', MarketingGuideContentSeeder::VERSION_KEY)
            ->firstOrFail();

        $this->assertNotNull($version->published_at);
        $this->assertSame(
            $firstAt->toDateTimeString(),
            $version->published_at->toDateTimeString(),
            'Re-running the seeder must not reset published_at.',
        );
    }

    public function test_current_version_returns_null_when_only_draft_exists(): void
    {
        MarketingGuideVersion::create([
            'key' => 'guide.draft',
            'number' => 1,
            'title' => 'Draft only',
            'status' => MarketingGuideVersion::STATUS_DRAFT,
            'published_at' => null,
        ]);

        $this->assertNull($this->service->currentVersion());
    }

    public function test_current_version_returns_latest_published_when_mixed_with_older_draft(): void
    {
        $draft = MarketingGuideVersion::create([
            'key' => 'guide.draft',
            'number' => 2,
            'title' => 'Newer draft',
            'status' => MarketingGuideVersion::STATUS_DRAFT,
            'published_at' => null,
        ]);

        $published = $this->makeVersion([
            'key' => 'guide.v0',
            'status' => MarketingGuideVersion::STATUS_PUBLISHED,
            'published_at' => now()->subDay(),
        ]);

        $current = $this->service->currentVersion();

        $this->assertNotNull($current);
        $this->assertSame($published->id, $current->id);
        $this->assertNotSame($draft->id, $current->id);
    }

    public function test_seeder_orphans_inactive_blocks_when_section_changes(): void
    {
        $this->seed(MarketingGuideContentSeeder::class);
        $version = MarketingGuideVersion::query()
            ->where('key', MarketingGuideContentSeeder::VERSION_KEY)
            ->firstOrFail();
        $section = $version->sections()->where('key', 'cara_kerja')->firstOrFail();

        $this->assertSame(2, $section->blocks()->count());

        $section->blocks()->create([
            'type' => MarketingGuideBlock::TYPE_CTA,
            'position' => 99,
            'data' => ['stray' => true],
            'is_active' => true,
        ]);

        $this->seed(MarketingGuideContentSeeder::class);

        $this->assertSame(2, $section->blocks()->where('is_active', true)->count());
        $this->assertTrue(
            $section->blocks()->where('position', 99)->where('is_active', false)->exists(),
        );
    }

    public function test_version_section_key_pair_is_unique_per_version(): void
    {
        $version = $this->makeVersion();
        $this->makeSection($version, 'hero', 1);

        $this->expectException(QueryException::class);

        MarketingGuideSection::create([
            'version_id' => $version->id,
            'key' => 'hero',
            'title' => 'Duplicate',
            'position' => 2,
            'is_active' => true,
        ]);
    }

    public function test_block_position_is_unique_within_section(): void
    {
        $version = $this->makeVersion();
        $section = $this->makeSection($version, 'flow');

        $this->makeBlock($section, 1, MarketingGuideBlock::TYPE_TEXT, ['intro' => 'one']);

        $this->expectException(QueryException::class);

        MarketingGuideBlock::create([
            'section_id' => $section->id,
            'type' => MarketingGuideBlock::TYPE_TEXT,
            'position' => 1,
            'data' => ['intro' => 'collision'],
            'is_active' => true,
        ]);
    }

    public function test_deleting_version_cascades_to_sections_and_blocks(): void
    {
        $version = $this->makeVersion();
        $section = $this->makeSection($version, 'flow');
        $this->makeBlock($section, 1, MarketingGuideBlock::TYPE_TEXT, ['intro' => 'halo']);

        $version->delete();

        $this->assertDatabaseMissing('marketing_guide_sections', ['id' => $section->id]);
        $this->assertDatabaseMissing('marketing_guide_blocks', ['section_id' => $section->id]);
    }

    public function test_regression_static_blade_anchors_are_now_in_database(): void
    {
        $this->seed(MarketingGuideContentSeeder::class);

        $version = $this->service->currentVersion();
        $this->assertNotNull($version);

        $combined = $version->sections->load('blocks')
            ->flatMap(fn ($section) => $section->blocks)
            ->map(fn ($block) => json_encode($block->data, JSON_UNESCAPED_UNICODE))
            ->implode("\n");

        // Phrases lifted directly from resources/views/marketing-guide/index.blade.php.
        // If any of these are missing, the seeder has drifted from the static view.
        foreach ([
            'Cara Kerja Gotik',
            'Daftarkan Event',
            'Hubungi Gotik',
            'E-Wallet',
            'Transfer Bank',
            'Jakarta Convention Center',
            'Histori Transaksi',
            'Siap Menjalankan Event Bersama Gotik?',
            'Apakah penyelenggara harus memiliki website?',
            'Berapa biaya platform Gotik?',
            'Hubungi tim Gotik untuk mendiskusikan kebutuhan',
            'Penjualan',
            'Rekap Transaksi',
        ] as $needle) {
            $this->assertStringContainsString($needle, $combined, "Missing phrase: {$needle}");
        }
    }

    public function test_published_version_renders_dynamic_view_with_all_block_types(): void
    {
        $this->seed(MarketingGuideContentSeeder::class);

        $creator = User::factory()->create([
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
        ]);

        $accessService = app(MarketingGuideAccessService::class);
        $created = $accessService->create($creator, now()->addDay(), 'Partner Demo');
        $token = $created['token'];

        $response = $this->get(route('marketing-guide.show', ['token' => $token]));
        $response->assertOk();

        $html = $response->getContent();

        // Dynamic view must render database-backed content, not the static fallback.
        $this->assertStringContainsString('Cara Kerja Gotik', $html);
        $this->assertStringContainsString('Daftarkan Event', $html);
        $this->assertStringContainsString('Hubungi Tim Gotik', $html);
        $this->assertStringContainsString('E-Wallet', $html);
        $this->assertStringContainsString('Jakarta Convention Center', $html);
        $this->assertStringContainsString('Siap Menjalankan Event Bersama Gotik?', $html);
        $this->assertStringContainsString('Apakah penyelenggara harus memiliki website?', $html);
        $this->assertStringContainsString('Panduan ini disiapkan untuk Partner Demo', $html);

        // Security headers still applied on the dynamic render.
        $this->assertStringContainsString('noindex, nofollow, noarchive', $html);
        $response->assertHeader('X-Robots-Tag', 'noindex, nofollow, noarchive');
    }

    private function makeVersion(array $overrides = []): MarketingGuideVersion
    {
        return MarketingGuideVersion::create(array_merge([
            'key' => 'guide.v1',
            'number' => 1,
            'title' => 'Panduan Marketing Gotik v1',
            'status' => MarketingGuideVersion::STATUS_PUBLISHED,
            'published_at' => now(),
        ], $overrides));
    }

    private function makeSection(
        MarketingGuideVersion $version,
        string $key,
        int $position = 1,
        bool $isActive = true,
    ): MarketingGuideSection {
        return MarketingGuideSection::create([
            'version_id' => $version->id,
            'key' => $key,
            'title' => ucwords(str_replace('_', ' ', $key)),
            'position' => $position,
            'is_active' => $isActive,
        ]);
    }

    private function makeBlock(
        MarketingGuideSection $section,
        int $position,
        string $type,
        array $data,
        bool $isActive = true,
    ): MarketingGuideBlock {
        return MarketingGuideBlock::create([
            'section_id' => $section->id,
            'type' => $type,
            'position' => $position,
            'data' => $data,
            'is_active' => $isActive,
        ]);
    }

    private function snapshot(): array
    {
        return [
            'counts' => [
                'versions' => MarketingGuideVersion::count(),
                'sections' => MarketingGuideSection::count(),
                'blocks' => MarketingGuideBlock::count(),
            ],
            'version_key' => MarketingGuideVersion::query()->value('key'),
        ];
    }
}
