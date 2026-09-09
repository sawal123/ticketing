<?php

namespace Tests\Feature;

use App\Livewire\Admin\MarketingGuideContentModal;
use App\Models\MarketingGuideBlock;
use App\Models\MarketingGuideVersion;
use App\Models\User;
use App\Services\MarketingGuide\MarketingGuideContentService;
use Database\Seeders\MarketingGuideContentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class MarketingGuideEditorPerformanceTest extends TestCase
{
    use RefreshDatabase;

    #[DataProvider('modalActions')]
    public function test_modal_open_is_scoped_and_does_not_render_or_mutate_the_full_editor(string $action): void
    {
        $admin = User::factory()->create([
            'uid' => (string) Str::uuid(),
            'role' => 'admin',
            'password' => 'Password123',
        ]);
        $this->seed(MarketingGuideContentSeeder::class);
        $draft = app(MarketingGuideContentService::class)->getOrCreateDraft($admin);
        $section = $draft->sections()->where('key', 'cara_kerja')->firstOrFail();
        $block = $section->blocks()->where('type', MarketingGuideBlock::TYPE_WORKFLOW)->firstOrFail();
        $component = Livewire::actingAs($admin)->test(MarketingGuideContentModal::class);

        $queries = [];
        DB::listen(function ($query) use (&$queries): void {
            $queries[] = $query->sql;
        });

        $startedAt = hrtime(true);
        $component->call($action, $action === 'openBlockEditor' ? $block->id : $section->id);
        $durationMs = (hrtime(true) - $startedAt) / 1_000_000;
        $effects = $component->effects;
        $html = $effects['html'] ?? '';
        $responseBytes = strlen((string) json_encode([
            'components' => [[
                'snapshot' => json_encode($component->snapshot),
                'effects' => $effects,
            ]],
        ]));

        $mutationQueries = array_values(array_filter(
            $queries,
            fn (string $sql): bool => preg_match('/^\s*(insert|update|delete)\b/i', $sql) === 1
        ));

        $this->assertLessThanOrEqual(4, count($queries), $action.' issued redundant queries.');
        $this->assertLessThan(65_536, $responseBytes, $action.' returned an oversized response.');
        $this->assertLessThan(50_000, strlen($html), $action.' rendered too much DOM.');
        $this->assertStringNotContainsString('Editor Marketing Guide', $html);
        $this->assertStringNotContainsString('Draft aktif', $html);
        $this->assertSame([], $mutationQueries, $action.' mutated the database while opening a modal.');
        $this->assertLessThan(1000, $durationMs, $action.' exceeded the server-side latency budget.');

        match ($action) {
            'openSectionEditor' => $component
                ->assertSet('editingSectionId', $section->id)
                ->assertSet('sectionTitle', $section->title),
            'openBlockEditor' => $component
                ->assertSet('editingBlockId', $block->id)
                ->assertSet('blockType', MarketingGuideBlock::TYPE_WORKFLOW)
                ->assertSet('blockDataRaw', json_encode(
                    $block->data,
                    JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
                )),
            'openAddBlock' => $component
                ->assertSet('addingBlockForSectionId', $section->id)
                ->assertSet('editingBlockId', null)
                ->assertSet('blockType', MarketingGuideBlock::TYPE_TEXT),
        };
    }

    public static function modalActions(): array
    {
        return [
            'edit section' => ['openSectionEditor'],
            'edit block' => ['openBlockEditor'],
            'add block' => ['openAddBlock'],
        ];
    }

    public function test_modal_component_edit_add_and_save_still_persist(): void
    {
        [$admin, $draft] = $this->editorFixture();
        $section = $draft->sections()->where('key', 'cara_kerja')->firstOrFail();
        $textBlock = $section->blocks()->where('type', MarketingGuideBlock::TYPE_TEXT)->firstOrFail();
        $component = Livewire::actingAs($admin)->test(MarketingGuideContentModal::class);

        $component->call('openSectionEditor', $section->id)
            ->set('sectionTitle', 'Section cepat')
            ->call('saveSection')
            ->assertSet('successMessage', 'Section berhasil diperbarui.');
        $this->assertSame('Section cepat', $section->fresh()->title);

        $component->call('openBlockEditor', $textBlock->id)
            ->set('blockDataRaw', json_encode(['intro' => 'Block cepat']))
            ->set('blockIsActive', true)
            ->call('saveBlock')
            ->assertSet('successMessage', 'Block berhasil diperbarui.');
        $this->assertSame('Block cepat', $textBlock->fresh()->data['intro']);

        $countBefore = $section->blocks()->count();
        $component->call('openAddBlock', $section->id)
            ->set('blockType', MarketingGuideBlock::TYPE_STATS)
            ->set('blockDataRaw', json_encode(['stats' => [['value' => '1', 'label' => 'Cepat']]]))
            ->call('saveBlock')
            ->assertSet('successMessage', 'Block baru ditambahkan.');
        $this->assertSame($countBefore + 1, $section->blocks()->count());
        $this->assertDatabaseHas('marketing_guide_blocks', [
            'section_id' => $section->id,
            'type' => MarketingGuideBlock::TYPE_STATS,
            'is_active' => true,
        ]);
    }

    public function test_modal_component_cannot_open_or_mutate_published_content(): void
    {
        [$admin, $draft] = $this->editorFixture();
        $published = app(MarketingGuideContentService::class)->currentVersion();
        $section = $published->sections()->firstOrFail();
        $block = $section->blocks()->firstOrFail();
        $beforeSection = $section->getAttributes();
        $beforeBlock = $block->getAttributes();
        $component = Livewire::actingAs($admin)->test(MarketingGuideContentModal::class);

        $component->call('openSectionEditor', $section->id)
            ->assertSet('editingSectionId', null)
            ->set('editingSectionId', $section->id)
            ->set('sectionTitle', 'Published tamper')
            ->call('saveSection');
        $component->call('openBlockEditor', $block->id)
            ->assertSet('editingBlockId', null)
            ->set('editingBlockId', $block->id)
            ->set('blockDataRaw', json_encode(['intro' => 'Published tamper']))
            ->call('saveBlock');

        $this->assertSame($beforeSection, $section->fresh()->getAttributes());
        $this->assertSame($beforeBlock, $block->fresh()->getAttributes());
        $this->assertSame($draft->id, app(MarketingGuideContentService::class)->findDraft()->id);
    }

    public function test_editor_page_routes_modal_buttons_to_the_small_modal_component(): void
    {
        [$admin] = $this->editorFixture();

        $this->actingAs($admin)->get(route('admin.marketing-guide.content'))
            ->assertOk()
            ->assertSeeLivewire(MarketingGuideContentModal::class)
            ->assertSee('mge-open-section-editor', false)
            ->assertSee('mge-open-block-editor', false)
            ->assertSee('mge-open-add-block', false);
    }

    /** @return array{User, MarketingGuideVersion} */
    private function editorFixture(): array
    {
        $admin = User::factory()->create([
            'uid' => (string) Str::uuid(),
            'role' => 'admin',
            'password' => 'Password123',
        ]);
        $this->seed(MarketingGuideContentSeeder::class);
        $draft = app(MarketingGuideContentService::class)->getOrCreateDraft($admin);

        return [$admin, $draft];
    }
}
