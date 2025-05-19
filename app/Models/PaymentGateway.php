<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentGateway extends Model
{
    use HasFactory;

    protected $fillable=['payment', 'category', 'biaya', 'biaya_type', 'icon', 'is_active', 'slug'];
}
