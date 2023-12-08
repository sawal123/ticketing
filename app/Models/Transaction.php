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
        'invoice',
        'payment_type',
        'status_transaksi',
    ];
}
