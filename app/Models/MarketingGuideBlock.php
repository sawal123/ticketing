<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketingGuideBlock extends Model
{
    use HasFactory;

    public const TYPE_TEXT = 'text';

    public const TYPE_WORKFLOW = 'workflow';

    public const TYPE_FLOW = 'flow';

    public const TYPE_CARDS = 'cards';

    public const TYPE_PLACEHOLDER = 'placeholder';

    public const TYPE_TICKETS = 'tickets';

    public const TYPE_STATS = 'stats';

    public const TYPE_QR = 'qr';

    public const TYPE_FAQ = 'faq';

    public const TYPE_CTA = 'cta';

    public const TYPES = [
        self::TYPE_TEXT,
        self::TYPE_WORKFLOW,
        self::TYPE_FLOW,
        self::TYPE_CARDS,
        self::TYPE_PLACEHOLDER,
        self::TYPE_TICKETS,
        self::TYPE_STATS,
        self::TYPE_QR,
        self::TYPE_FAQ,
        self::TYPE_CTA,
    ];

    protected $fillable = [
        'section_id',
        'type',
        'position',
        'data',
        'is_active',
    ];

    protected $casts = [
        'section_id' => 'integer',
        'position' => 'integer',
        'data' => 'array',
        'is_active' => 'boolean',
    ];

    public function section(): BelongsTo
    {
        return $this->belongsTo(MarketingGuideSection::class, 'section_id');
    }
}
