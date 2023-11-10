<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cash extends Model
{
    use HasFactory;
    protected $fillable = [
        'uid','uid_partner', 'uid_user', 'uid_event','name', 'email', 'alamat', 'lahir'
    ];
}
