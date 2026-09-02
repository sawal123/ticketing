<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Harga extends Model
{
    use HasFactory;

    public const DEFAULT_MAX_ORDER_QTY = 5;

    protected $fillable = [
        'uid',
        'kategori',
        'qty',
        'sold_qty',
        'reserved_qty',
        'harga',
        'status',
        'max_order_qty',
        'description',
    ];

    protected $casts = [
        'qty' => 'integer',
        'sold_qty' => 'integer',
        'reserved_qty' => 'integer',
        'harga' => 'integer',
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
}
