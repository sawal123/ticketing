<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MarketingGuideSection extends Model
{
    use HasFactory;

    protected $fillable = [
        'version_id',
        'key',
        'title',
        'slug',
        'nav_group',
        'position',
        'is_active',
    ];

    protected $casts = [
        'version_id' => 'integer',
        'position' => 'integer',
        'is_active' => 'boolean',
    ];

    public function version(): BelongsTo
    {
        return $this->belongsTo(MarketingGuideVersion::class, 'version_id');
    }

    public function blocks(): HasMany
    {
        return $this->hasMany(MarketingGuideBlock::class, 'section_id')
            ->orderBy('position')
            ->orderBy('id');
    }

    public function activeBlocks(): HasMany
    {
        return $this->blocks()->where('is_active', true);
    }
}
