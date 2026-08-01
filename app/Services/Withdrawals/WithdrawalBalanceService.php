<?php

namespace App\Services\Withdrawals;

use App\Models\Cart;
use App\Models\HargaCart;
use App\Models\Penarikan;
use Illuminate\Support\Facades\DB;

class WithdrawalBalanceService
{
    public const LOCKING_STATUSES = [
        'PENDING',
        'PROCESSING',
        'SUCCESS',
    ];

    public const NON_LOCKING_STATUSES = [
        'REJECTED',
        'CANCELLED',
        'FAILED',
    ];

    public function availableBalanceFor(string $ownerUid): int
    {
        return max(0, $this->grossEarningsFor($ownerUid) - $this->deductedWithdrawalsFor($ownerUid));
    }

    public function grossEarningsFor(string $ownerUid): int
    {
        $carts = Cart::query()
            ->select([
                'carts.uid',
                'carts.gross_amount as cart_gross_amount',
                'transactions.gross_amount as transaction_gross_amount',
                'transactions.amount as transaction_amount',
            ])
            ->join('events', 'events.uid', '=', 'carts.event_uid')
            ->leftJoin('transactions', function ($join) {
                $join->on('transactions.invoice', '=', 'carts.invoice')
                    ->orOn('transactions.uid', '=', 'carts.uid');
            })
            ->where('carts.status', Cart::STATUS_SUCCESS)
            ->where('events.user_uid', $ownerUid)
            ->where(function ($query) {
                $query->whereNull('carts.payment_type')
                    ->orWhere('carts.payment_type', '!=', 'cash');
            })
            ->get()
            ->unique('uid');

        if ($carts->isEmpty()) {
            return 0;
        }

        $fallbackByCart = HargaCart::query()
            ->whereIn('uid', $carts->pluck('uid')->all())
            ->get()
            ->groupBy('uid')
            ->map(fn ($items) => $items->sum(function (HargaCart $item) {
                $lineTotal = (int) $item->quantity * (int) $item->harga_ticket;
                $discount = (int) ($item->disc ?? 0);

                return max(0, $lineTotal - $discount);
            }));

        return (int) $carts->sum(function ($cart) use ($fallbackByCart) {
            foreach ([
                $cart->cart_gross_amount,
                $cart->transaction_gross_amount,
                $cart->transaction_amount,
            ] as $amount) {
                $normalized = (int) $amount;
                if ($normalized > 0) {
                    return $normalized;
                }
            }

            return (int) ($fallbackByCart[$cart->uid] ?? 0);
        });
    }

    public function deductedWithdrawalsFor(?string $ownerUid = null, ?array $statuses = null): int
    {
        $normalizedStatuses = array_map('strtoupper', $statuses ?? self::LOCKING_STATUSES);

        return (int) Penarikan::query()
            ->when($ownerUid !== null, fn ($query) => $query->where('uid_user', $ownerUid))
            ->whereIn(DB::raw('UPPER(status)'), $normalizedStatuses)
            ->get()
            ->sum(fn (Penarikan $penarikan) => (int) $penarikan->amount);
    }
}
