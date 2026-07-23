<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Harga extends Model
{

    use HasFactory;
    protected $fillable = [
        'uid',
        'kategori',
        'qty',
        'sold_qty',
        'reserved_qty',
        'harga',
        'status',
    ];

    protected $casts = [
        'qty' => 'integer',
        'sold_qty' => 'integer',
        'reserved_qty' => 'integer',
        'harga' => 'integer',
    ];

    public function event()
    {
        return $this->belongsTo(Event::class);
    }
    public function hargaCarts()
    {
        return $this->hasMany(HargaCart::class, 'harga_id', 'id');
    }

    public function remainingQty(): int
    {
        return max(0, (int) $this->qty - (int) $this->sold_qty - (int) $this->reserved_qty);
    }
}
