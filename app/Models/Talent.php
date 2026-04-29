<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Talent extends Model
{
    use HasFactory;

    protected $fillable = [
        'uid',
        'talent',
        'gambar',
        'link',
    ];


    public function event()
    {
        // Wajib menentukan foreign key dan local key karena nama kolom tidak standar
        return $this->belongsTo(Event::class, 'uid', 'uid');
    }
}
