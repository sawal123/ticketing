<?php

namespace App\Services\Tickets;

use App\Models\Cart;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\QueryException;
use Illuminate\Encryption\Encrypter;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use LogicException;
use RuntimeException;

class GateTokenService
{
    public const TOKEN_BYTES = 32;

    public const TOKEN_PATTERN = '/^[A-Za-z0-9_-]{43}$/D';

    public const MANUAL_CODE_LENGTH = 8;

    public const MANUAL_CODE_ALPHABET = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';

    public const MANUAL_CODE_PATTERN = '/^[23456789ABCDEFGHJKLMNPQRSTUVWXYZ]{8}$/D';

    private const MANUAL_CODE_KEY_DOMAIN = 'gate-manual-code-v1';

    private const COLLISION_RETRIES = 5;

    /**
     * Issue a QR gate token and manual code with one timestamp and version.
     *
     * The token return value is retained for compatibility with existing callers.
     */
    public function issue(Cart $cart, bool $rotation = false): string
    {
        $issuedAt = now();
        $version = $rotation
            ? max(1, (int) $cart->gate_token_version) + 1
            : max(1, (int) $cart->gate_token_version);

        for ($attempt = 1; $attempt <= self::COLLISION_RETRIES; $attempt++) {
            $token = $this->generate();
            $manualCode = $this->generateManualCode();

            $cart->gate_token_hash = $this->hash($token);
            $cart->gate_token_encrypted = $this->encrypter()->encryptString($token);
            $cart->gate_manual_code_hash = $this->hashManualCode($manualCode);
            $cart->gate_manual_code_encrypted = $this->encrypter()->encryptString($manualCode);
            $cart->gate_token_issued_at = $issuedAt;
            $cart->gate_token_version = $version;

            try {
                DB::transaction(static fn () => $cart->save());

                return $token;
            } catch (QueryException $exception) {
                if (! $this->isManualCodeCollision($exception)
                    || $attempt === self::COLLISION_RETRIES) {
                    throw $exception;
                }
            }
        }

        throw new RuntimeException('Kode manual unik gagal diterbitkan.');
    }

    /**
     * Backfill only a missing manual code without rotating a valid QR token.
     */
    public function issueMissingManualCode(Cart $cart): string
    {
        if (! $cart->gate_token_hash || ! $cart->gate_token_encrypted) {
            throw new RuntimeException('Gate token harus tersedia sebelum menerbitkan kode manual.');
        }

        if ($cart->gate_manual_code_hash || $cart->gate_manual_code_encrypted) {
            throw new RuntimeException('Kode manual sudah diterbitkan untuk tiket ini.');
        }

        for ($attempt = 1; $attempt <= self::COLLISION_RETRIES; $attempt++) {
            $manualCode = $this->generateManualCode();
            $cart->gate_manual_code_hash = $this->hashManualCode($manualCode);
            $cart->gate_manual_code_encrypted = $this->encrypter()->encryptString($manualCode);

            try {
                DB::transaction(static fn () => $cart->save());

                return $manualCode;
            } catch (QueryException $exception) {
                if (! $this->isManualCodeCollision($exception)
                    || $attempt === self::COLLISION_RETRIES) {
                    throw $exception;
                }
            }
        }

        throw new RuntimeException('Kode manual unik gagal diterbitkan.');
    }

    public function issueIfEnabled(Cart $cart): bool
    {
        if (! $this->eventIsEnabled($cart->event_uid)
            || $cart->status !== Cart::STATUS_SUCCESS
            || $cart->scanned_at
            || (string) $cart->konfirmasi === '1') {
            return false;
        }

        if ($cart->gate_token_hash && $cart->gate_token_encrypted) {
            if (! $cart->gate_manual_code_hash && ! $cart->gate_manual_code_encrypted) {
                $this->issueMissingManualCode($cart);

                return true;
            }

            return false;
        }

        $this->issue($cart);

        return true;
    }

    public function tokenForQr(Cart $cart): string
    {
        if (! $cart->gate_token_encrypted || ! $cart->gate_token_hash) {
            throw new RuntimeException('Gate token belum diterbitkan untuk tiket ini.');
        }

        try {
            $token = $this->encrypter()->decryptString($cart->gate_token_encrypted);
        } catch (DecryptException) {
            throw new RuntimeException('Gate token tidak dapat didekripsi.');
        }

        if (! $this->isValidFormat($token)
            || ! hash_equals($cart->gate_token_hash, $this->hash($token))) {
            throw new RuntimeException('Integritas gate token tidak valid.');
        }

        return $token;
    }

    public function manualCodeForDisplay(Cart $cart): string
    {
        if (! $cart->gate_manual_code_encrypted || ! $cart->gate_manual_code_hash) {
            throw new RuntimeException('Kode manual belum diterbitkan untuk tiket ini.');
        }

        try {
            $manualCode = $this->encrypter()->decryptString($cart->gate_manual_code_encrypted);
        } catch (DecryptException) {
            throw new RuntimeException('Kode manual tidak dapat didekripsi.');
        }

        if (preg_match(self::MANUAL_CODE_PATTERN, $manualCode) !== 1
            || ! hash_equals($cart->gate_manual_code_hash, $this->hashManualCode($manualCode))) {
            throw new RuntimeException('Integritas kode manual tidak valid.');
        }

        return substr($manualCode, 0, 4).'-'.substr($manualCode, 4, 4);
    }

    public function generate(): string
    {
        return rtrim(strtr(base64_encode(random_bytes(self::TOKEN_BYTES)), '+/', '-_'), '=');
    }

    public function generateManualCode(): string
    {
        $lastIndex = strlen(self::MANUAL_CODE_ALPHABET) - 1;
        $code = '';

        for ($index = 0; $index < self::MANUAL_CODE_LENGTH; $index++) {
            $code .= self::MANUAL_CODE_ALPHABET[random_int(0, $lastIndex)];
        }

        return $code;
    }

    public function normalizeManualCode(string $manualCode): string
    {
        $normalized = preg_replace('/[\s-]+/u', '', strtoupper(trim($manualCode)));

        return is_string($normalized) ? $normalized : '';
    }

    public function hash(string $token): string
    {
        return hash('sha256', $token);
    }

    public function hashManualCode(string $manualCode): string
    {
        $normalized = $this->normalizeManualCode($manualCode);
        if (! $this->isValidManualCode($normalized)) {
            throw new InvalidArgumentException('Format kode manual tidak valid.');
        }

        return hash_hmac('sha256', $normalized, $this->manualCodeKey());
    }

    public function isValidFormat(string $token): bool
    {
        return preg_match(self::TOKEN_PATTERN, $token) === 1;
    }

    public function isValidManualCode(string $manualCode): bool
    {
        return preg_match(self::MANUAL_CODE_PATTERN, $this->normalizeManualCode($manualCode)) === 1;
    }

    public function eventIsEnabled(string $eventUid): bool
    {
        return in_array($eventUid, config('gate-tokens.active_event_uids', []), true);
    }

    public function cashTicketProof(Cart $cart): string
    {
        if (! $cart->gate_token_hash) {
            throw new RuntimeException('Gate token belum diterbitkan untuk tiket ini.');
        }

        return hash_hmac('sha256', $this->cashProofPayload($cart), $this->keyBytes());
    }

    public function validCashTicketProof(Cart $cart, ?string $proof): bool
    {
        return is_string($proof)
            && preg_match('/^[a-f0-9]{64}$/D', $proof) === 1
            && hash_equals($this->cashTicketProof($cart), $proof);
    }

    private function cashProofPayload(Cart $cart): string
    {
        return implode('|', [
            $cart->uid,
            $cart->gate_token_hash,
            (string) $cart->gate_token_version,
        ]);
    }

    private function manualCodeKey(): string
    {
        return hash_hmac('sha256', self::MANUAL_CODE_KEY_DOMAIN, $this->keyBytes(), true);
    }

    private function isManualCodeCollision(QueryException $exception): bool
    {
        $message = strtolower($exception->getMessage());
        $sqlState = (string) ($exception->errorInfo[0] ?? $exception->getCode());

        return in_array($sqlState, ['23000', '23505'], true)
            && (str_contains($message, 'gate_manual_code_hash')
                || str_contains($message, 'carts_gate_manual_code_hash_unique'));
    }

    private function encrypter(): Encrypter
    {
        return new Encrypter($this->keyBytes(), 'aes-256-cbc');
    }

    private function keyBytes(): string
    {
        $configured = (string) config('gate-tokens.key');
        $key = str_starts_with($configured, 'base64:')
            ? base64_decode(substr($configured, 7), true)
            : $configured;

        if (! is_string($key) || strlen($key) !== 32) {
            throw new LogicException(
                'GATE_TOKEN_KEY wajib berisi key acak 32 byte (disarankan format base64).'
            );
        }

        return $key;
    }
}
