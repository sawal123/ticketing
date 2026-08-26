<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EventBankAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_uid',
        'bank_name',
        'account_number',
        'account_holder_name',
        'bank_book_path',
        'bank_book_original_name',
        'bank_book_mime',
        'status',
        'verified_at',
        'verified_by',
        'rejection_reason',
    ];

    protected $casts = [
        'verified_at' => 'datetime',
    ];

    public function event()
    {
        return $this->belongsTo(Event::class, 'event_uid', 'uid');
    }
}
