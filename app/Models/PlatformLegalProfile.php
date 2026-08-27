<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlatformLegalProfile extends Model
{
    use HasFactory;

    public const DEFAULT_KEY = 'default';

    protected $fillable = [
        'profile_key',
        'company_name',
        'legal_id',
        'address',
        'representative_name',
        'representative_position',
        'email',
        'phone',
        'website',
    ];
}
