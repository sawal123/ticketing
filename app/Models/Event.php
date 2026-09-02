<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Event extends Model
{
    use HasFactory, SoftDeletes;

    public const REGISTRATION_MODE_TICKETING = 'ticketing';

    public const REGISTRATION_MODE_INDIVIDUAL = 'individual';

    public const REGISTRATION_MODE_TEAM = 'team';

    public const REGISTRATION_MODES = [
        self::REGISTRATION_MODE_TICKETING,
        self::REGISTRATION_MODE_INDIVIDUAL,
        self::REGISTRATION_MODE_TEAM,
    ];

    protected $fillable = [
        'category_id',
        'uid',
        'user_uid',
        'event',
        'alamat',
        'tanggal',
        'event_end',
        'venue_name',
        'venue_address',
        'venue_city',
        'venue_province',
        'status',
        'cover',
        'fee',
        'deskripsi',
        'map',
        'pajak',
        'start_sale',
        'slug',
        'konfirmasi',
        'payment_otp_enabled',
        'registration_mode',
    ];

    protected $casts = [
        'payment_otp_enabled' => 'boolean',
    ];

    public static function registrationModes(): array
    {
        return self::REGISTRATION_MODES;
    }

    public static function registrationModeOptions(): array
    {
        return [
            self::REGISTRATION_MODE_TICKETING => [
                'label' => 'Ticketing Biasa',
                'description' => 'Konser, festival, seminar, dan event umum.',
            ],
            self::REGISTRATION_MODE_INDIVIDUAL => [
                'label' => 'Pendaftaran Individu',
                'description' => 'Pendaftaran peserta per orang.',
            ],
            self::REGISTRATION_MODE_TEAM => [
                'label' => 'Pendaftaran Tim',
                'description' => 'Pendaftaran satu tim/kelompok.',
            ],
        ];
    }

    public static function normalizeRegistrationMode(?string $mode): string
    {
        return in_array($mode, self::REGISTRATION_MODES, true)
            ? $mode
            : self::REGISTRATION_MODE_TICKETING;
    }

    public static function registrationModeLabel(?string $mode): string
    {
        $mode = self::normalizeRegistrationMode($mode);

        return self::registrationModeOptions()[$mode]['label'];
    }

    public function getRegistrationModeAttribute($value): string
    {
        return self::normalizeRegistrationMode($value);
    }

    public function registrationModeLocked(): bool
    {
        return $this->hasRegistrationModeTicketCategories()
            || $this->hasRegistrationModeTransactions();
    }

    public function hasRegistrationModeTicketCategories(): bool
    {
        if ($this->relationLoaded('hargas')) {
            return $this->hargas->isNotEmpty();
        }

        return $this->hargas()->exists();
    }

    public function hasRegistrationModeTransactions(): bool
    {
        if ($this->relationLoaded('carts')) {
            return $this->carts->isNotEmpty();
        }

        return $this->carts()->exists();
    }

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id', 'id');
    }

    public function fasilitas()
    {
        return $this->belongsToMany(Fasilitas::class, 'event_fasilitas', 'event_uid', 'fasilitas_id', 'uid', 'id');
    }

    public function organizer()
    {
        return $this->hasOne(EventOrganizer::class, 'event_uid', 'uid');
    }

    public function bankAccount()
    {
        return $this->hasOne(EventBankAccount::class, 'event_uid', 'uid');
    }

    public function documents()
    {
        return $this->hasMany(EventDocument::class, 'event_uid', 'uid');
    }

    public function organizerLetter()
    {
        return $this->hasOne(EventDocument::class, 'event_uid', 'uid')
            ->where('document_type', EventDocument::TYPE_ORGANIZER_LETTER);
    }

    public function responsibleIdentityDocument()
    {
        return $this->hasOne(EventDocument::class, 'event_uid', 'uid')
            ->where('document_type', EventDocument::TYPE_RESPONSIBLE_IDENTITY);
    }

    public function agreements()
    {
        return $this->hasMany(Agreement::class, 'event_uid', 'uid');
    }

    public function currentMouAgreement()
    {
        return $this->hasOne(Agreement::class, 'event_uid', 'uid')
            ->where('type', Agreement::TYPE_MOU)
            ->where('version', 1);
    }

    public function latestCompletedAgreement(): ?Agreement
    {
        return $this->agreements()
            ->where('status', Agreement::STATUS_COMPLETED)
            ->orderByRaw("CASE WHEN type = 'addendum' THEN 2 ELSE 1 END DESC")
            ->orderByDesc('version')
            ->first();
    }

    public function activeAgreement(): ?Agreement
    {
        return $this->agreements()
            ->whereNotIn('status', [Agreement::STATUS_COMPLETED, Agreement::STATUS_CANCELLED, Agreement::STATUS_REJECTED])
            ->latest('id')
            ->first()
            ?? $this->latestCompletedAgreement();
    }

    public function harga()
    {
        return $this->hasOne(Harga::class, 'uid', 'uid')
            ->where('status', 'active')
            ->orderByRaw('CAST(harga AS UNSIGNED) ASC');
    }

    public function talents() // Ubah nama jadi plural (talents)
    {
        return $this->hasMany(Talent::class, 'uid', 'uid');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_uid', 'uid');
    }

    // Hapus method users() yang duplikat

    public function carts() // Ubah nama jadi plural (carts) dan ganti jadi hasMany
    {
        return $this->hasMany(Cart::class, 'event_uid', 'uid');
    }

    public function hargas()
    {
        return $this->hasMany(Harga::class, 'uid', 'uid');
    }

    public function eventPaymentGateways()
    {
        return $this->hasMany(EventPaymentGateway::class);
    }

    public function paymentGateways()
    {
        return $this->belongsToMany(PaymentGateway::class, 'event_payment_gateways')
            ->withPivot(['id', 'is_active', 'fee_mode', 'fee_fixed', 'fee_percent'])
            ->withTimestamps();
    }
    // Hapus method harga_carts() dari sini. Relasinya agak aneh kalau Event langsung ke HargaCart tanpa lewat Cart.
}
