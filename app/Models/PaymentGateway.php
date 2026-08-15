<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentGateway extends Model
{
    use HasFactory;

    protected $fillable = [
        'payment',
        'category',
        'biaya',
        'biaya_type',
        'default_fee_fixed',
        'default_fee_percent',
        'midtrans_code',
        'icon',
        'is_active',
        'slug',
    ];

    protected $casts = [
        'biaya' => 'decimal:2',
        'default_fee_fixed' => 'decimal:2',
        'default_fee_percent' => 'decimal:4',
        'is_active' => 'boolean',
    ];

    public function eventPaymentGateways()
    {
        return $this->hasMany(EventPaymentGateway::class);
    }

    public function events()
    {
        return $this->belongsToMany(Event::class, 'event_payment_gateways')
            ->withPivot(['id', 'is_active', 'fee_mode', 'fee_fixed', 'fee_percent'])
            ->withTimestamps();
    }
}
