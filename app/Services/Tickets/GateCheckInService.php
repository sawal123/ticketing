<?php

namespace App\Services\Tickets;

use App\Exceptions\GateTokenException;
use App\Models\Cart;
use App\Models\Event;
use Closure;
use Illuminate\Support\Facades\DB;

class GateCheckInService
{
    public function __construct(private GateTokenService $tokens) {}

    public function inspect(string $token, string $ownerUid): Cart
    {
        return $this->inspectResolved(
            fn () => $this->findByToken($token),
            $ownerUid,
        );
    }

    public function inspectManual(string $manualCode, string $eventUid, string $ownerUid): Cart
    {
        $this->assertEventAccess($eventUid, $ownerUid);

        return $this->inspectResolved(
            fn () => $this->findByManualCode($manualCode, $eventUid),
            $ownerUid,
        );
    }

    public function checkIn(
        string $token,
        string $ownerUid,
        string $actorUid,
        ?string $deviceId = null
    ): Cart {
        return $this->checkInResolved(
            fn () => $this->findByToken($token, true),
            $ownerUid,
            $actorUid,
            $deviceId,
        );
    }

    public function checkInManual(
        string $manualCode,
        string $eventUid,
        string $ownerUid,
        string $actorUid,
        ?string $deviceId = null
    ): Cart {
        $this->assertEventAccess($eventUid, $ownerUid);

        return $this->checkInResolved(
            fn () => $this->findByManualCode($manualCode, $eventUid, true),
            $ownerUid,
            $actorUid,
            $deviceId,
        );
    }

    private function inspectResolved(Closure $resolve, string $ownerUid): Cart
    {
        $cart = $resolve();
        $this->assertTicketAccess($cart, $ownerUid);

        return $cart;
    }

    private function checkInResolved(
        Closure $resolve,
        string $ownerUid,
        string $actorUid,
        ?string $deviceId
    ): Cart {
        return DB::transaction(function () use ($resolve, $ownerUid, $actorUid, $deviceId) {
            $cart = $resolve();
            $this->assertTicketAccess($cart, $ownerUid);

            $updated = Cart::query()
                ->whereKey($cart->id)
                ->where('status', Cart::STATUS_SUCCESS)
                ->whereNull('scanned_at')
                ->where(function ($query) {
                    $query->whereNull('konfirmasi')->orWhere('konfirmasi', '0');
                })
                ->update([
                    'konfirmasi' => '1',
                    'scanned_at' => now(),
                    'scanned_by' => $actorUid,
                    'scan_device_id' => $deviceId,
                    'updated_at' => now(),
                ]);

            if ($updated !== 1) {
                throw new GateTokenException('Tiket sudah pernah digunakan.', 409);
            }

            return $cart->fresh(['event', 'users', 'cashBuyer', 'hargaCarts']);
        }, 3);
    }

    private function findByToken(string $token, bool $lock = false): Cart
    {
        if (! $this->tokens->isValidFormat($token)) {
            throw new GateTokenException('Gate token tidak valid.', 404);
        }

        $query = $this->ticketQuery()
            ->where('gate_token_hash', $this->tokens->hash($token));

        return $this->firstCredentialMatch($query, $lock, 'Gate token tidak valid.');
    }

    private function findByManualCode(
        string $manualCode,
        string $eventUid,
        bool $lock = false
    ): Cart {
        if (! $this->tokens->isValidManualCode($manualCode)) {
            throw new GateTokenException('Kode manual tidak valid.', 404);
        }

        $query = $this->ticketQuery()
            ->where('event_uid', $eventUid)
            ->where('gate_manual_code_hash', $this->tokens->hashManualCode($manualCode));

        return $this->firstCredentialMatch($query, $lock, 'Kode manual tidak valid.');
    }

    private function ticketQuery()
    {
        return Cart::query()->with(['event', 'users', 'cashBuyer', 'hargaCarts']);
    }

    private function firstCredentialMatch($query, bool $lock, string $notFoundMessage): Cart
    {
        if ($lock) {
            $query->lockForUpdate();
        }

        $cart = $query->first();
        if (! $cart) {
            throw new GateTokenException($notFoundMessage, 404);
        }

        return $cart;
    }

    private function assertEventAccess(string $eventUid, string $ownerUid): void
    {
        if (! Event::query()
            ->where('uid', $eventUid)
            ->where('user_uid', $ownerUid)
            ->exists()) {
            throw new GateTokenException('Anda tidak memiliki akses ke event tiket ini.', 403);
        }
    }

    private function assertTicketAccess(Cart $cart, string $ownerUid): void
    {
        if (! $cart->event || $cart->event->user_uid !== $ownerUid) {
            throw new GateTokenException('Anda tidak memiliki akses ke event tiket ini.', 403);
        }

        if ($cart->status !== Cart::STATUS_SUCCESS) {
            throw new GateTokenException('Tiket belum lunas.', 422);
        }

        if ($cart->scanned_at !== null || (string) $cart->konfirmasi === '1') {
            throw new GateTokenException('Tiket sudah pernah digunakan.', 409);
        }

        if (! in_array((string) $cart->konfirmasi, ['', '0'], true)) {
            throw new GateTokenException('Status tiket tidak dapat dipindai.', 422);
        }
    }
}
