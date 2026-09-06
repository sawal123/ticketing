<?php

namespace App\Services\MarketingGuide;

use App\Models\MarketingGuideBlock;
use App\Models\MarketingGuideSection;
use App\Models\MarketingGuideVersion;
use Illuminate\Database\Eloquent\Collection;
use InvalidArgumentException;

class MarketingGuideContentService
{
    /**
     * Resolve currently published version, falling back to the most recent active version.
     */
    public function currentVersion(): ?MarketingGuideVersion
    {
        $published = MarketingGuideVersion::query()
            ->published()
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->first();

        if ($published !== null) {
            return $published;
        }

        return MarketingGuideVersion::query()
            ->active()
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
        if (! in_array($type, MarketingGuideBlock::TYPES, true)) {
            throw new InvalidArgumentException(sprintf(
                'Tipe block "%s" tidak diizinkan. Tipe yang valid: %s.',
                $type,
                implode(', ', MarketingGuideBlock::TYPES)
            ));
        }
    }
}