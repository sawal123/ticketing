<?php

namespace Tests\Feature;

use App\Models\MarketingGuideBlock;
use App\Models\MarketingGuideSection;
use App\Models\MarketingGuideVersion;
use App\Services\MarketingGuide\MarketingGuideContentService;
use Database\Seeders\MarketingGuideContentSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MarketingGuideContentTest extends TestCase
{
    use RefreshDatabase;

    private MarketingGuideContentService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(MarketingGuideContentService::class);
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
        $this->assertSame(12, $sections->count());
        $this->assertSame(range(1, 12), $sections->pluck('position')->all());

        $expectedKeys = [
            'hero', 'cara_kerja', 'menjadi_penyelenggara', 'setup_event',
            'tiket_harga', 'cara_pembeli_beli', 'pembayaran', 'dashboard_transaksi',
            'qr_ticket', 'scanner_checkin', 'faq', 'cta',
        ];
        $this->assertSame($expectedKeys, $sections->pluck('key')->all());

        foreach (MarketingGuideBlock::all() as $block) {
            $this->assertContains($block->type, MarketingGuideBlock::TYPES);
        }
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
            ->map(fn ($block) => json_encode($block->data))
            ->implode("\n");

        foreach ([
            'Cara Kerja Gotik',
            'Daftarkan Event',
            'Pencairan dana',
            'Hubungi Tim',
            'E-Wallet',
            'Histori Transaksi',
            'Jakarta Convention Center',
            'Aktivasi event biasanya memakan waktu',
            'Siap Mengelola Event Anda?',
        ] as $needle) {
            $this->assertStringContainsString($needle, $combined);
        }
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
