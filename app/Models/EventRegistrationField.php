<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EventRegistrationField extends Model
{
    use HasFactory;

    public const TYPES = ['text', 'number', 'select', 'textarea'];

    public const SCOPES = ['registration', 'member'];

    public const SCOPE_REGISTRATION = 'registration';

    public const SCOPE_MEMBER = 'member';

    protected $fillable = ['event_uid', 'label', 'type', 'scope', 'is_required', 'options', 'sort_order'];

    protected $casts = [
        'options' => 'array',
        'is_required' => 'boolean',
        'sort_order' => 'integer',
    ];

    public static function types(): array
    {
        return self::TYPES;
    }

    public static function scopes(): array
    {
        return self::SCOPES;
    }

    public function event()
    {
        return $this->belongsTo(Event::class, 'event_uid', 'uid');
    }
}
