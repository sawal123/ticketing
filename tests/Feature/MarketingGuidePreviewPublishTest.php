<?php

namespace Tests\Feature;

use App\Http\Controllers\MarketingGuideController;
use App\Livewire\Admin\MarketingGuideContentEditor;
use App\Models\MarketingGuideAccess;
use App\Models\MarketingGuideVersion;
use App\Models\User;
use App\Services\MarketingGuide\MarketingGuideAccessService;
use App\Services\MarketingGuide\MarketingGuideContentService;
use Database\Seeders\MarketingGuideContentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class MarketingGuidePreviewPublishTest extends TestCase
{
    use RefreshDatabase;

    private MarketingGuideContentService $service;

    private User $admin;

    private MarketingGuideVersion $published;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(MarketingGuideContentService::class);
        $this->admin = $this->makeUser('admin');
        $this->seed(MarketingGuideContentSeeder::class);
        $this->published = $this->service->currentVersion();
    }

    public function test_preview_route_requires_auth_and_admin(): void
    {
        $route = app('router')->getRoutes()->getByName('admin.marketing-guide.content.preview');
        $this->assertContains('auth', $route->gatherMiddleware());
        $this->assertContains('admin', $route->gatherMiddleware());
        $this->get(route('admin.marketing-guide.content.preview'))->assertRedirect('/login');
        $this->actingAs($this->makeUser('user'))
            ->get(route('admin.marketing-guide.content.preview'))->assertRedirect('/');
    }

    #[DataProvider('unauthorizedActors')]
    public function test_preview_and_publish_defense_in_depth(?string $role, string $entry): void
    {
        $draft = $this->service->getOrCreateDraft($this->admin);
        $user = $role === null ? null : $this->makeUser($role);
        if ($user) {
            $this->actingAs($user);
        } else {
            Auth::logout();
        }

        try {
            if ($entry === 'preview') {
                app(MarketingGuideController::class)->preview();
            } elseif ($entry === 'livewire') {
                $editor = new MarketingGuideContentEditor;
                $editor->boot($this->service);
                $editor->publishDraft();
            } else {
                $this->service->publishDraft($user);
            }
            $this->fail('Unauthorized action succeeded.');
        } catch (HttpException $e) {
            $this->assertSame(403, $e->getStatusCode());
        }

        $this->assertSame($this->published->id, $this->service->currentVersion()->id);
        $this->assertSame('draft', $draft->fresh()->status);
    }

    public static function unauthorizedActors(): array
    {
        return [
            'guest preview' => [null, 'preview'],
            'user preview' => ['user', 'preview'],
            'guest publish action' => [null, 'livewire'],
            'user publish action' => ['user', 'livewire'],
            'user publish service' => ['user', 'service'],
        ];
    }

    public function test_preview_renders_saved_draft_using_dg3_without_access_side_effects(): void
    {
        $draft = $this->changedDraft();
        $access = app(MarketingGuideAccessService::class)->create($this->admin, now()->addDay(), 'Partner');
        $before = $access['access']->fresh()->getAttributes();

        $preview = $this->actingAs($this->admin)->get(route('admin.marketing-guide.content.preview', [
            'version_id' => $this->published->id,
            'token' => 'ignored',
        ]));
        $preview->assertOk()->assertViewIs('marketing-guide.dynamic')
            ->assertSee('DG4 draft only')->assertDontSee('DG4 published only');
        $this->assertSame($draft->id, $preview->viewData('sections')->first()->version_id);
        $this->assertPrivateResponse($preview);
        $this->assertSame(1, MarketingGuideAccess::count());
        $this->assertSame($before, $access['access']->fresh()->getAttributes());
        $this->assertSame($this->published->id, $this->service->currentVersion()->id);

        $this->get(route('marketing-guide.show', $access['token']))
            ->assertOk()->assertViewIs('marketing-guide.dynamic')->assertDontSee('DG4 draft only')->assertSee('DG4 published only');
    }

    public function test_preview_without_draft_does_not_clone_or_fall_back_to_published(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.marketing-guide.content.preview'));
        $response->assertNotFound()->assertSee('Tidak ada draft');
        $this->assertPrivateResponse($response);
        $this->assertNull($this->service->findDraft());
        $this->assertSame(0, MarketingGuideAccess::count());
    }

    public function test_publish_valid_draft_archives_old_version_and_updates_public_and_audit_fields(): void
    {
        $this->freezeSecond();
        $draft = $this->changedDraft();
        $access = app(MarketingGuideAccessService::class)->create($this->admin, now()->addDay());
        $locks = [];
        DB::listen(function ($query) use (&$locks) {
            if (str_contains(strtolower($query->sql), 'for update')) {
                $locks[] = $query->connection->transactionLevel();
            }
        });

        Livewire::actingAs($this->admin)->test(MarketingGuideContentEditor::class)
            ->assertSee('Preview Draft')->assertSee('wire:confirm', false)
            ->call('publishDraft')->assertSet('errorMessage', '')
            ->assertSee('berhasil dipublish')->assertSee('Siapkan Draft');

        $this->assertNotEmpty($locks);
        $this->assertGreaterThan(0, min($locks));
        $this->assertSame('archived', $this->published->fresh()->status);
        $this->assertSame('published', $draft->fresh()->status);
        $this->assertTrue($draft->fresh()->published_at->equalTo(now()));
        $this->assertSame($this->admin->uid, $draft->fresh()->published_by_uid);
        $this->assertSame($draft->id, $this->service->currentVersion()->id);
        $this->assertSame(1, MarketingGuideVersion::published()->count());
        $this->assertNull($this->service->findDraft());
        $this->get(route('marketing-guide.show', $access['token']))->assertOk()->assertSee('DG4 draft only');

        Livewire::actingAs($this->admin)->test(MarketingGuideContentEditor::class)->call('openEditor');
        $next = $this->service->findDraft();
        $this->assertNotSame($draft->id, $next->id);
        $this->assertSame($draft->number + 1, $next->number);
        $this->assertSame($draft->sections->count(), $next->sections->count());
        $this->assertSame('DG4 draft only', $next->sections()->first()->blocks()->where('type', 'text')->first()->data['title']);
        $this->assertNull($next->published_at);
        $this->assertNull($next->published_by_uid);
        $this->assertSame($draft->id, $this->service->currentVersion()->id);
    }

    #[DataProvider('invalidDrafts')]
    public function test_invalid_draft_is_preserved_and_old_published_remains_public(string $case, mixed $value): void
    {
        $draft = $this->changedDraft();
        $section = $draft->sections()->first();
        if ($case === 'no sections') {
            $draft->sections()->update(['is_active' => false]);
        } elseif ($case === 'slug') {
            $section->update(['slug' => $value]);
        } elseif ($case === 'duplicate') {
            $draft->sections()->where('id', '!=', $section->id)->first()->update(['slug' => $section->slug]);
        } else {
            $block = $section->blocks()->first();
            $block->update($case === 'type' ? ['type' => $value] : ['type' => $value[0], 'data' => $value[1]]);
            if ($case === 'hidden active block') {
                $section->update(['is_active' => false]);
            }
        }
        $before = $draft->fresh()->load('sections.blocks')->toArray();
        $old = $this->published->getAttributes();
        $access = app(MarketingGuideAccessService::class)->create($this->admin, now()->addDay());

        Livewire::actingAs($this->admin)->test(MarketingGuideContentEditor::class)
            ->call('publishDraft')->assertSet('successMessage', '')
            ->assertSet('errorMessage', fn ($message) => $message !== '');

        $this->assertSame($before, $draft->fresh()->load('sections.blocks')->toArray());
        $this->assertSame($old, $this->published->fresh()->getAttributes());
        $this->assertSame($this->published->id, $this->service->currentVersion()->id);
        $this->assertSame('draft', $draft->fresh()->status);
        $this->get(route('marketing-guide.show', $access['token']))->assertOk()->assertDontSee('DG4 draft only');
    }

    public static function invalidDrafts(): array
    {
        return [
            'no active section' => ['no sections', null],
            'null slug' => ['slug', null],
            'empty slug' => ['slug', ''],
            'invalid slug' => ['slug', 'bad slug'],
            'selector injection slug' => ['slug', 'x"]'],
            'duplicate active slug' => ['duplicate', null],
            'unknown block' => ['type', 'script'],
            'scalar payload' => ['payload', ['text', 'invalid']],
            'missing schema field' => ['payload', ['workflow', []]],
            'invalid schema list' => ['payload', ['workflow', ['steps' => 'bad']]],
            'invalid nested scalar' => ['payload', ['workflow', ['steps' => [['title' => []]]]]],
            'invalid columns' => ['payload', ['cards', ['columns' => '2unsafe', 'cards' => []]]],
            'unknown icon' => ['payload', ['placeholder', ['icon' => 'untrusted-icon']]],
            'nested icon' => ['payload', ['workflow', ['steps' => [['icon' => 'untrusted-icon']]]]],
            'unsafe href' => ['payload', ['cta', ['cta' => ['href' => 'javascript:alert(1)']]]],
            'unsafe nested href' => ['payload', ['text', ['extra' => ['href' => 'data:text/html,bad']]]],
            'array href' => ['payload', ['text', ['extra' => ['href' => ['https://example.test']]]]],
            'array icon' => ['payload', ['text', ['extra' => ['icon' => ['check']]]]],
            'active block in inactive section' => ['hidden active block', ['workflow', []]],
        ];
    }

    public function test_invalid_preview_returns_safe_validation_response(): void
    {
        $draft = $this->changedDraft();
        $draft->sections()->first()->blocks()->first()->update([
            'type' => 'cta', 'data' => ['cta' => ['href' => 'javascript:<script>alert(1)</script>']],
        ]);
        $response = $this->actingAs($this->admin)->get(route('admin.marketing-guide.content.preview'));
        $response->assertStatus(422)->assertDontSee('<script>', false);
        $this->assertPrivateResponse($response);
        $this->assertSame('draft', $draft->fresh()->status);
    }

    public function test_failure_after_archival_rolls_back_every_write_and_can_be_retried(): void
    {
        $draft = $this->changedDraft();
        $before = $draft->fresh()->getAttributes();
        $old = $this->published->getAttributes();
        $dispatcher = MarketingGuideVersion::getEventDispatcher();
        MarketingGuideVersion::setEventDispatcher(clone $dispatcher);
        MarketingGuideVersion::updating(function ($version) use ($draft) {
            if ($version->id === $draft->id && $version->status === 'published') {
                $this->assertSame('archived', $this->published->fresh()->status);
                throw new RuntimeException('Simulated publish write failure');
            }
        });

        try {
            Livewire::actingAs($this->admin)->test(MarketingGuideContentEditor::class)
                ->call('publishDraft')->assertSet('successMessage', '')
                ->assertSee('Publish gagal')->assertDontSee('Simulated publish write failure');
        } finally {
            MarketingGuideVersion::setEventDispatcher($dispatcher);
        }

        $this->assertSame($old, $this->published->fresh()->getAttributes());
        $this->assertSame($before, $draft->fresh()->getAttributes());
        $this->assertSame($this->published->id, $this->service->currentVersion()->id);
        $access = app(MarketingGuideAccessService::class)->create($this->admin, now()->addDay());
        $this->get(route('marketing-guide.show', $access['token']))->assertOk()->assertDontSee('DG4 draft only');
        $this->assertSame($draft->id, $this->service->publishDraft($this->admin)->id);
    }

    public function test_publish_without_draft_is_rejected_without_touching_published(): void
    {
        $before = $this->published->getAttributes();
        Livewire::actingAs($this->admin)->test(MarketingGuideContentEditor::class)
            ->call('publishDraft')->assertSet('errorMessage', 'Tidak ada draft untuk dipublish.');
        $this->assertSame($before, $this->published->fresh()->getAttributes());
        $this->assertNull($this->service->findDraft());
    }

    #[DataProvider('staleMutations')]
    public function test_stale_editor_mutations_cannot_edit_a_version_after_publish(string $action): void
    {
        $draft = $this->changedDraft();
        $section = $draft->sections()->with('version', 'blocks.section.version')->first();
        $block = $section->blocks->first();
        $this->service->publishDraft($this->admin);
        $next = $this->service->getOrCreateDraft($this->admin);
        $before = $draft->fresh()->load('sections.blocks')->toArray();

        try {
            match ($action) {
                'section' => $this->service->updateSection($section, ['title' => 'Unsafe edit'], $this->admin),
                'sections order' => $this->service->reorderSections($draft, [$section->id], $this->admin),
                'block' => $this->service->updateBlock($block, ['intro' => 'Unsafe edit'], true, $this->admin),
                'add' => $this->service->addBlock($section, 'text', ['intro' => 'Unsafe edit'], true, $this->admin),
                'remove' => $this->service->removeBlock($block, $this->admin),
                'blocks order' => $this->service->reorderBlocks($section, [$block->id], $this->admin),
            };
            $this->fail('Stale editor mutated published content.');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('bukan draft', $e->getMessage());
        }

        $this->assertSame($before, $draft->fresh()->load('sections.blocks')->toArray());
        $this->assertSame($next->id, $this->service->findDraft()->id);
    }

    public static function staleMutations(): array
    {
        return array_map(fn ($action) => [$action], ['section', 'sections order', 'block', 'add', 'remove', 'blocks order']);
    }

    public function test_repeated_publish_and_clone_keeps_version_keys_bounded(): void
    {
        for ($i = 0; $i < 8; $i++) {
            $draft = $this->service->getOrCreateDraft($this->admin);
            $this->assertLessThanOrEqual(64, strlen($draft->key));
            $this->service->publishDraft($this->admin);
        }
        $this->assertSame(1, MarketingGuideVersion::published()->count());
        $this->assertSame($draft->id, $this->service->currentVersion()->id);
    }

    private function changedDraft(): MarketingGuideVersion
    {
        $publishedBlock = $this->published->sections()->first()->blocks()->where('type', 'text')->first();
        $publishedBlock->update(['data' => array_merge($publishedBlock->data, [
            'intro' => 'DG4 published only', 'title' => 'DG4 published only',
        ])]);
        $draft = $this->service->getOrCreateDraft($this->admin);
        $block = $draft->sections()->first()->blocks()->where('type', 'text')->first();
        $this->service->updateBlock($block, array_merge($block->data, [
            'intro' => 'DG4 draft only', 'title' => 'DG4 draft only',
        ]), true, $this->admin);

        return $draft;
    }

    private function assertPrivateResponse($response): void
    {
        $response->assertHeader('X-Robots-Tag', 'noindex, nofollow, noarchive')->assertHeader('Pragma', 'no-cache');
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        $this->assertStringContainsString('private', $response->headers->get('Cache-Control'));
    }

    private function makeUser(string $role): User
    {
        return User::factory()->create([
            'uid' => (string) Str::uuid(),
            'role' => $role,
            'email' => Str::random(16).'@example.test',
            'password' => 'Password123',
        ]);
    }
}
