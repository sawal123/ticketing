<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Landing extends Model
{
    use HasFactory;

    protected $fillable = [
        'description',
        'keyword',
        'logo',
        'icon',
        'nodata_image',
        'nodata_title',
        'nodata_subtitle',
    ];

    public function emptyStateImageUrl(): string
    {
        if (filled($this->nodata_image)) {
            return asset('storage/setting/'.$this->nodata_image);
        }

        return asset('/storage/setting/nodata.svg');
    }
}
