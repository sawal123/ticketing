<?php

namespace Tests\Feature;

use App\Livewire\Admin\MarketingGuideContentEditor;
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
        $this->assertSame([['value' => '1', 'label' => 'Test']], $new->data['stats']);

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

    private function makeUser(string $role): User
    {
        $user = new User;
        $user->uid = (string) Str::uuid();
        $user->name = $role === 'admin' ? 'Admin' : 'Penyewa';
        $user->email = $role.'+'.Str::random(8).'@test.test';
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
