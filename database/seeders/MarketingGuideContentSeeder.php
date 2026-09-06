<?php

namespace Database\Seeders;

use App\Models\MarketingGuideBlock;
use App\Models\MarketingGuideSection;
use App\Models\MarketingGuideVersion;
use App\Services\MarketingGuide\MarketingGuideContentService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class MarketingGuideContentSeeder extends Seeder
{
    public const VERSION_KEY = 'guide.v1';

    /**
     * Idempotent seed: subsequent runs keep existing sections/blocks stable
     * and update their content in place. Anchor keys: (version_id, key) for
     * sections, (section_id, position) for blocks.
     */
    public function run(): void
    {
        $service = app(MarketingGuideContentService::class);
        $now = Carbon::now();

        DB::transaction(function () use ($service, $now) {
            $version = MarketingGuideVersion::query()->updateOrCreate(
                ['key' => self::VERSION_KEY],
                [
                    'number' => 1,
                    'title' => 'Panduan Marketing Gotik v1',
                    'status' => MarketingGuideVersion::STATUS_PUBLISHED,
                    'created_by_uid' => null,
                    'published_at' => $now,
                ]
            );

            foreach ($this->sections() as $sectionKey => $payload) {
                $section = MarketingGuideSection::query()->updateOrCreate(
                    [
                        'version_id' => $version->id,
                        'key' => $sectionKey,
                    ],
                    [
                        'title' => $payload['title'],
                        'slug' => $payload['slug'] ?? null,
                        'position' => $payload['position'],
                        'is_active' => true,
                    ]
                );

                $desiredPositions = [];

                foreach ($payload['blocks'] as $position => $block) {
                    $desiredPositions[] = $position;
                    $service->assertValidBlockType($block['type']);

                    MarketingGuideBlock::query()->updateOrCreate(
                        [
                            'section_id' => $section->id,
                            'position' => $position,
                        ],
                        [
                            'type' => $block['type'],
                            'data' => $block['data'] ?? [],
                            'is_active' => true,
                        ]
                    );
                }

                $section->blocks()
                    ->whereNotIn('position', $desiredPositions)
                    ->update(['is_active' => false]);
            }
        });
    }

    /**
     * @return array<string, array{title: string, slug: string, position: int, blocks: array<int, array{type: string, data: array}>}>
     */
    private function sections(): array
    {
        $path = __DIR__ . '/content/marketing_guide_sections.php';

        if (! file_exists($path)) {
            return [];
        }

        return require $path;
    }
}