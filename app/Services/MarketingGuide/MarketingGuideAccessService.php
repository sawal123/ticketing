<?php

namespace App\Services\MarketingGuide;

use App\Models\MarketingGuideAccess;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class MarketingGuideAccessService
{
    public const TOKEN_BYTES = 32;

    public const TOKEN_PATTERN = '/^[A-Za-z0-9_-]{43}$/D';

    public const STATUS_VALID = 'valid';

    public const STATUS_EXPIRED = 'expired';

    public const STATUS_REVOKED = 'revoked';

    public const STATUS_INVALID = 'invalid';

    /**
     * Create temporary access. Plain token is returned once and never stored.
     *
     * @return array{token: string, access: MarketingGuideAccess}
     */
    public function create(User $creator, CarbonInterface $expiresAt, ?string $recipientName = null): array
    {
        if (blank($creator->uid)) {
            throw new InvalidArgumentException('Pembuat akses harus memiliki uid.');
        }

        if ($expiresAt->lessThanOrEqualTo(now())) {
            throw new InvalidArgumentException('Waktu kedaluwarsa harus di masa depan.');
        }

        $token = $this->generateToken();

        $access = MarketingGuideAccess::query()->create([
            'token_hash' => $this->hash($token),
            'recipient_name' => $recipientName,
            'expires_at' => $expiresAt,
            'revoked_at' => null,
            'last_accessed_at' => null,
            'access_count' => 0,
            'created_by' => $creator->uid,
        ]);

        return [
            'token' => $token,
            'access' => $access,
        ];
    }

    public function hash(string $token): string
    {
        return hash('sha256', $token);
    }

    public function generateToken(): string
    {
        return rtrim(strtr(base64_encode(random_bytes(self::TOKEN_BYTES)), '+/', '-_'), '=');
    }

    public function isValidFormat(string $token): bool
    {
        return preg_match(self::TOKEN_PATTERN, $token) === 1;
    }

    public function findByToken(string $token): ?MarketingGuideAccess
    {
        if (! $this->isValidFormat($token)) {
            return null;
        }

        return MarketingGuideAccess::query()
            ->where('token_hash', $this->hash($token))
            ->first();
    }

    public function resolveStatus(?MarketingGuideAccess $access): string
    {
        if ($access === null) {
            return self::STATUS_INVALID;
        }

        if ($access->isRevoked()) {
            return self::STATUS_REVOKED;
        }

        if ($access->isExpired()) {
            return self::STATUS_EXPIRED;
        }

        return self::STATUS_VALID;
    }

    public function revoke(MarketingGuideAccess $access): MarketingGuideAccess
    {
        if ($access->revoked_at === null) {
            $access->forceFill([
                'revoked_at' => now(),
            ])->save();
        }

        return $access->refresh();
    }

    /**
     * Extend expires_at only. Never clears revoked_at.
     */
    public function extend(MarketingGuideAccess $access, int $days): MarketingGuideAccess
    {
        if ($days < 1) {
            throw new InvalidArgumentException('Perpanjangan minimal 1 hari.');
        }

        $base = $access->expires_at !== null && $access->expires_at->isFuture()
            ? $access->expires_at->copy()
            : now();

        $access->forceFill([
            'expires_at' => $base->addDays($days),
        ])->save();

        return $access->refresh();
    }

    public function displayStatus(MarketingGuideAccess $access): string
    {
        return match ($this->resolveStatus($access)) {
            self::STATUS_REVOKED => 'Revoked',
            self::STATUS_EXPIRED => 'Expired',
            self::STATUS_VALID => 'Active',
            default => 'Invalid',
        };
    }

    public function recordAccess(MarketingGuideAccess $access): MarketingGuideAccess
    {
        return DB::transaction(function () use ($access) {
            $locked = MarketingGuideAccess::query()
                ->whereKey($access->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if (! $locked->isValid()) {
                return $locked;
            }

            $locked->forceFill([
                'access_count' => ((int) $locked->access_count) + 1,
                'last_accessed_at' => now(),
            ])->save();

            return $locked->refresh();
        });
    }
}
