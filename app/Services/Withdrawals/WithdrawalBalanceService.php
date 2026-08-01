<?php

namespace App\Services\Withdrawals;

use App\Models\Cart;
use App\Models\Penarikan;
use App\Services\Reports\FinancialSnapshotService;
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
            ->with('hargaCarts')
            ->select('carts.*')
            ->join('events', 'events.uid', '=', 'carts.event_uid')
            ->where('carts.status', Cart::STATUS_SUCCESS)
            ->where('events.user_uid', $ownerUid)
            ->where(function ($query) {
                $query->whereNull('carts.payment_type')
                    ->orWhere('carts.payment_type', '!=', 'cash');
            })
            ->get()
            ->unique('uid');

        return (int) app(FinancialSnapshotService::class)
            ->collectionTotals($carts)['owner_revenue'];
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
