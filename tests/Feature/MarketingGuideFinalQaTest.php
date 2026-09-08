<?php

namespace Tests\Feature;

use App\Livewire\Admin\MarketingGuideContentEditor;
use App\Models\MarketingGuideVersion;
use App\Models\User;
use App\Services\MarketingGuide\MarketingGuideAccessService;
use App\Services\MarketingGuide\MarketingGuideContentService;
use Database\Seeders\MarketingGuideContentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class MarketingGuideFinalQaTest extends TestCase
{
    use RefreshDatabase;

    private MarketingGuideContentService $service;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(MarketingGuideContentService::class);
        $this->admin = User::factory()->create([
            'uid' => (string) Str::uuid(), 'role' => 'admin', 'password' => 'Password123',
        ]);
        $this->seed(MarketingGuideContentSeeder::class);
    }

    public function test_reordering_blocks_with_high_positions_preserves_content(): void
    {
        $draft = $this->service->getOrCreateDraft($this->admin);
        $section = $draft->sections()->first();
        $blocks = $section->blocks()->get();
        foreach ($blocks as $index => $block) {
            $block->update(['position' => 1001 + $index]);
        }
        $before = $blocks->mapWithKeys(fn ($block) => [$block->id => $block->data])->all();
        $ids = $blocks->pluck('id')->reverse()->values()->all();

        $this->service->reorderBlocks($section, $ids, $this->admin);

        $ordered = $section->blocks()->get();
        $this->assertSame($ids, $ordered->pluck('id')->all());
        $this->assertSame(range(1, count($ids)), $ordered->pluck('position')->all());
        foreach ($ordered as $block) {
            $this->assertSame($before[$block->id], $block->data);
        }
    }

    #[DataProvider('editorActions')]
    public function test_content_mutations_recheck_admin_role_from_database(string $action): void
    {
        $draft = $this->service->getOrCreateDraft($this->admin);
        $section = $draft->sections()->first();
        $block = $section->blocks()->first();
        $before = $draft->fresh()->load('sections.blocks')->toArray();
        $this->actingAs($this->admin);
        User::whereKey($this->admin->id)->update(['role' => 'user']);
        $this->assertSame('admin', $this->admin->role);

        try {
            if ($action === 'livewire') {
                $editor = new MarketingGuideContentEditor;
                $editor->boot($this->service);
                $editor->openSectionEditor($section->id);
                $editor->sectionTitle = 'Unauthorized title';
                $editor->saveSection();
            } else {
                match ($action) {
                    'clone' => $this->service->getOrCreateDraft($this->admin),
                    'section' => $this->service->updateSection($section, ['title' => 'Unauthorized'], $this->admin),
                    'sections order' => $this->service->reorderSections($draft, [$section->id], $this->admin),
                    'block' => $this->service->updateBlock($block, ['intro' => 'Unauthorized'], true, $this->admin),
                    'add' => $this->service->addBlock($section, 'text', ['intro' => 'Unauthorized'], true, $this->admin),
                    'remove' => $this->service->removeBlock($block, $this->admin),
                    'blocks order' => $this->service->reorderBlocks($section, [$block->id], $this->admin),
                };
            }
            $this->fail('A demoted admin was allowed to edit the guide.');
        } catch (HttpException $e) {
            $this->assertSame(403, $e->getStatusCode());
        }

        $this->assertSame($before, $draft->fresh()->load('sections.blocks')->toArray());
    }

    public static function editorActions(): array
    {
        return array_map(fn ($action) => [$action], [
            'clone', 'section', 'sections order', 'block', 'add', 'remove', 'blocks order', 'livewire',
        ]);
    }

    #[DataProvider('publicModes')]
    public function test_public_security_and_access_metadata_in_dynamic_and_fallback_modes(bool $dynamic): void
    {
        $published = $this->service->currentVersion();
        $draft = $this->service->getOrCreateDraft($this->admin);
        $draft->sections()->first()->blocks()->first()->update(['data' => ['intro' => 'DG5 secret draft']]);
        if (! $dynamic) {
            $published->update(['status' => MarketingGuideVersion::STATUS_ARCHIVED]);
        }
        $accessService = app(MarketingGuideAccessService::class);
        $access = $accessService->create($this->admin, now()->addDays(3), 'DG5 recipient');

        $response = $this->get(route('marketing-guide.show', $access['token']));
        $response->assertOk()->assertViewIs($dynamic ? 'marketing-guide.dynamic' : 'marketing-guide.index')
            ->assertSee('DG5 recipient')->assertDontSee('DG5 secret draft')
            ->assertSee('name="robots" content="noindex, nofollow, noarchive"', false)
            ->assertDontSee($access['token'])->assertDontSee($access['access']->token_hash)
            ->assertDontSee('localStorage')->assertDontSee('sessionStorage');
        $this->assertSame('DG5 recipient', $response->viewData('recipientName'));
        $this->assertTrue($access['access']->expires_at->equalTo($response->viewData('expiresAt')));
        $this->assertSame(1, $access['access']->fresh()->access_count);
        $this->assertNotNull($access['access']->fresh()->last_accessed_at);

        foreach ([
            'X-Robots-Tag' => 'noindex, nofollow, noarchive',
            'Pragma' => 'no-cache', 'Referrer-Policy' => 'no-referrer',
            'X-Content-Type-Options' => 'nosniff', 'X-Frame-Options' => 'DENY',
            'Permissions-Policy' => 'camera=(), microphone=(), geolocation=()',
        ] as $header => $value) {
            $response->assertHeader($header, $value);
        }
        foreach (['private', 'no-store', 'no-cache', 'must-revalidate', 'max-age=0'] as $directive) {
            $this->assertStringContainsString($directive, $response->headers->get('Cache-Control'));
        }
    }

    public static function publicModes(): array
    {
        return ['dynamic' => [true], 'static fallback with draft' => [false]];
    }

    public function test_public_renders_all_ten_block_types_and_active_navigation_in_order(): void
    {
        $draft = $this->service->getOrCreateDraft($this->admin);
        $orderedIds = $draft->sections()->orderByDesc('position')->pluck('id')->all();
        $this->service->reorderSections($draft, $orderedIds, $this->admin);
        $this->service->publishDraft($this->admin);
        $access = app(MarketingGuideAccessService::class)->create($this->admin, now()->addDay());

        $response = $this->get(route('marketing-guide.show', $access['token']))->assertOk();
        $sections = $response->viewData('sections');
        $this->assertSame($orderedIds, $sections->pluck('id')->all());
        $this->assertEqualsCanonicalizing(MarketingGuideContentService::BLOCK_TYPES, $sections
            ->flatMap(fn ($section) => $section->activeBlocks->pluck('type'))->unique()->values()->all());
        foreach (['badge', 'workflow', 'flow-diagram', 'grid grid-', 'placeholder', 'ticket-card',
            'stats-grid', 'qr-mockup', 'faq-container', 'cta-content'] as $class) {
            $response->assertSee('class="'.$class, false);
        }

        $document = new \DOMDocument;
        @$document->loadHTML($response->getContent());
        $xpath = new \DOMXPath($document);
        $sectionIds = array_map(fn ($node) => $node->getAttribute('id'), iterator_to_array($xpath->query('//main/section')));
        $this->assertSame($sections->pluck('slug')->all(), $sectionIds);
        $groups = $sections->groupBy('nav_group');
        foreach (['sidebar', 'drawer'] as $container) {
            $anchors = $xpath->query('//*[@id="'.$container.'"]//a[@data-id]');
            $this->assertSame($groups->flatten(1)->pluck('slug')->all(), array_map(
                fn ($node) => $node->getAttribute('data-id'), iterator_to_array($anchors)
            ));
            foreach ($anchors as $anchor) {
                $this->assertSame('#'.$anchor->getAttribute('data-id'), $anchor->getAttribute('href'));
                $this->assertContains($anchor->getAttribute('data-id'), $sectionIds);
            }
        }
    }

    public function test_tampered_livewire_ids_cannot_mutate_published_sections_or_blocks(): void
    {
        $published = $this->service->currentVersion();
        $this->service->getOrCreateDraft($this->admin);
        $section = $published->sections()->first();
        $block = $section->blocks()->first();
        $before = $published->fresh()->load('sections.blocks')->toArray();

        Livewire::actingAs($this->admin)->test(MarketingGuideContentEditor::class)
            ->set('editingSectionId', $section->id)->set('sectionTitle', 'Tampered section')
            ->call('saveSection')->call('toggleSectionActive', $section->id)
            ->call('moveSectionUp', $section->id)->call('moveSectionDown', $section->id)
            ->set('editingBlockId', $block->id)->set('blockDataRaw', '{"intro":"Tampered block"}')
            ->call('saveBlock')->call('removeBlock', $block->id)
            ->call('moveBlockUp', $block->id)->call('moveBlockDown', $block->id)
            ->set('editingBlockId', null)->set('addingBlockForSectionId', $section->id)
            ->call('saveBlock');

        $this->assertSame($before, $published->fresh()->load('sections.blocks')->toArray());
    }

    public function test_logo_targets_first_active_section_after_hero_is_disabled(): void
    {
        $draft = $this->service->getOrCreateDraft($this->admin);
        $draft->sections()->where('slug', 'hero')->update(['is_active' => false]);
        $first = $this->service->activeSectionsForVersion($draft)->first();
        $this->assertNotSame('hero', $first->slug);

        $this->actingAs($this->admin)->get(route('admin.marketing-guide.content.preview'))
            ->assertOk()->assertSee('href="#'.$first->slug.'" class="logo"', false);

        $this->service->publishDraft($this->admin);
        $access = app(MarketingGuideAccessService::class)->create($this->admin, now()->addDay());
        $this->get(route('marketing-guide.show', $access['token']))
            ->assertOk()->assertSee('href="#'.$first->slug.'" class="logo"', false);
    }
}
