<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Transaction extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uid',
        'user_uid',
        'event_uid',
        'amount',
        'gross_amount',
        'invoice',
        'payment_type',
        'status_transaksi',
        'paid_at',
    ];

    protected $casts = [
        'gross_amount' => 'integer',
        'paid_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_uid', 'uid');
    }

    public function event()
    {
        return $this->belongsTo(Event::class, 'event_uid', 'uid');
    }
}
