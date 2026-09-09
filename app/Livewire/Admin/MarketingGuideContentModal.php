<?php

namespace App\Livewire\Admin;

use App\Services\MarketingGuide\MarketingGuideContentService;
use Livewire\Attributes\On;

class MarketingGuideContentModal extends MarketingGuideContentEditor
{
    #[On('mge-open-section-editor')]
    public function openSectionEditor(int $sectionId): void
    {
        parent::openSectionEditor($sectionId);
    }

    #[On('mge-open-block-editor')]
    public function openBlockEditor(int $blockId): void
    {
        parent::openBlockEditor($blockId);
    }

    #[On('mge-open-add-block')]
    public function openAddBlock(int $sectionId): void
    {
        parent::openAddBlock($sectionId);
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
}
