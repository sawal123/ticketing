<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TutorialProgress extends Model
{
    use HasFactory;

    protected $table = 'tutorial_progress';

    protected $fillable = [
        'user_uid',
        'tutorial_key',
        'completed_at',
        'dismissed_at',
    ];

    protected $casts = [
        'completed_at' => 'datetime',
        'dismissed_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_uid', 'uid');
    }
}
