<?php

namespace App\Services\MarketingGuide;

use App\Models\MarketingGuideBlock;
use App\Models\MarketingGuideSection;
use App\Models\MarketingGuideVersion;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class MarketingGuideContentService
{
    /**
     * Whitelisted block types. Mirrors MarketingGuideBlock::TYPES but kept
     * here so callers do not need the model constant.
     *
     * @var list<string>
     */
    public const BLOCK_TYPES = [
        'text',
        'workflow',
        'flow',
        'cards',
        'placeholder',
        'tickets',
        'stats',
        'qr',
        'faq',
        'cta',
    ];

    /**
     * Whitelisted icon names (FontAwesome-style). Sourced from
     * resources/views/marketing-guide/index.blade.php so the editor
     * never introduces a glyph the public renderer does not know.
     *
     * @var list<string>
     */
    public const ICON_WHITELIST = [
        'align-left',
        'arrow-right',
        'calculator',
        'camera',
        'chart-bar',
        'chart-line',
        'chart-pie',
        'check',
        'check-circle',
        'clock',
        'cog',
        'comment',
        'credit-card',
        'download',
        'edit',
        'envelope',
        'exchange-alt',
        'eye',
        'file-contract',
        'file-pdf',
        'hand-holding-usd',
        'handshake',
        'images',
        'info-circle',
        'list',
        'lock',
        'lock-open',
        'map-marker-alt',
        'mobile-alt',
        'money-bill',
        'paper-plane',
        'phone',
        'qrcode',
        'redo',
        'rocket',
        'search',
        'shield-alt',
        'sign-in-alt',
        'ticket-alt',
        'university',
        'user',
        'users',
        'wallet',
        'wifi',
    ];

    private const DRAFT_VERSION_KEY_PREFIX = 'guide.draft.';

    /**
     * Resolve the currently published version. Returns null when no
     * published version exists (drafts are intentionally not exposed).
     */
    public function currentVersion(): ?MarketingGuideVersion
    {
        return MarketingGuideVersion::query()
            ->published()
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->first();
    }

    /**
     * Get a version by its stable key (idempotent lookup).
     */
    public function findVersionByKey(string $key): ?MarketingGuideVersion
    {
        return MarketingGuideVersion::query()->where('key', $key)->first();
    }

    /**
     * List active sections of a version ordered for rendering.
     *
     * @return Collection<int, MarketingGuideSection>
     */
    public function activeSectionsForVersion(MarketingGuideVersion $version): Collection
    {
        return $version->sections()
            ->where('is_active', true)
            ->orderBy('position')
            ->orderBy('id')
            ->get();
    }

    /**
     * Resolve a section by its stable key within a version.
     */
    public function findSectionByKey(MarketingGuideVersion $version, string $key): ?MarketingGuideSection
    {
        return $version->sections()->where('key', $key)->first();
    }

    /**
     * Get active blocks for a section, ordered for rendering.
     *
     * @return Collection<int, MarketingGuideBlock>
     */
    public function activeBlocksForSection(MarketingGuideSection $section): Collection
    {
        return $section->activeBlocks()->get();
    }

    /**
     * Validate that the given block type is whitelisted.
     */
    public function assertValidBlockType(string $type): void
    {
        if (! in_array($type, self::BLOCK_TYPES, true)) {
            throw new InvalidArgumentException(sprintf(
                'Tipe block "%s" tidak diizinkan. Tipe yang valid: %s.',
                $type,
                implode(', ', self::BLOCK_TYPES)
            ));
        }
    }

    /**
     * Validate that the given icon (if any) is whitelisted.
     */
    public function assertValidIcon(?string $icon): void
    {
        if ($icon === null || $icon === '') {
            return;
        }

        if (! in_array($icon, self::ICON_WHITELIST, true)) {
            throw new InvalidArgumentException(sprintf(
                'Icon "%s" tidak diizinkan.',
                $icon
            ));
        }
    }

    /**
     * Validate that the given href (if any) uses a safe scheme.
     * Allowed: relative anchor (#...), mailto:, http(s)://.
     * Anything else (javascript:, data:, vbscript:, ftp:, file:, ...)
     * is rejected so the renderer never executes client-side code.
     */
    public function assertValidHref(?string $href): void
    {
        if ($href === null || $href === '') {
            return;
        }

        $normalized = trim($href);

        if ($normalized === '') {
            return;
        }

        if (str_starts_with($normalized, '#')) {
            return;
        }

        if (preg_match("/^(https?:\/\/|mailto:)/i", $normalized) === 1) {
            return;
        }

        throw new InvalidArgumentException(sprintf(
            'Link "%s" tidak diizinkan. Hanya anchor (#...), mailto:, atau http(s):// yang diizinkan.',
            $normalized
        ));
    }

    /**
     * Get the working draft version. Reuses the existing draft if one
     * exists; otherwise clones the latest published version into a new
     * draft row. The clone is fully transaction-safe and produces a
     * complete copy of every section and block.
     *
     * The create is concurrency-safe: when two requests race to create
     * the very first draft, exactly one wins the insert and the loser
     * retries in a fresh transaction, picks up the winner's draft and
     * reuses it. A unique-key error is therefore never surfaced to the
     * caller.
     */
    public function getOrCreateDraft(User $editor): MarketingGuideVersion
    {
        $attempts = 0;

        while (true) {
            try {
                return DB::transaction(function () use ($editor) {
                    // Serialize draft creation with publishing before reading either status.
                    MarketingGuideVersion::query()->orderBy('id')->lockForUpdate()->get();
                    $existingDraft = $this->findDraft();
                    if ($existingDraft !== null) {
                        return $existingDraft;
                    }

                    $published = $this->currentVersion();
                    if ($published === null) {
                        throw new InvalidArgumentException(
                            'Tidak ada versi published untuk di-clone. Jalankan seeder terlebih dahulu.'
                        );
                    }

                    return $this->cloneVersion($published, $editor);
                });
            } catch (QueryException $e) {
                $attempts++;

                // Only a unique-key race is retried. Every attempt runs in
                // its own fresh transaction, so the retry sees the winner
                // that committed in between and reuses it instead of
                // failing with a "duplicate key" error.
                if (! $this->isUniqueViolation($e) || $attempts >= 3) {
                    throw $e;
                }
            }
        }
    }

    /**
     * Detect the unique-constraint violation raised when two concurrent
     * requests try to create the first draft with the same stable key.
     */
    private function isUniqueViolation(QueryException $e): bool
    {
        $message = strtolower($e->getMessage());

        if (
            str_contains($message, 'duplicate entry')
            || str_contains($message, 'unique constraint failed')
        ) {
            return true;
        }

        $info = $e->errorInfo;
        if (is_array($info)) {
            $sqlState = (string) ($info[0] ?? '');
            $driverCode = (int) ($info[1] ?? 0);

            if ($sqlState === '23000' || $driverCode === 1062 || $driverCode === 19) {
                return true;
            }
        }

        return false;
    }

    /**
     * Find the most recent active draft version (status=draft, not archived).
     */
    public function findDraft(): ?MarketingGuideVersion
    {
        return MarketingGuideVersion::query()
            ->where('status', MarketingGuideVersion::STATUS_DRAFT)
            ->orderByDesc('id')
            ->first();
    }

    public function publishDraft(User $admin): MarketingGuideVersion
    {
        $admin = $admin->fresh();
        abort_unless($admin !== null && $admin->uid && strtolower((string) $admin->role) === 'admin', 403);

        return DB::transaction(function () use ($admin) {
            // Use the same lock order as draft creation. Never accept a client version ID.
            $versions = MarketingGuideVersion::query()->orderBy('id')->lockForUpdate()->get();
            $draft = $versions->where('status', MarketingGuideVersion::STATUS_DRAFT)->last();
            if ($draft === null) {
                throw new InvalidArgumentException('Tidak ada draft untuk dipublish.');
            }

            $this->validatedDraftSections($draft);

            foreach ($versions->where('status', MarketingGuideVersion::STATUS_PUBLISHED) as $published) {
                $published->update(['status' => MarketingGuideVersion::STATUS_ARCHIVED]);
            }

            $draft->update([
                'status' => MarketingGuideVersion::STATUS_PUBLISHED,
                'published_at' => now(),
                'published_by_uid' => $admin->uid,
            ]);

            return $draft;
        }, 3);
    }

    /** Validate the complete active content before exposing it through preview or publish. */
    public function validatedDraftSections(MarketingGuideVersion $draft): Collection
    {
        $allSections = $draft->sections()->orderBy('position')->orderBy('id')->with('activeBlocks')->get();
        $sections = $allSections->where('is_active', true)->values();
        if ($sections->isEmpty()) {
            throw new InvalidArgumentException('Draft harus memiliki setidaknya satu section aktif.');
        }

        $slugs = [];
        foreach ($sections as $section) {
            $slug = $section->slug;
            if (! is_string($slug) || strlen($slug) > 96 || preg_match('/^[a-z][a-z0-9]*(?:-[a-z0-9]+)*$/D', $slug) !== 1) {
                throw new InvalidArgumentException("Slug section #{$section->id} tidak valid.");
            }
            if (isset($slugs[$slug])) {
                throw new InvalidArgumentException("Slug section \"{$slug}\" duplicate.");
            }
            $slugs[$slug] = true;
        }

        // Also validate active blocks in inactive sections so they cannot hide invalid content.
        foreach ($allSections as $section) {
            foreach ($section->activeBlocks as $block) {
                $this->assertValidBlockType($block->type);
                $this->assertValidPayload($block->type, $block->data);
            }
        }

        return $sections;
    }

    /**
     * Deep-clone a version into a new draft row. Returns the new draft
     * with sections and blocks eagerly loaded. Source version is never
     * mutated.
     */
    private function cloneVersion(MarketingGuideVersion $source, User $editor): MarketingGuideVersion
    {
        $draft = MarketingGuideVersion::query()->create([
            'key' => self::DRAFT_VERSION_KEY_PREFIX.$source->id,
            'number' => $source->number + 1,
            'title' => $source->title,
            'status' => MarketingGuideVersion::STATUS_DRAFT,
            'created_by_uid' => User::query()->where('uid', $editor->uid)->exists() ? $editor->uid : null,
            'published_at' => null,
            'published_by_uid' => null,
        ]);

        $source->sections()
            ->orderBy('position')
            ->orderBy('id')
            ->get()
            ->each(function (MarketingGuideSection $section) use ($draft) {
                $newSection = MarketingGuideSection::query()->create([
                    'version_id' => $draft->id,
                    'key' => $section->key,
                    'title' => $section->title,
                    'slug' => $section->slug,
                    'nav_group' => $section->nav_group,
                    'position' => $section->position,
                    'is_active' => $section->is_active,
                ]);

                $section->blocks()
                    ->orderBy('position')
                    ->orderBy('id')
                    ->get()
                    ->each(function (MarketingGuideBlock $block) use ($newSection) {
                        MarketingGuideBlock::query()->create([
                            'section_id' => $newSection->id,
                            'type' => $block->type,
                            'position' => $block->position,
                            'data' => $block->data ?? [],
                            'is_active' => $block->is_active,
                        ]);
                    });
            });

        return $draft->load('sections.blocks');
    }

    /**
     * Persist section edits to a draft. Only draft versions can be mutated.
     *
     * @param  array{title?: string, nav_group?: ?string, position?: int, is_active?: bool}  $attributes
     */
    public function updateSection(
        MarketingGuideSection $section,
        array $attributes,
        User $editor,
    ): MarketingGuideSection {
        return DB::transaction(function () use ($section, $attributes, $editor) {
            $section = $section->fresh();
            $this->assertDraftVersion($section->version, $editor);
            $section->refresh();

            if (array_key_exists('title', $attributes)) {
                $title = $this->scalarString($attributes['title']);
                if ($title === '' || strlen($title) > 200) {
                    throw new InvalidArgumentException('Title section tidak valid (wajib diisi, maks 200 karakter).');
                }
                $section->title = $title;
            }

            if (array_key_exists('nav_group', $attributes)) {
                $nav = $attributes['nav_group'];
                if ($nav !== null && $nav !== '') {
                    $nav = $this->scalarString($nav);
                    if (strlen($nav) > 64) {
                        throw new InvalidArgumentException('Nav group terlalu panjang (maks 64 karakter).');
                    }
                } else {
                    $nav = null;
                }
                $section->nav_group = $nav === '' ? null : $nav;
            }

            if (array_key_exists('position', $attributes)) {
                $section->position = max(0, (int) $attributes['position']);
            }

            if (array_key_exists('is_active', $attributes)) {
                $section->is_active = (bool) $attributes['is_active'];
            }

            $section->save();

            return $section->fresh();
        });
    }

    /**
     * Reorder the sections of a draft. Accepts the new ordered list of
     * section ids. Sections omitted from the list are pushed to the end
     * in their existing order.
     *
     * @param  list<int>  $orderedIds
     */
    public function reorderSections(
        MarketingGuideVersion $draft,
        array $orderedIds,
        User $editor,
    ): void {
        DB::transaction(function () use ($draft, $orderedIds, $editor) {
            $this->assertDraftVersion($draft, $editor);

            $known = $draft->sections()->pluck('id')->all();
            $knownMap = array_flip($known);

            foreach ($orderedIds as $id) {
                $id = (int) $id;
                if (! isset($knownMap[$id])) {
                    throw new InvalidArgumentException("Section id {$id} bukan bagian dari draft.");
                }
            }

            $this->applyOrdering(
                $draft->sections(),
                array_merge($orderedIds, array_values(array_diff($known, $orderedIds)))
            );
        });
    }

    /**
     * Apply a position sequence to a relationship using a two-phase
     * write to dodge the (parent, position) unique constraint. The
     * parent scope is passed in by the caller so this stays generic.
     *
     * The constraint column is unsigned, so we cannot use negatives.
     * Instead we set every row's position to its final value + 1000
     * (offset beyond the maximum realistic count of blocks per parent)
     * in phase one, then write the final value in phase two. This keeps
     * every intermediate state conflict-free.
     *
     * @param  list<int>  $orderedIds
     */
    private function applyOrdering(HasMany $relation, array $orderedIds): void
    {
        $parent = $relation->getParent();
        $foreignKey = $relation->getQualifiedForeignKeyName();
        $modelClass = $relation->getModel()::class;
        $offset = 1000;

        $finalPositions = [];
        foreach ($orderedIds as $i => $id) {
            $finalPositions[(int) $id] = $i + 1;
        }

        // Phase 1: shift every targeted row into a non-conflicting slot
        // beyond the highest expected position.
        foreach ($finalPositions as $id => $pos) {
            $modelClass::query()
                ->where('id', $id)
                ->where($foreignKey, $parent->getKey())
                ->update(['position' => $pos + $offset]);
        }

        // Phase 2: write final positive positions in order.
        foreach ($finalPositions as $id => $pos) {
            $modelClass::query()
                ->where('id', $id)
                ->where($foreignKey, $parent->getKey())
                ->update(['position' => $pos]);
        }
    }

    /**
     * Update a single block on a draft. Block type is treated as a
     * structural anchor and cannot change through this method. The
     * inner data array and the active flag can be edited.
     *
     * @param  array<string, mixed>  $data
     */
    public function updateBlock(
        MarketingGuideBlock $block,
        array $data,
        bool $isActive,
        User $editor,
    ): MarketingGuideBlock {
        return DB::transaction(function () use ($block, $data, $isActive, $editor) {
            $block = $block->fresh();
            $this->assertDraftVersion($block->section->version, $editor);
            $block->refresh();
            $this->assertValidPayload($block->type, $data);

            $block->data = $data;
            $block->is_active = $isActive;
            $block->save();

            return $block->fresh('section');
        });
    }

    /**
     * Insert a new block at the end of a draft section.
     *
     * @param  array<string, mixed>  $data
     */
    public function addBlock(
        MarketingGuideSection $section,
        string $type,
        array $data,
        bool $isActive,
        User $editor,
    ): MarketingGuideBlock {
        return DB::transaction(function () use ($section, $type, $data, $isActive, $editor) {
            $section = $section->fresh();
            $this->assertDraftVersion($section->version, $editor);
            $section->refresh();
            $this->assertValidBlockType($type);
            $this->assertValidPayload($type, $data);

            $nextPosition = ((int) $section->blocks()->max('position')) + 1;

            return MarketingGuideBlock::query()->create([
                'section_id' => $section->id,
                'type' => $type,
                'position' => max(1, $nextPosition),
                'data' => $data,
                'is_active' => $isActive,
            ])->fresh('section');
        });
    }

    /**
     * Soft-delete a block on a draft. The row is never removed
     * physically; is_active is set to false so the public renderer
     * ignores it. The position is left in place so the slot can be
     * reactivated or new blocks can reuse the ordering gap.
     */
    public function removeBlock(MarketingGuideBlock $block, User $editor): void
    {
        DB::transaction(function () use ($block, $editor) {
            $block = $block->fresh();
            $this->assertDraftVersion($block->section->version, $editor);
            $block->refresh();
            $block->is_active = false;
            $block->save();
        });
    }

    /**
     * Reorder the blocks of a draft section.
     *
     * @param  list<int>  $orderedIds
     */
    public function reorderBlocks(
        MarketingGuideSection $section,
        array $orderedIds,
        User $editor,
    ): void {
        DB::transaction(function () use ($section, $orderedIds, $editor) {
            $section = $section->fresh();
            $this->assertDraftVersion($section->version, $editor);
            $section->refresh();

            $known = $section->blocks()->pluck('id')->all();
            $knownMap = array_flip($known);

            foreach ($orderedIds as $id) {
                $id = (int) $id;
                if (! isset($knownMap[$id])) {
                    throw new InvalidArgumentException("Block id {$id} bukan bagian dari section ini.");
                }
            }

            $this->applyOrdering(
                $section->blocks(),
                array_merge($orderedIds, array_values(array_diff($known, $orderedIds)))
            );
        });
    }

    /**
     * Guard: refuse to mutate anything that is not an active draft.
     */
    private function assertDraftVersion(MarketingGuideVersion $version, User $editor): void
    {
        // All callers hold this lock until their mutation commits, including stale editor requests.
        $version = MarketingGuideVersion::query()->lockForUpdate()->findOrFail($version->id);
        if ($version->status !== MarketingGuideVersion::STATUS_DRAFT) {
            throw new InvalidArgumentException(
                'Versi ini bukan draft. Clone published ke draft sebelum melakukan perubahan.'
            );
        }
        if ($editor->uid === null || $editor->uid === '') {
            throw new InvalidArgumentException('Editor tidak memiliki uid.');
        }
    }

    /**
     * Recursively sanitize a block payload, rejecting unknown icons,
     * unsafe hrefs, and any non-array / non-scalar field. Unknown keys
     * are kept (for forward compatibility) but the values are coerced
     * into strings where validation is required.
     */
    private function assertValidPayload(string $type, mixed $data): void
    {
        if (! is_array($data)) {
            throw new InvalidArgumentException('Data block harus berupa array.');
        }

        $this->validateTypeStructure($type, $data);
        $this->walkPayload($data);
    }

    /**
     * Structural schema used to make sure a payload actually matches its
     * block type (not only icon/href safety). Only known keys are checked
     * so forward-compatible extra keys are never a reason to reject a
     * block that the structured editor already validated.
     */
    private function typeSchema(string $type): array
    {
        $schemas = [
            'text' => [
                'required' => [],
                'strings' => ['intro', 'intro_spacing', 'badge', 'title', 'subtitle'],
                'bools' => ['show_recipient', 'show_expiry'],
                'objects' => ['cta' => ['label', 'href', 'icon']],
                'lists' => [],
                'ints' => [],
            ],
            'workflow' => [
                'required' => ['steps'],
                'strings' => [],
                'bools' => [],
                'objects' => [],
                'lists' => ['steps' => ['icon', 'title', 'description']],
                'ints' => [],
            ],
            'flow' => [
                'required' => ['boxes'],
                'strings' => [],
                'bools' => [],
                'objects' => [],
                'lists' => ['boxes' => ['icon', 'label']],
                'ints' => [],
            ],
            'cards' => [
                'required' => ['columns', 'cards'],
                'strings' => [],
                'bools' => [],
                'objects' => [],
                'lists' => ['cards' => ['icon', 'title', 'body']],
                'ints' => ['columns' => [2, 3]],
            ],
            'placeholder' => [
                'required' => [],
                'strings' => ['icon', 'title', 'caption'],
                'bools' => [],
                'objects' => [],
                'lists' => [],
                'ints' => [],
            ],
            'tickets' => [
                'required' => ['tickets'],
                'strings' => ['tip'],
                'bools' => [],
                'objects' => [],
                'lists' => ['tickets' => ['type', 'price', 'quantity', 'description']],
                'ints' => [],
            ],
            'stats' => [
                'required' => ['stats'],
                'strings' => [],
                'bools' => [],
                'objects' => [],
                'lists' => ['stats' => ['value', 'label']],
                'ints' => [],
            ],
            'qr' => [
                'required' => [],
                'strings' => [
                    'event_name',
                    'event_date',
                    'icon',
                    'ticket_holder',
                    'ticket_type',
                    'ticket_number',
                    'entry_window',
                    'footer',
                ],
                'bools' => [],
                'objects' => [],
                'lists' => [],
                'ints' => [],
            ],
            'faq' => [
                'required' => ['items'],
                'strings' => [],
                'bools' => [],
                'objects' => [],
                'lists' => ['items' => ['question', 'answer']],
                'ints' => [],
            ],
            'cta' => [
                'required' => ['cta'],
                'strings' => ['title', 'subtitle'],
                'bools' => [],
                'objects' => ['cta' => ['label', 'href', 'icon', 'variant']],
                'lists' => [],
                'ints' => [],
            ],
        ];

        return $schemas[$type] ?? [
            'required' => [],
            'strings' => [],
            'bools' => [],
            'objects' => [],
            'lists' => [],
            'ints' => [],
        ];
    }

    /**
     * Enforce the minimal structure expected by the block type. Container
     * keys (steps/boxes/cards/tickets/stats/items) must be real lists of
     * objects, nested objects (cta) must be objects, and every known
     * scalar field must stay a scalar. This is what stops a payload meant
     * for another type from silently landing on a block.
     */
    private function validateTypeStructure(string $type, array $data): void
    {
        $schema = $this->typeSchema($type);
        $label = sprintf('Payload block "%s"', $type);

        foreach ($schema['required'] as $key) {
            if (! array_key_exists($key, $data)) {
                throw new InvalidArgumentException(
                    sprintf('%s wajib memiliki field "%s".', $label, $key)
                );
            }
        }

        foreach ($schema['strings'] as $key) {
            if (array_key_exists($key, $data) && ! $this->isStringable($data[$key])) {
                throw new InvalidArgumentException(
                    sprintf('%s: field "%s" harus berupa teks.', $label, $key)
                );
            }
        }

        foreach ($schema['bools'] as $key) {
            if (array_key_exists($key, $data) && ! is_bool($data[$key])) {
                throw new InvalidArgumentException(
                    sprintf('%s: field "%s" harus berupa boolean.', $label, $key)
                );
            }
        }

        foreach ($schema['ints'] as $key => $allowed) {
            if (array_key_exists($key, $data) && (
                (! is_int($data[$key]) && ! (is_string($data[$key]) && ctype_digit($data[$key])))
                || ! in_array((int) $data[$key], $allowed, true)
            )) {
                $allowedList = implode(' atau ', $allowed);
                throw new InvalidArgumentException(
                    sprintf('%s: field "%s" harus bernilai %s.', $label, $key, $allowedList)
                );
            }
        }

        foreach ($schema['objects'] as $key => $childKeys) {
            if (! array_key_exists($key, $data)) {
                continue;
            }
            if (! is_array($data[$key]) || array_is_list($data[$key])) {
                throw new InvalidArgumentException(
                    sprintf('%s: field "%s" harus berupa objek.', $label, $key)
                );
            }
            foreach ($childKeys as $child) {
                if (array_key_exists($child, $data[$key]) && ! $this->isStringable($data[$key][$child])) {
                    throw new InvalidArgumentException(
                        sprintf('%s: field "%s.%s" harus berupa teks.', $label, $key, $child)
                    );
                }
            }
        }

        foreach ($schema['lists'] as $key => $itemScalarKeys) {
            if (! array_key_exists($key, $data)) {
                continue;
            }
            if (! is_array($data[$key]) || ! array_is_list($data[$key])) {
                throw new InvalidArgumentException(
                    sprintf('%s: field "%s" harus berupa daftar.', $label, $key)
                );
            }
            foreach ($data[$key] as $index => $item) {
                if (! is_array($item) || array_is_list($item)) {
                    throw new InvalidArgumentException(
                        sprintf('%s: item ke-%d pada "%s" harus berupa objek.', $label, $index + 1, $key)
                    );
                }
                foreach ($itemScalarKeys as $child) {
                    if (array_key_exists($child, $item) && ! $this->isStringable($item[$child])) {
                        throw new InvalidArgumentException(
                            sprintf('%s: field "%s[%d].%s" harus berupa teks.', $label, $key, $index + 1, $child)
                        );
                    }
                }
            }
        }
    }

    private function isStringable(mixed $value): bool
    {
        return $value === null || is_string($value) || is_int($value) || is_float($value);
    }

    /**
     * Walk the payload tree, validating every icon and href field.
     */
    private function walkPayload(mixed $node, string $path = 'data'): void
    {
        if (is_array($node)) {
            $isList = array_is_list($node);

            foreach ($node as $key => $child) {
                $childPath = $isList ? "{$path}[{$key}]" : "{$path}.{$key}";

                if (! $isList && in_array($key, ['icon', 'href'], true) && ! $this->isStringable($child)) {
                    throw new InvalidArgumentException("Field {$childPath} harus berupa teks.");
                }
                if (! $isList && $key === 'icon') {
                    $this->assertValidIcon(is_scalar($child) ? (string) $child : null);
                }
                if (! $isList && $key === 'href') {
                    $this->assertValidHref(is_scalar($child) ? (string) $child : null);
                }

                if (is_array($child)) {
                    $this->walkPayload($child, $childPath);
                } elseif (is_string($child)) {
                    continue;
                } elseif ($child === null || is_bool($child) || is_int($child) || is_float($child)) {
                    continue;
                } else {
                    throw new InvalidArgumentException("Field {$childPath} memiliki tipe nilai yang tidak didukung.");
                }
            }
        }
    }

    private function scalarString(mixed $value): string
    {
        if (! is_scalar($value)) {
            return '';
        }

        return trim((string) $value);
    }
}
