<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Harga extends Model
{
    use HasFactory;

    public const DEFAULT_MAX_ORDER_QTY = 5;

    public const PROMO_TYPE_RUPIAH = 'rupiah';

    public const PROMO_TYPE_PERSEN = 'persen';

    protected $fillable = [
        'uid',
        'kategori',
        'qty',
        'sold_qty',
        'reserved_qty',
        'harga',
        'is_promo',
        'promo_type',
        'promo_value',
        'status',
        'max_order_qty',
        'description',
    ];

    protected $casts = [
        'qty' => 'integer',
        'sold_qty' => 'integer',
        'reserved_qty' => 'integer',
        'harga' => 'integer',
        'is_promo' => 'boolean',
        'promo_value' => 'integer',
        'max_order_qty' => 'integer',
    ];

    public function event()
    {
        return $this->belongsTo(Event::class, 'uid', 'uid');
    }

    public function hargaCarts()
    {
        return $this->hasMany(HargaCart::class, 'harga_id', 'id');
    }

    public function remainingQty(): int
    {
        return max(0, (int) $this->qty - (int) $this->sold_qty - (int) $this->reserved_qty);
    }

    public function maxOrderQty(): int
    {
        $value = (int) $this->max_order_qty;

        return $value > 0 ? $value : self::DEFAULT_MAX_ORDER_QTY;
    }

    public function hasActivePromo(): bool
    {
        if (! (bool) ($this->is_promo ?? false)) {
            return false;
        }

        $basePrice = (int) $this->harga;
        $promoValue = (int) ($this->promo_value ?? 0);
        $promoType = strtolower((string) ($this->promo_type ?? ''));

        if ($basePrice <= 0 || $promoValue <= 0) {
            return false;
        }

        if (! in_array($promoType, [self::PROMO_TYPE_RUPIAH, self::PROMO_TYPE_PERSEN], true)) {
            return false;
        }

        return $this->promoDiscountAmount() > 0 && $this->effectivePrice() < $basePrice;
    }

    public function promoDiscountAmount(): int
    {
        if (! (bool) ($this->is_promo ?? false)) {
            return 0;
        }

        $basePrice = max(0, (int) $this->harga);
        $promoValue = max(0, (int) ($this->promo_value ?? 0));
        $promoType = strtolower((string) ($this->promo_type ?? ''));

        if ($basePrice <= 0 || $promoValue <= 0) {
            return 0;
        }

        if ($promoType === self::PROMO_TYPE_PERSEN) {
            $percent = min(99, $promoValue);

            return min($basePrice - 1, (int) round(($basePrice * $percent) / 100));
        }

        if ($promoType === self::PROMO_TYPE_RUPIAH) {
            return min($basePrice - 1, $promoValue);
        }

        return 0;
    }

    public function effectivePrice(): int
    {
        $basePrice = max(0, (int) $this->harga);
        $discount = $this->promoDiscountAmount();

        return max(0, $basePrice - $discount);
    }

    public function promoDiscountPercent(): int
    {
        if (! $this->hasActivePromo()) {
            return 0;
        }

        $promoType = strtolower((string) ($this->promo_type ?? ''));
        if ($promoType === self::PROMO_TYPE_PERSEN) {
            return max(1, min(99, (int) $this->promo_value));
        }

        $basePrice = max(1, (int) $this->harga);
        $computed = (int) round(($this->promoDiscountAmount() / $basePrice) * 100);

        return max(1, min(99, $computed));
    }
}
