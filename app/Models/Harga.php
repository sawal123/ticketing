<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Harga extends Model
{

    use HasFactory;
    protected $fillable = [
        'uid', 'kategori', 'qty', 'harga',
    ];

    public function event(){
        return $this->belongsTo(Event::class);
    }
}
