<?php

namespace App\Services\Tickets;

use App\Models\Cart;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Encryption\Encrypter;
use LogicException;
use RuntimeException;

class GateTokenService
{
    public const TOKEN_BYTES = 32;

    public const TOKEN_PATTERN = '/^[A-Za-z0-9_-]{43}$/D';

    public function issue(Cart $cart, bool $rotation = false): string
    {
        $token = $this->generate();

        $cart->gate_token_hash = $this->hash($token);
        $cart->gate_token_encrypted = $this->encrypter()->encryptString($token);
        $cart->gate_token_issued_at = now();
        $cart->gate_token_version = $rotation
            ? max(1, (int) $cart->gate_token_version) + 1
            : max(1, (int) $cart->gate_token_version);
        $cart->save();

        return $token;
    }

    public function issueIfEnabled(Cart $cart): bool
    {
        if (! $this->eventIsEnabled($cart->event_uid)
            || $cart->status !== Cart::STATUS_SUCCESS
            || $cart->gate_token_hash
            || $cart->scanned_at
            || (string) $cart->konfirmasi === '1') {
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

    public function generate(): string
    {
        return rtrim(strtr(base64_encode(random_bytes(self::TOKEN_BYTES)), '+/', '-_'), '=');
    }

    public function hash(string $token): string
    {
        return hash('sha256', $token);
    }

    public function isValidFormat(string $token): bool
    {
        return preg_match(self::TOKEN_PATTERN, $token) === 1;
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
