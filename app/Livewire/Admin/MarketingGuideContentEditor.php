<?php

namespace App\Livewire\Admin;

use App\Models\MarketingGuideBlock;
use App\Models\MarketingGuideSection;
use App\Models\User;
use App\Services\MarketingGuide\MarketingGuideContentService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('admin.layout', ['title' => 'Editor Marketing Guide'])]
#[Title('Editor Marketing Guide')]
class MarketingGuideContentEditor extends Component
{
    public ?int $editingSectionId = null;

    public ?int $editingBlockId = null;

    public ?int $addingBlockForSectionId = null;

    public string $sectionTitle = '';

    public ?string $sectionNavGroup = null;

    public bool $sectionIsActive = true;

    public int $sectionPosition = 1;

    public string $blockType = MarketingGuideBlock::TYPE_TEXT;

    public bool $blockIsActive = true;

    public string $blockDataRaw = '{}';

    public string $errorMessage = '';

    public string $successMessage = '';

    protected MarketingGuideContentService $service;

    public function boot(MarketingGuideContentService $service): void
    {
        $this->service = $service;
    }

    public function mount(): void
    {
        $this->ensureAdmin();
    }

    public function openEditor(): void
    {
        $this->ensureAdmin();
        // Reuse-or-clone happens lazily on first save action so the
        // page render stays cheap. We do warm the draft here so the
        // admin sees the actual state.
        try {
            $this->service->getOrCreateDraft($this->adminUser());
            $this->successMessage = 'Draft siap diedit.';
        } catch (\Throwable $e) {
            $this->errorMessage = $e->getMessage();
        }
    }

    public function openSectionEditor(int $sectionId): void
    {
        $this->ensureAdmin();
        $section = $this->findDraftSection($sectionId);
        if ($section === null) {
            return;
        }
        $this->editingSectionId = $section->id;
        $this->sectionTitle = (string) $section->title;
        $this->sectionNavGroup = $section->nav_group;
        $this->sectionIsActive = (bool) $section->is_active;
        $this->sectionPosition = (int) $section->position;
        $this->resetErrorBag();
        $this->dispatch('open-modal', name: 'mge-section-modal');
    }

    public function closeSectionEditor(): void
    {
        $this->ensureAdmin();
        $this->editingSectionId = null;
        $this->dispatch('close-modal', name: 'mge-section-modal');
    }

    public function saveSection(): void
    {
        $this->ensureAdmin();
        if ($this->editingSectionId === null) {
            return;
        }
        $section = $this->findDraftSection($this->editingSectionId);
        if ($section === null) {
            return;
        }
        try {
            $this->service->updateSection(
                $section,
                [
                    'title' => $this->sectionTitle,
                    'nav_group' => $this->sectionNavGroup,
                    'is_active' => $this->sectionIsActive,
                    'position' => $this->sectionPosition,
                ],
                $this->adminUser()
            );
            $this->successMessage = 'Section berhasil diperbarui.';
            $this->errorMessage = '';
            $this->editingSectionId = null;
            $this->dispatch('close-modal', name: 'mge-section-modal');
        } catch (\Throwable $e) {
            $this->errorMessage = $e->getMessage();
        }
    }

    public function toggleSectionActive(int $sectionId): void
    {
        $this->ensureAdmin();
        $section = $this->findDraftSection($sectionId);
        if ($section === null) {
            return;
        }
        try {
            $this->service->updateSection(
                $section,
                ['is_active' => ! $section->is_active],
                $this->adminUser()
            );
            $this->successMessage = $section->is_active
                ? 'Section dinonaktifkan.'
                : 'Section diaktifkan kembali.';
            $this->errorMessage = '';
        } catch (\Throwable $e) {
            $this->errorMessage = $e->getMessage();
        }
    }

    public function openBlockEditor(int $blockId): void
    {
        $this->ensureAdmin();
        $block = $this->findDraftBlock($blockId);
        if ($block === null) {
            return;
        }
        $this->editingBlockId = $block->id;
        $this->addingBlockForSectionId = null;
        $this->blockType = (string) $block->type;
        $this->blockIsActive = (bool) $block->is_active;
        $this->blockDataRaw = json_encode(
            $block->data ?? [],
            JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
        );
        if ($this->blockDataRaw === false) {
            $this->blockDataRaw = '{}';
        }
        $this->errorMessage = '';
        $this->successMessage = '';
        $this->resetErrorBag();
        $this->dispatch('open-modal', name: 'mge-block-modal');
    }

    public function closeBlockEditor(): void
    {
        $this->ensureAdmin();
        $this->editingBlockId = null;
        $this->addingBlockForSectionId = null;
        $this->dispatch('close-modal', name: 'mge-block-modal');
    }

    public function openAddBlock(int $sectionId): void
    {
        $this->ensureAdmin();
        $section = $this->findDraftSection($sectionId);
        if ($section === null) {
            return;
        }
        $this->editingBlockId = null;
        $this->addingBlockForSectionId = $section->id;
        $this->blockType = MarketingGuideBlock::TYPE_TEXT;
        $this->blockIsActive = true;
        $this->blockDataRaw = $this->defaultDataRawForType($this->blockType);
        $this->errorMessage = '';
        $this->successMessage = '';
        $this->resetErrorBag();
        $this->dispatch('open-modal', name: 'mge-block-modal');
    }

    public function switchBlockType(string $type): void
    {
        $this->ensureAdmin();

        // The block type is a structural anchor for an existing block and
        // can never change after creation. Only the "add block" flow is
        // allowed to pick a type; otherwise a payload meant for another
        // type could be saved onto an existing block.
        if ($this->editingBlockId !== null) {
            $this->blockType = (string) ($this->findDraftBlock($this->editingBlockId)?->type ?? $this->blockType);
            $this->errorMessage = 'Tipe block tidak dapat diubah. Hapus block lalu tambahkan block baru dengan tipe yang diinginkan.';

            return;
        }

        try {
            $this->service->assertValidBlockType($type);
        } catch (\Throwable $e) {
            $this->errorMessage = $e->getMessage();

            return;
        }
        $this->blockType = $type;
        $this->blockDataRaw = $this->defaultDataRawForType($type);
    }

    public function saveBlock(): void
    {
        $this->ensureAdmin();
        $decoded = $this->decodeData($this->blockDataRaw);
        if ($decoded === null) {
            $this->errorMessage = 'Data block tidak valid (JSON rusak).';

            return;
        }
        try {
            if ($this->editingBlockId !== null) {
                $block = $this->findDraftBlock($this->editingBlockId);
                if ($block === null) {
                    return;
                }
                // The stored block type is authoritative. Even if the
                // client tampered with $this->blockType, the payload is
                // validated against the real type of the row.
                $this->blockType = (string) $block->type;
                $this->service->updateBlock($block, $decoded, $this->blockIsActive, $this->adminUser());
                $this->successMessage = 'Block berhasil diperbarui.';
            } else {
                if ($this->addingBlockForSectionId === null) {
                    return;
                }
                $section = $this->findDraftSection($this->addingBlockForSectionId);
                if ($section === null) {
                    return;
                }
                $this->service->addBlock(
                    $section,
                    $this->blockType,
                    $decoded,
                    $this->blockIsActive,
                    $this->adminUser()
                );
                $this->successMessage = 'Block baru ditambahkan.';
            }
            $this->errorMessage = '';
            $this->editingBlockId = null;
            $this->addingBlockForSectionId = null;
            $this->dispatch('close-modal', name: 'mge-block-modal');
        } catch (\Throwable $e) {
            $this->errorMessage = $e->getMessage();
        }
    }

    public function removeBlock(int $blockId): void
    {
        $this->ensureAdmin();
        $block = $this->findDraftBlock($blockId);
        if ($block === null) {
            return;
        }
        try {
            $this->service->removeBlock($block, $this->adminUser());
            $this->successMessage = 'Block dinonaktifkan.';
            $this->errorMessage = '';
        } catch (\Throwable $e) {
            $this->errorMessage = $e->getMessage();
        }
    }

    public function moveBlockUp(int $blockId): void
    {
        $this->ensureAdmin();
        $block = $this->findDraftBlock($blockId);
        if ($block === null) {
            return;
        }
        $this->shiftBlock($block, -1);
    }

    public function moveBlockDown(int $blockId): void
    {
        $this->ensureAdmin();
        $block = $this->findDraftBlock($blockId);
        if ($block === null) {
            return;
        }
        $this->shiftBlock($block, 1);
    }

    public function moveSectionUp(int $sectionId): void
    {
        $this->ensureAdmin();
        $draft = $this->service->findDraft();
        if ($draft === null) {
            return;
        }
        $ordered = $draft->sections()
            ->orderBy('position')
            ->orderBy('id')
            ->pluck('id')
            ->all();
        $idx = array_search($sectionId, $ordered, true);
        if ($idx === false || $idx === 0) {
            return;
        }
        [$ordered[$idx - 1], $ordered[$idx]] = [$ordered[$idx], $ordered[$idx - 1]];
        try {
            $this->service->reorderSections($draft, $ordered, $this->adminUser());
            $this->successMessage = 'Urutan section diperbarui.';
            $this->errorMessage = '';
        } catch (\Throwable $e) {
            $this->errorMessage = $e->getMessage();
        }
    }

    public function moveSectionDown(int $sectionId): void
    {
        $this->ensureAdmin();
        $draft = $this->service->findDraft();
        if ($draft === null) {
            return;
        }
        $ordered = $draft->sections()
            ->orderBy('position')
            ->orderBy('id')
            ->pluck('id')
            ->all();
        $idx = array_search($sectionId, $ordered, true);
        if ($idx === false || $idx === count($ordered) - 1) {
            return;
        }
        [$ordered[$idx + 1], $ordered[$idx]] = [$ordered[$idx], $ordered[$idx + 1]];
        try {
            $this->service->reorderSections($draft, $ordered, $this->adminUser());
            $this->successMessage = 'Urutan section diperbarui.';
            $this->errorMessage = '';
        } catch (\Throwable $e) {
            $this->errorMessage = $e->getMessage();
        }
    }

    private function shiftBlock(MarketingGuideBlock $block, int $delta): void
    {
        $ordered = $block->section->blocks()
            ->orderBy('position')
            ->orderBy('id')
            ->pluck('id')
            ->all();
        $idx = array_search($block->id, $ordered, true);
        if ($idx === false) {
            return;
        }
        $target = $idx + $delta;
        if ($target < 0 || $target >= count($ordered)) {
            return;
        }
        [$ordered[$target], $ordered[$idx]] = [$ordered[$idx], $ordered[$target]];
        try {
            $this->service->reorderBlocks($block->section, $ordered, $this->adminUser());
            $this->successMessage = 'Urutan block diperbarui.';
            $this->errorMessage = '';
        } catch (\Throwable $e) {
            $this->errorMessage = $e->getMessage();
        }
    }

    public function render()
    {
        $this->ensureAdmin();

        $draft = $this->service->findDraft();
        $published = $this->service->currentVersion();
        $sections = collect();
        if ($draft !== null) {
            $sections = $draft->sections()
                ->with(['blocks' => function ($q) {
                    $q->orderBy('position')->orderBy('id');
                }])
                ->orderBy('position')
                ->orderBy('id')
                ->get();
        }

        $grouped = $sections->groupBy(fn ($s) => $s->nav_group ?? 'Tanpa Grup');

        return view('livewire.admin.marketing-guide-content-editor', [
            'draft' => $draft,
            'published' => $published,
            'sections' => $sections,
            'grouped' => $grouped,
            'blockTypes' => MarketingGuideContentService::BLOCK_TYPES,
            'iconWhitelist' => MarketingGuideContentService::ICON_WHITELIST,
        ]);
    }

    private function ensureAdmin(): void
    {
        abort_unless($this->isAdmin(Auth::user()), 403);
    }

    private function adminUser(): User
    {
        $user = Auth::user();
        abort_unless($this->isAdmin($user), 403);

        return $user;
    }

    private function isAdmin(mixed $user): bool
    {
        return $user instanceof User
            && strtolower((string) $user->role) === 'admin'
            && $user->uid !== null;
    }

    private function findDraftSection(int $sectionId): ?MarketingGuideSection
    {
        $draft = $this->service->findDraft();
        if ($draft === null) {
            return null;
        }
        $section = MarketingGuideSection::query()->find($sectionId);
        if ($section === null || (int) $section->version_id !== (int) $draft->id) {
            return null;
        }

        return $section;
    }

    private function findDraftBlock(int $blockId): ?MarketingGuideBlock
    {
        $draft = $this->service->findDraft();
        if ($draft === null) {
            return null;
        }
        $block = MarketingGuideBlock::query()->with('section')->find($blockId);
        if ($block === null) {
            return null;
        }
        if ((int) $block->section->version_id !== (int) $draft->id) {
            return null;
        }

        return $block;
    }

    private function decodeData(string $raw): mixed
    {
        $trim = trim($raw);
        if ($trim === '') {
            return [];
        }
        $decoded = json_decode($trim, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return null;
        }

        return $decoded;
    }

    private function defaultDataRawForType(string $type): string
    {
        $defaults = [
            'text' => ['intro' => '', 'title' => '', 'subtitle' => ''],
            'workflow' => ['steps' => []],
            'flow' => ['boxes' => []],
            'cards' => ['columns' => 2, 'cards' => []],
            'placeholder' => ['icon' => 'info-circle', 'title' => '', 'caption' => ''],
            'tickets' => ['tickets' => [], 'tip' => ''],
            'stats' => ['stats' => []],
            'qr' => [
                'event_name' => '',
                'event_date' => '',
                'icon' => 'qrcode',
                'ticket_holder' => '',
                'ticket_type' => '',
                'ticket_number' => '',
                'entry_window' => '',
                'footer' => '',
            ],
            'faq' => ['items' => []],
            'cta' => [
                'title' => '',
                'subtitle' => '',
                'cta' => [
                    'label' => '',
                    'href' => '',
                    'icon' => 'arrow-right',
                    'variant' => 'primary',
                ],
            ],
        ];
        $payload = $defaults[$type] ?? [];

        return (string) json_encode(
            $payload,
            JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
        );
    }
}
