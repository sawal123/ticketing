<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Partner extends Model
{
    use HasFactory;

    public const TYPE_RUPIAH = 'rupiah';

    public const TYPE_PERSEN = 'persen';

    protected $fillable = [
        'uid',
        'user_uid',
        'event_uid',
        'referensi',
        'name',
        'email',
        'hp',
        'alamat',
        'city',
        'status',
        'discount_type',
        'discount_value',
        'discount_max',
        'allow_promo_tickets',
        'commission_type',
        'commission_value',
    ];

    protected $casts = [
        'discount_value' => 'integer',
        'discount_max' => 'integer',
        'allow_promo_tickets' => 'boolean',
        'commission_value' => 'integer',
    ];

    public function event()
    {
        return $this->belongsTo(Event::class, 'event_uid', 'uid');
    }

    public function carts()
    {
        return $this->hasMany(Cart::class, 'partner_uid', 'uid');
    }

    public function hasActiveDiscount(): bool
    {
        $type = strtolower((string) ($this->discount_type ?? ''));
        $value = (int) ($this->discount_value ?? 0);

        return $this->status === 'active'
            && in_array($type, [self::TYPE_RUPIAH, self::TYPE_PERSEN], true)
            && $value > 0;
    }

    public function calculateDiscount(int $ticketTotal): int
    {
        if (! $this->hasActiveDiscount() || $ticketTotal <= 0) {
            return 0;
        }

        $type = strtolower((string) $this->discount_type);
        $value = (int) $this->discount_value;

        if ($type === self::TYPE_PERSEN) {
            $percent = min(99, $value);
            $discount = (int) round(($ticketTotal * $percent) / 100);
            $maxDiscount = (int) ($this->discount_max ?? 0);

            if ($maxDiscount > 0) {
                $discount = min($discount, $maxDiscount);
            }

            return min($discount, $ticketTotal);
        }

        return min($value, $ticketTotal);
    }

    public function hasActiveCommission(): bool
    {
        $type = strtolower((string) ($this->commission_type ?? ''));
        $value = (int) ($this->commission_value ?? 0);

        return $this->status === 'active'
            && in_array($type, [self::TYPE_RUPIAH, self::TYPE_PERSEN], true)
            && $value > 0;
    }

    public function calculateCommission(int $netSubtotal, int $totalQty): int
    {
        if (! $this->hasActiveCommission() || $totalQty <= 0) {
            return 0;
        }

        $type = strtolower((string) $this->commission_type);
        $value = (int) $this->commission_value;

        if ($type === self::TYPE_PERSEN) {
            $percent = min(100, $value);

            return (int) round((max(0, $netSubtotal) * $percent) / 100);
        }

        return $totalQty * $value;
    }

    public function referralUrl(?Event $event = null): ?string
    {
        $targetEvent = $event ?? $this->event;
        if (! $targetEvent || ! filled($targetEvent->slug) || ! filled($this->referensi)) {
            return null;
        }

        return url('/ticket/'.$targetEvent->slug).'?ref='.urlencode($this->referensi);
    }
}
