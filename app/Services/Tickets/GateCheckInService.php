<?php

namespace App\Services\Tickets;

use App\Exceptions\GateTokenException;
use App\Models\Cart;
use Illuminate\Support\Facades\DB;

class GateCheckInService
{
    public function __construct(private GateTokenService $tokens) {}

    public function inspect(string $token, string $ownerUid): Cart
    {
        $cart = $this->findByToken($token);
        $this->assertGateAccess($cart, $ownerUid);

        return $cart;
    }

    public function checkIn(
        string $token,
        string $ownerUid,
        string $actorUid,
        ?string $deviceId = null
    ): Cart {
        return DB::transaction(function () use ($token, $ownerUid, $actorUid, $deviceId) {
            $cart = $this->findByToken($token, true);
            $this->assertGateAccess($cart, $ownerUid);

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

        $query = Cart::query()
            ->with(['event', 'users', 'cashBuyer', 'hargaCarts'])
            ->where('gate_token_hash', $this->tokens->hash($token));

        if ($lock) {
            $query->lockForUpdate();
        }

        $cart = $query->first();
        if (! $cart) {
            throw new GateTokenException('Gate token tidak valid.', 404);
        }

        return $cart;
    }

    private function assertGateAccess(Cart $cart, string $ownerUid): void
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
