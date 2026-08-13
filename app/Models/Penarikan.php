<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Penarikan extends Model
{
    use HasFactory, SoftDeletes;

    public const STATUS_PENDING = 'PENDING';

    public const STATUS_PROCESSING = 'PROCESSING';

    public const STATUS_SUCCESS = 'SUCCESS';

    public const STATUS_REJECTED = 'REJECTED';

    public const STATUS_CANCELLED = 'CANCELLED';

    public const STATUS_FAILED = 'FAILED';

    protected $fillable = [
        'uid',
        'uid_user',
        'amount',
        'note',
        'kwitansi',
        'status',
        'approved_at',
        'bank_name',
        'bank_account_name',
        'bank_account_number',
        'transfer_proof',
        'transfer_proof_uploaded_at',
        'transfer_proof_uploaded_by',
    ];

    protected $casts = [
        'amount' => 'integer',
        'kwitansi' => 'integer',
        'approved_at' => 'datetime',
        'transfer_proof_uploaded_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'uid_user', 'uid');
    }

    public function transferProofUploader()
    {
        return $this->belongsTo(User::class, 'transfer_proof_uploaded_by', 'uid');
    }
}
