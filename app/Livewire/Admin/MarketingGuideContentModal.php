<?php

namespace App\Livewire\Admin;

use App\Services\MarketingGuide\MarketingGuideContentService;
use Livewire\Attributes\On;

class MarketingGuideContentModal extends MarketingGuideContentEditor
{
    protected bool $dispatchesModalOpen = false;

    #[On('mge-open-section-editor')]
    public function openSectionEditor(int $sectionId): void
    {
        $this->editingSectionId = null;
        parent::openSectionEditor($sectionId);

        $this->dispatchModalResult(
            'mge-section-modal',
            $this->editingSectionId === $sectionId,
            'Edit Section'
        );
    }

    #[On('mge-open-block-editor')]
    public function openBlockEditor(int $blockId): void
    {
        $this->editingBlockId = null;
        $this->addingBlockForSectionId = null;
        parent::openBlockEditor($blockId);

        $this->dispatchModalResult(
            'mge-block-modal',
            $this->editingBlockId === $blockId,
            'Edit Block'
        );
    }

    #[On('mge-open-add-block')]
    public function openAddBlock(int $sectionId): void
    {
        $this->editingBlockId = null;
        $this->addingBlockForSectionId = null;
        parent::openAddBlock($sectionId);

        $this->dispatchModalResult(
            'mge-block-modal',
            $this->addingBlockForSectionId === $sectionId,
            'Tambah Block'
        );
    }

    public function saveSection(): void
    {
        parent::saveSection();

        if ($this->successMessage !== '') {
            $this->dispatch('mge-content-updated')->to(MarketingGuideContentEditor::class);
        }
    }

    public function saveBlock(): void
    {
        parent::saveBlock();

        if ($this->successMessage !== '') {
            $this->dispatch('mge-content-updated')->to(MarketingGuideContentEditor::class);
        }
    }

    public function render()
    {
        return view('livewire.admin.marketing-guide-content-editor', [
            'modalOnly' => true,
            'blockTypes' => MarketingGuideContentService::BLOCK_TYPES,
            'iconWhitelist' => MarketingGuideContentService::ICON_WHITELIST,
        ]);
    }

    private function dispatchModalResult(string $name, bool $loaded, string $title): void
    {
        if ($loaded) {
            $this->dispatch('mge-modal-loaded', name: $name, title: $title);

            return;
        }

        $this->dispatch(
            'mge-modal-error',
            name: $name,
            message: 'Data editor tidak dapat dimuat. Muat ulang halaman lalu coba lagi.'
        );
    }
}
