<?php

namespace App\Models;

use App\Models\Event;
use Laravel\Sanctum\HasApiTokens;

use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\Schema;


class User extends Authenticatable
{
    use HasApiTokens;
    use HasFactory, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     * 
     */
    /** @var \App\Models\User $user **/
    // $user = Auth::user();
    const USER_ROLE = 'user';
    const ADMIN_ROLE = 'admin';
    const STAFF_ROLE = 'staff';
    protected $fillable = [
        'uid',
        'name',
        'email',
        'nomor',
        'birthday',
        'alamat',
        'kota',
        'gender',
        'gambar',
        'role',
        'password',
        'parent_uid',
        'google_id',
        'google_token',
        'google_refresh_token',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    protected static function booted(): void
    {
        static::creating(function (User $user) {
            foreach ([
                'birthday' => '2000-01-01',
                'gender' => 'pria',
                'kota' => '-',
                'alamat' => '-',
                'nomor' => '-',
                'user_uid' => '-',
            ] as $column => $default) {
                if (static::usersColumnExists($column) && blank($user->{$column})) {
                    $user->{$column} = $default;
                }
            }
        });
    }

    protected static function usersColumnExists(string $column): bool
    {
        return Schema::hasColumn('users', $column);
    }

    public function events()
    {
        return $this->hasMany(Event::class, 'user_uid', 'uid');
    }

    public function carts()
    {
        return $this->hasMany(Cart::class, 'user_uid', 'uid');
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class, 'user_uid', 'uid');
    }
}
