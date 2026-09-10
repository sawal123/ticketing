<?php

namespace Tests\Feature;

use App\Livewire\Admin\MarketingGuideContentEditor;
use App\Livewire\Admin\MarketingGuideContentModal;
use App\Models\MarketingGuideBlock;
use App\Models\MarketingGuideVersion;
use App\Models\User;
use App\Services\MarketingGuide\MarketingGuideContentService;
use Database\Seeders\MarketingGuideContentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class MarketingGuideContentEditorTest extends TestCase
{
    use RefreshDatabase;

    private MarketingGuideContentService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(MarketingGuideContentService::class);
    }

    public function test_guest_cannot_open_editor_page(): void
    {
        $this->get(route('admin.marketing-guide.content'))
            ->assertRedirect('/login');
    }

    public function test_non_admin_cannot_open_editor_page(): void
    {
        $user = $this->makeUser('user');
        $this->actingAs($user)
            ->get(route('admin.marketing-guide.content'))
            ->assertStatus(302);
    }

    public function test_admin_can_open_editor_page(): void
    {
        $admin = $this->makeUser('admin');
        $this->seed(MarketingGuideContentSeeder::class);
        $this->actingAs($admin)
            ->get(route('admin.marketing-guide.content'))
            ->assertOk()
            ->assertSeeLivewire(MarketingGuideContentEditor::class);
    }

    public function test_draft_is_created_from_published_and_reused_on_subsequent_calls(): void
    {
        $this->seed(MarketingGuideContentSeeder::class);
        $admin = $this->makeUser('admin');

        Livewire::actingAs($admin)
            ->test(MarketingGuideContentEditor::class)
            ->call('openEditor')
            ->assertHasNoErrors();

        $firstDraft = $this->service->findDraft();
        $this->assertNotNull($firstDraft);

        $this->assertSame(14, $firstDraft->sections()->count());
        $totalBlocks = MarketingGuideBlock::query()
            ->whereIn('section_id', $firstDraft->sections()->pluck('id'))
            ->count();
        $this->assertSame(38, $totalBlocks);

        // Calling openEditor again must reuse the existing draft, not
        // produce a new one.
        Livewire::actingAs($admin)
            ->test(MarketingGuideContentEditor::class)
            ->call('openEditor')
            ->assertHasNoErrors();

        $this->assertSame(
            $firstDraft->id,
            $this->service->findDraft()->id
        );
        $this->assertSame(1, MarketingGuideVersion::query()
            ->where('status', MarketingGuideVersion::STATUS_DRAFT)
            ->count());
    }

    public function test_draft_clone_does_not_modify_published_version(): void
    {
        $this->seed(MarketingGuideContentSeeder::class);
        $admin = $this->makeUser('admin');

        $published = $this->service->currentVersion();
        $publishedSnap = [
            'id' => $published->id,
            'key' => $published->key,
            'title' => $published->title,
            'published_at' => $published->published_at?->toIso8601String(),
            'sections' => $published->sections()->count(),
            'blocks' => MarketingGuideBlock::query()
                ->whereIn('section_id', $published->sections()->pluck('id'))
                ->count(),
        ];

        Livewire::actingAs($admin)
            ->test(MarketingGuideContentEditor::class)
            ->call('openEditor')
            ->call('toggleSectionActive', $this->service->findDraft()->sections()->first()->id);

        $published->refresh();
        $this->assertSame($publishedSnap['id'], $published->id);
        $this->assertSame($publishedSnap['key'], $published->key);
        $this->assertSame($publishedSnap['title'], $published->title);
        $this->assertSame($publishedSnap['published_at'], $published->published_at?->toIso8601String());
        $this->assertSame($publishedSnap['sections'], $published->sections()->count());
        $this->assertSame($publishedSnap['blocks'], MarketingGuideBlock::query()
            ->whereIn('section_id', $published->sections()->pluck('id'))
            ->count());
    }

    public function test_admin_can_edit_section_metadata_on_draft(): void
    {
        $this->seed(MarketingGuideContentSeeder::class);
        $admin = $this->makeUser('admin');

        $draft = $this->makeOrGetDraft($admin);
        $section = $draft->sections()->where('key', 'pengenalan')->first();
        $this->assertNotNull($section);

        Livewire::actingAs($admin)
            ->test(MarketingGuideContentEditor::class)
            ->set('sectionTitle', 'Pengenalan Diedit')
            ->set('sectionNavGroup', 'MENGENAL GOTIK v2')
            ->set('sectionIsActive', true)
            ->set('sectionPosition', 99)
            ->call('openSectionEditor', $section->id)
            ->set('sectionTitle', 'Pengenalan Diedit')
            ->set('sectionNavGroup', 'MENGENAL GOTIK v2')
            ->set('sectionIsActive', true)
            ->set('sectionPosition', 99)
            ->call('saveSection')
            ->assertHasNoErrors();

        $fresh = $section->fresh();
        $this->assertSame('Pengenalan Diedit', $fresh->title);
        $this->assertSame('MENGENAL GOTIK v2', $fresh->nav_group);
        $this->assertSame(99, (int) $fresh->position);
        $this->assertTrue($fresh->is_active);
    }

    public function test_section_reorder_persists_new_positions(): void
    {
        $this->seed(MarketingGuideContentSeeder::class);
        $admin = $this->makeUser('admin');
        $draft = $this->makeOrGetDraft($admin);

        $firstId = $draft->sections()->reorder()->orderBy('position')->value('id');
        Livewire::actingAs($admin)
            ->test(MarketingGuideContentEditor::class)
            ->call('moveSectionDown', $firstId)
            ->assertHasNoErrors();

        $positions = $draft->fresh()->sections()->reorder()->orderBy('position')->pluck('id')->all();
        $this->assertNotSame($firstId, $positions[0]);
        $this->assertSame($firstId, $positions[1]);
    }

    public function test_block_active_toggle_and_reorder(): void
    {
        $this->seed(MarketingGuideContentSeeder::class);
        $admin = $this->makeUser('admin');
        $draft = $this->makeOrGetDraft($admin);
        $section = $draft->sections()->where('key', 'pengenalan')->first();
        $block = $section->blocks()->orderBy('position')->first();

        Livewire::actingAs($admin)
            ->test(MarketingGuideContentEditor::class)
            ->call('removeBlock', $block->id)
            ->assertHasNoErrors();
        $this->assertFalse($block->fresh()->is_active);

        $this->assertTrue(MarketingGuideBlock::query()->where('id', $block->id)->exists());

        // Now move a different block to the front. Use a section
        // with multiple blocks so we have something else to move.
        $section = $draft->sections()->where('key', 'cara_kerja')->first();
        $first = $section->blocks()->reorder()->orderBy('position')->first();
        $other = $section->blocks()->reorder()->orderBy('position')->where('id', '<>', $first->id)->first();
        $this->assertNotNull($other);
        Livewire::actingAs($admin)
            ->test(MarketingGuideContentEditor::class)
            ->call('moveBlockUp', $other->id)
            ->assertHasNoErrors();
        $this->assertSame(1, (int) $other->fresh()->position);
    }

    public function test_admin_can_add_edit_and_remove_block(): void
    {
        $this->seed(MarketingGuideContentSeeder::class);
        $admin = $this->makeUser('admin');
        $draft = $this->makeOrGetDraft($admin);
        $section = $draft->sections()->where('key', 'pengenalan')->first();

        $countBefore = $section->blocks()->count();

        Livewire::actingAs($admin)
            ->test(MarketingGuideContentEditor::class)
            ->call('openAddBlock', $section->id)
            ->set('blockType', 'stats')
            ->set('blockIsActive', true)
            ->set('blockDataRaw', json_encode(['stats' => [['value' => '1', 'label' => 'Test']]]))
            ->call('saveBlock')
            ->assertHasNoErrors();

        $this->assertSame($countBefore + 1, $section->blocks()->count());
        $new = $section->blocks()->reorder()->orderByDesc('position')->orderByDesc('id')->first();
        $this->assertSame('stats', $new->type);
        $this->assertTrue($new->is_active);
        $this->assertCount(1, $new->data['stats']);
        $this->assertSame('1', $new->data['stats'][0]['value']);
        $this->assertSame('Test', $new->data['stats'][0]['label']);

        Livewire::actingAs($admin)
            ->test(MarketingGuideContentEditor::class)
            ->set('blockDataRaw', json_encode(['stats' => [['value' => '2', 'label' => 'Updated']]]))
            ->set('blockIsActive', true)
            ->call('openBlockEditor', $new->id)
            ->set('blockDataRaw', json_encode(['stats' => [['value' => '2', 'label' => 'Updated']]]))
            ->set('blockIsActive', true)
            ->call('saveBlock')
            ->assertHasNoErrors();
        $this->assertSame('Updated', $new->fresh()->data['stats'][0]['label']);

        Livewire::actingAs($admin)
            ->test(MarketingGuideContentEditor::class)
            ->call('removeBlock', $new->id)
            ->assertHasNoErrors();
        $this->assertFalse($new->fresh()->is_active);
        $this->assertTrue(MarketingGuideBlock::query()->where('id', $new->id)->exists());
    }

    public function test_invalid_block_type_is_rejected(): void
    {
        $this->seed(MarketingGuideContentSeeder::class);
        $admin = $this->makeUser('admin');

        $this->expectException(\InvalidArgumentException::class);
        $this->service->assertValidBlockType('dangerous');
    }

    public function test_invalid_icon_is_rejected_on_update(): void
    {
        $this->seed(MarketingGuideContentSeeder::class);
        $admin = $this->makeUser('admin');
        $draft = $this->makeOrGetDraft($admin);
        $section = $draft->sections()->where('key', 'pengenalan')->first();
        $block = $section->blocks()->orderBy('position')->first();

        $this->expectException(\InvalidArgumentException::class);
        $this->service->updateBlock(
            $block,
            ['icon' => 'javascript:alert(1)'],
            true,
            $admin
        );
    }

    public function test_unsafe_href_is_rejected_on_update(): void
    {
        $this->seed(MarketingGuideContentSeeder::class);
        $admin = $this->makeUser('admin');
        $draft = $this->makeOrGetDraft($admin);
        $section = $draft->sections()->where('key', 'pengenalan')->first();
        $block = $section->blocks()->orderBy('position')->first();

        $this->expectException(\InvalidArgumentException::class);
        $this->service->updateBlock(
            $block,
            ['cta' => ['label' => 'X', 'href' => 'javascript:alert(1)']],
            true,
            $admin
        );
    }

    public function test_safe_href_variants_are_accepted(): void
    {
        $this->seed(MarketingGuideContentSeeder::class);
        $admin = $this->makeUser('admin');
        $draft = $this->makeOrGetDraft($admin);
        $section = $draft->sections()->where('key', 'pengenalan')->first();
        $block = $section->blocks()->orderBy('position')->first();

        foreach (['#section', 'mailto:foo@bar.test', 'https://example.test/path'] as $href) {
            $this->service->updateBlock(
                $block,
                ['cta' => ['label' => 'X', 'href' => $href]],
                true,
                $admin
            );
            $this->assertSame($href, $block->fresh()->data['cta']['href']);
        }
    }

    public function test_non_admin_cannot_invoke_livewire_actions(): void
    {
        $this->seed(MarketingGuideContentSeeder::class);
        $user = $this->makeUser('user');

        // The middleware redirects; the component's defense-in-depth
        // ensureAdmin() aborts with 403. Either response means the
        // request was refused, which is what we want.
        $this->actingAs($user)
            ->get(route('admin.marketing-guide.content'))
            ->assertStatus(302);
    }

    public function test_nested_items_can_be_edited_in_place(): void
    {
        $this->seed(MarketingGuideContentSeeder::class);
        $admin = $this->makeUser('admin');
        $draft = $this->makeOrGetDraft($admin);
        $section = $draft->sections()->where('key', 'cara_kerja')->first();
        $this->assertNotNull($section);
        $block = $section->blocks()->where('type', 'workflow')->first();
        $this->assertNotNull($block);

        $newData = [
            'steps' => [
                ['icon' => 'edit', 'title' => 'Edit langkah', 'description' => 'Edit deskripsi'],
                ['icon' => 'rocket', 'title' => 'Baru', 'description' => 'Item tambahan'],
            ],
        ];
        $this->service->updateBlock($block, $newData, true, $admin);

        $fresh = $block->fresh();
        $this->assertCount(2, $fresh->data['steps']);
        $this->assertSame('Edit langkah', $fresh->data['steps'][0]['title']);
        $this->assertSame('rocket', $fresh->data['steps'][1]['icon']);
    }

    public function test_get_or_create_draft_throws_when_published_missing(): void
    {
        $admin = $this->makeUser('admin');
        $this->expectException(\InvalidArgumentException::class);
        $this->service->getOrCreateDraft($admin);
    }

    public function test_update_on_published_version_is_rejected(): void
    {
        $this->seed(MarketingGuideContentSeeder::class);
        $admin = $this->makeUser('admin');
        $published = $this->service->currentVersion();
        $section = $published->sections()->first();
        $this->assertNotNull($section);

        $this->expectException(\InvalidArgumentException::class);
        $this->service->updateSection(
            $section,
            ['title' => 'Should fail'],
            $admin
        );
    }

    public function test_raw_json_textarea_is_not_rendered_in_editor(): void
    {
        $this->seed(MarketingGuideContentSeeder::class);
        $admin = $this->makeUser('admin');
        $draft = $this->makeOrGetDraft($admin);
        $section = $draft->sections()->where('key', 'cta_hubungi')->first();
        $block = $section->blocks()->where('type', 'cta')->first();

        Livewire::actingAs($admin)
            ->test(MarketingGuideContentEditor::class)
            ->call('openBlockEditor', $block->id)
            ->assertDontSee('Lihat JSON mentah')
            ->assertDontSee('x-model="raw"');
    }

    public function test_cta_block_saves_dg1_nested_payload(): void
    {
        $this->seed(MarketingGuideContentSeeder::class);
        $admin = $this->makeUser('admin');
        $draft = $this->makeOrGetDraft($admin);
        $section = $draft->sections()->where('key', 'cta_hubungi')->first();

        $payload = [
            'title' => 'Judul CTA Test',
            'subtitle' => 'Subtitle CTA Test',
            'cta' => [
                'label' => 'Hubungi Tim',
                'href' => 'mailto:hello@gotik.io',
                'icon' => 'envelope',
                'variant' => 'cta',
            ],
        ];

        Livewire::actingAs($admin)
            ->test(MarketingGuideContentEditor::class)
            ->call('openAddBlock', $section->id)
            ->set('blockType', 'cta')
            ->set('blockIsActive', true)
            ->set('blockDataRaw', json_encode($payload))
            ->call('saveBlock')
            ->assertSet('errorMessage', '')
            ->assertSet('successMessage', 'Block baru ditambahkan.');

        $ctaBlocks = $section->blocks()->where('type', 'cta')->orderBy('id')->get();
        $this->assertCount(2, $ctaBlocks);
        $block = $ctaBlocks->last();
        $this->assertNotNull($block);
        $this->assertSame('Judul CTA Test', $block->data['title']);
        $this->assertSame('Subtitle CTA Test', $block->data['subtitle']);
        $this->assertSame('Hubungi Tim', $block->data['cta']['label']);
        $this->assertSame('mailto:hello@gotik.io', $block->data['cta']['href']);
        $this->assertSame('envelope', $block->data['cta']['icon']);
        $this->assertSame('cta', $block->data['cta']['variant']);
    }

    public function test_cta_form_uses_dg1_nested_fields_when_editing(): void
    {
        $this->seed(MarketingGuideContentSeeder::class);
        $admin = $this->makeUser('admin');
        $draft = $this->makeOrGetDraft($admin);
        $section = $draft->sections()->where('key', 'cta_hubungi')->first();
        $block = $section->blocks()->where('type', 'cta')->first();

        Livewire::actingAs($admin)
            ->test(MarketingGuideContentModal::class)
            ->call('openBlockEditor', $block->id)
            ->assertSet('blockType', 'cta')
            ->assertSee('x-model="data.title"', false)
            ->assertSee('x-model="data.subtitle"', false)
            ->assertSee('x-model="data.cta.label"', false)
            ->assertSee('x-model="data.cta.href"', false)
            ->assertSee('x-model="data.cta.icon"', false)
            ->assertSee('x-model="data.cta.variant"', false);
    }

    public function test_workflow_and_flow_bind_their_own_item_field(): void
    {
        $this->seed(MarketingGuideContentSeeder::class);
        $admin = $this->makeUser('admin');
        $draft = $this->makeOrGetDraft($admin);

        // workflow items bind to item.title, never to a combined expression.
        $workflow = $draft->sections()->where('key', 'cara_kerja')->first()
            ->blocks()->where('type', 'workflow')->first();
        Livewire::actingAs($admin)
            ->test(MarketingGuideContentModal::class)
            ->call('openBlockEditor', $workflow->id)
            ->assertSee('x-model="item.title"', false)
            ->assertDontSee('item.title || item.label', false);

        // flow items bind to item.label.
        $flow = $draft->sections()->where('key', 'menjadi_penyelenggara')->first()
            ->blocks()->where('type', 'flow')->first();
        Livewire::actingAs($admin)
            ->test(MarketingGuideContentModal::class)
            ->call('openBlockEditor', $flow->id)
            ->assertSee('x-model="item.label"', false)
            ->assertDontSee('item.title || item.label', false);
    }

    public function test_intro_only_text_block_opens_with_cta_guard(): void
    {
        $this->seed(MarketingGuideContentSeeder::class);
        $admin = $this->makeUser('admin');
        $draft = $this->makeOrGetDraft($admin);

        // Seed text blocks such as the cara_kerja intro only carry `intro`
        // (no nested cta). They must open without a client-side error, so
        // the editor normalizes data.cta before binding data.cta.*.
        $section = $draft->sections()->where('key', 'cara_kerja')->first();
        $block = $section->blocks()->where('type', 'text')->first();
        $this->assertArrayHasKey('intro', $block->data);
        $this->assertArrayNotHasKey('cta', $block->data);

        Livewire::actingAs($admin)
            ->test(MarketingGuideContentModal::class)
            ->call('openBlockEditor', $block->id)
            ->assertSet('blockType', 'text')
            // Guard exists and CTA fields are still rendered for text.
            ->assertSee('ensureCtaDefaults')
            ->assertSee('x-model="data.cta.label"', false)
            ->assertSee('x-model="data.cta.href"', false)
            ->assertSee('x-model="data.cta.icon"', false);

        // Opening the editor must not mutate the stored payload.
        $this->assertArrayNotHasKey('cta', $block->fresh()->data);
        $this->assertSame($block->data, $block->fresh()->data);
    }

    public function test_existing_block_type_is_locked_while_editing(): void
    {
        $this->seed(MarketingGuideContentSeeder::class);
        $admin = $this->makeUser('admin');
        $draft = $this->makeOrGetDraft($admin);
        $section = $draft->sections()->where('key', 'cara_kerja')->first();
        $block = $section->blocks()->where('type', 'workflow')->first();

        Livewire::actingAs($admin)
            ->test(MarketingGuideContentModal::class)
            ->call('openBlockEditor', $block->id)
            ->assertSet('blockType', 'workflow')
            // The type selector must not be offered while editing.
            ->assertDontSee('wire:change="switchBlockType', false)
            ->assertSee('Tipe terkunci saat edit')
            // Attempting to switch type while editing is refused.
            ->call('switchBlockType', 'stats')
            ->assertSet('blockType', 'workflow')
            ->assertSet('errorMessage', 'Tipe block tidak dapat diubah. Hapus block lalu tambahkan block baru dengan tipe yang diinginkan.');

        $this->assertSame('workflow', $block->fresh()->type);
    }

    public function test_payload_of_another_type_cannot_be_saved_onto_existing_block(): void
    {
        $this->seed(MarketingGuideContentSeeder::class);
        $admin = $this->makeUser('admin');
        $draft = $this->makeOrGetDraft($admin);
        $section = $draft->sections()->where('key', 'cara_kerja')->first();
        $block = $section->blocks()->where('type', 'workflow')->first();

        Livewire::actingAs($admin)
            ->test(MarketingGuideContentEditor::class)
            ->call('openBlockEditor', $block->id)
            // Client tampers with the type property and submits a stats
            // payload. The block type comes from the DB row, so this must
            // be rejected and the block must keep its steps payload.
            ->set('blockType', 'stats')
            ->set('blockDataRaw', json_encode(['stats' => [['value' => '1', 'label' => 'X']]]))
            ->set('blockIsActive', true)
            ->call('saveBlock')
            ->assertSet('blockType', 'workflow')
            ->assertSet('errorMessage', 'Payload block "workflow" wajib memiliki field "steps".');

        $fresh = $block->fresh();
        $this->assertSame('workflow', $fresh->type);
        $this->assertArrayHasKey('steps', $fresh->data);
        $this->assertArrayNotHasKey('stats', $fresh->data);
    }

    public function test_malformed_payload_per_type_is_rejected(): void
    {
        $this->seed(MarketingGuideContentSeeder::class);
        $admin = $this->makeUser('admin');
        $draft = $this->makeOrGetDraft($admin);
        $section = $draft->sections()->where('key', 'cara_kerja')->first();

        $malformed = [
            'text' => ['title' => ['bukan string']],
            'workflow' => ['steps' => 'bukan list'],
            'workflow_list_item' => ['steps' => ['a', 'b']],
            'flow' => ['boxes' => ['bukan objek']],
            'cards' => ['columns' => 5, 'cards' => []],
            'cards_missing' => ['cards' => []],
            'stats' => ['stats' => [['value' => ['nested'], 'label' => 'X']]],
            'tickets' => ['tickets' => 'bukan list'],
            'faq' => ['items' => [['question' => ['nested'], 'answer' => 'A']]],
            'qr' => ['event_name' => ['nested']],
            'cta' => ['title' => 'T', 'subtitle' => 'S', 'cta' => 'bukan objek'],
        ];

        foreach ($malformed as $type => $data) {
            try {
                $this->service->addBlock($section, str_contains($type, '_') ? explode('_', $type)[0] : $type, $data, true, $admin);
                $this->fail("Payload malformed untuk type {$type} seharusnya ditolak.");
            } catch (\InvalidArgumentException $e) {
                $this->assertNotSame('', $e->getMessage());
            }
        }
    }

    public function test_valid_minimal_payload_per_type_is_accepted(): void
    {
        $this->seed(MarketingGuideContentSeeder::class);
        $admin = $this->makeUser('admin');
        $draft = $this->makeOrGetDraft($admin);
        $section = $draft->sections()->where('key', 'cara_kerja')->first();

        $valid = [
            'text' => ['intro' => '', 'title' => '', 'subtitle' => ''],
            'workflow' => ['steps' => [['icon' => 'edit', 'title' => 'A', 'description' => 'D']]],
            'flow' => ['boxes' => [['icon' => 'phone', 'label' => 'A']]],
            'cards' => ['columns' => 2, 'cards' => [['icon' => 'info-circle', 'title' => 'A', 'body' => 'B']]],
            'placeholder' => ['icon' => 'cog', 'title' => 'A', 'caption' => 'B'],
            'tickets' => ['tickets' => [['type' => 'A', 'price' => '1', 'quantity' => '2', 'description' => 'D']], 'tip' => ''],
            'stats' => ['stats' => [['value' => '1', 'label' => 'L']]],
            'qr' => ['event_name' => 'E', 'event_date' => '', 'icon' => 'qrcode', 'ticket_holder' => '', 'ticket_type' => '', 'ticket_number' => '', 'entry_window' => '', 'footer' => ''],
            'faq' => ['items' => [['question' => 'Q', 'answer' => 'A']]],
            'cta' => ['title' => 'T', 'subtitle' => 'S', 'cta' => ['label' => 'L', 'href' => '#x', 'icon' => 'arrow-right', 'variant' => 'primary']],
        ];

        foreach ($valid as $type => $data) {
            $block = $this->service->addBlock($section, $type, $data, true, $admin);
            $this->assertSame($type, $block->type);
        }
    }

    public function test_nested_item_reorder_persists_after_save(): void
    {
        $this->seed(MarketingGuideContentSeeder::class);
        $admin = $this->makeUser('admin');
        $draft = $this->makeOrGetDraft($admin);
        $section = $draft->sections()->where('key', 'cara_kerja')->first();
        $block = $section->blocks()->where('type', 'workflow')->first();

        $steps = $block->data['steps'];
        $this->assertGreaterThanOrEqual(2, count($steps));

        // Simulate the client "move down" on the first nested item: the
        // Alpine editor reorders the array and the whole payload is saved.
        $reordered = $steps;
        $moved = array_shift($reordered);
        $reordered[] = $moved;

        Livewire::actingAs($admin)
            ->test(MarketingGuideContentModal::class)
            ->call('openBlockEditor', $block->id)
            ->assertSee('moveItemIn(data.steps')
            ->set('blockDataRaw', json_encode(['steps' => $reordered]))
            ->set('blockIsActive', true)
            ->call('saveBlock')
            ->assertHasNoErrors();

        $stored = $block->fresh()->data['steps'];
        $this->assertCount(count($reordered), $stored);

        // Compare by semantic value (item order), not by internal JSON
        // object key order which MySQL may not preserve.
        $this->assertSame(
            array_map(fn($item) => $item['title'], $reordered),
            array_map(fn($item) => $item['title'], $stored),
        );
        $this->assertNotSame($steps[0]['title'], $stored[0]['title']);
        $this->assertSame($steps[0]['title'], $stored[count($stored) - 1]['title']);
    }

    public function test_nested_reorder_controls_rendered_for_all_container_types(): void
    {
        $this->seed(MarketingGuideContentSeeder::class);
        $admin = $this->makeUser('admin');
        $draft = $this->makeOrGetDraft($admin);

        $cases = [
            ['section' => 'cara_kerja', 'type' => 'workflow', 'expr' => 'moveItemIn(data.steps || data.boxes'],
            ['section' => 'menjadi_penyelenggara', 'type' => 'flow', 'expr' => 'moveItemIn(data.steps || data.boxes'],
            ['section' => 'pembayaran', 'type' => 'stats', 'expr' => 'moveItemIn(data.stats'],
            ['section' => 'tiket_harga', 'type' => 'tickets', 'expr' => 'moveItemIn(data.tickets'],
            ['section' => 'faq', 'type' => 'faq', 'expr' => 'moveItemIn(data.items'],
        ];

        // Cards lives on many sections; use pengenalan's sibling 'setup_event'.
        $section = $draft->sections()->where('key', 'setup_event')->first();
        $cards = $section->blocks()->where('type', 'cards')->first();

        foreach ($cases as $case) {
            $sec = $draft->sections()->where('key', $case['section'])->first();
            $block = $sec->blocks()->where('type', $case['type'])->first();
            $this->assertNotNull($block, 'missing ' . $case['type'] . ' block');
            Livewire::actingAs($admin)
                ->test(MarketingGuideContentModal::class)
                ->call('openBlockEditor', $block->id)
                ->assertSee($case['expr']);
        }

        Livewire::actingAs($admin)
            ->test(MarketingGuideContentModal::class)
            ->call('openBlockEditor', $cards->id)
            ->assertSee('moveItemIn(data.cards');
    }

    public function test_get_or_create_draft_reuses_winner_after_duplicate_key_race(): void
    {
        $this->seed(MarketingGuideContentSeeder::class);
        $admin = $this->makeUser('admin');

        // A draft already exists (the "winner" of the race committed).
        $winner = $this->service->getOrCreateDraft($admin);
        $this->assertNotNull($winner);

        // The losing request read a stale snapshot (no draft yet) and then
        // tries to clone -> duplicate key. It must retry and reuse the
        // winner instead of surfacing a unique-key error.
        $losingService = $this->createPartialMock(MarketingGuideContentService::class, ['findDraft']);
        $calls = 0;
        $losingService->method('findDraft')->willReturnCallback(function () use (&$calls, $winner) {
            $calls++;

            return $calls === 1 ? null : $winner;
        });

        $result = $losingService->getOrCreateDraft($admin);

        $this->assertSame($winner->id, $result->id);
        $this->assertGreaterThan(1, $calls);
        $this->assertSame(1, MarketingGuideVersion::query()
            ->where('status', MarketingGuideVersion::STATUS_DRAFT)
            ->count());
    }

    private function makeUser(string $role): User
    {
        $user = new User;
        $user->uid = (string) Str::uuid();
        $user->name = $role === 'admin' ? 'Admin' : 'Penyewa';
        $user->email = $role . '+' . Str::random(8) . '@test.test';
        $user->role = $role;
        $user->password = Hash::make('password');
        $user->save();

        return $user;
    }

    private function makeOrGetDraft(User $admin): MarketingGuideVersion
    {
        $draft = $this->service->findDraft();
        if ($draft !== null) {
            return $draft;
        }

        return $this->service->getOrCreateDraft($admin);
    }
}
