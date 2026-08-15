<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EventPaymentGateway extends Model
{
    use HasFactory;

    public const FEE_MODE_GLOBAL = 'global';
    public const FEE_MODE_MANUAL = 'manual';

    protected $fillable = [
        'event_id',
        'payment_gateway_id',
        'is_active',
        'fee_mode',
        'fee_fixed',
        'fee_percent',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'fee_fixed' => 'decimal:2',
        'fee_percent' => 'decimal:4',
    ];

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function paymentGateway()
    {
        return $this->belongsTo(PaymentGateway::class);
    }
}
