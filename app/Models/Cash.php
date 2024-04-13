<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Cash extends Model
{
    use HasFactory;
    use SoftDeletes;
    protected $fillable = [
        'uid','uid_partner', 'uid_user', 'uid_event','name', 'email', 'nomor',  'alamat', 'lahir', 'gender'
    ];
}
