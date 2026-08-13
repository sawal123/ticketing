<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\Event;
use App\Services\Reports\FinancialSnapshotService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class EventTransactionPdfController extends Controller
{
    public function __invoke(Request $request, string $uid)
    {
        $event = $this->authorizedEvent($uid);

        $filters = $this->sanitizedFilters($request);

        $transactions = $this->exportQuery($event->uid, $filters)->get();
        $exportTotals = $this->exportSnapshotTotalsFromRows($transactions);

        $filterInfo = 'Semua Data';
        if ($filters['payment'] !== 'all' || $filters['range'] !== null || $filters['search'] !== '') {
            $parts = [];
            if ($filters['payment'] !== 'all') {
                $parts[] = 'Metode: ' . strtoupper($filters['payment']);
            }
            if ($filters['range'] !== null) {
                $parts[] = 'Rentang: ' . $filters['range'];
            }
            if ($filters['search'] !== '') {
                $parts[] = "Cari: '" . $filters['search'] . "'";
            }
            $filterInfo = implode(', ', $parts);
        }

        $fileName = 'transaksi-event-' . Str::slug($event->event) . '-' . now()->format('YmdHis') . '.pdf';

        return Pdf::loadView('exports.transactions-print', [
            'event' => $event,
            'transactions' => $transactions,
            'filter_info' => $filterInfo,
            'exportTotals' => $exportTotals,
        ])->setPaper('a4', 'landscape')->stream($fileName);
    }

    private function authorizedEvent(string $uid): Event
    {
        $user = Auth::user();
        abort_unless($user !== null, 403);

        $isAdmin = $user->role === 'admin';
        $ownerId = ($user->role === 'staff') ? $user->parent_uid : $user->uid;

        $query = Event::query()->where('uid', $uid);

        if (! $isAdmin) {
            abort_unless(filled($ownerId), 403);
            $query->where('user_uid', $ownerId);
        }

        return $query->firstOrFail();
    }

    private function sanitizedFilters(Request $request): array
    {
        $payment = (string) $request->query('payment', 'all');
        if (! in_array($payment, ['all', 'cash', 'non-cash'], true)) {
            $payment = 'all';
        }

        $search = mb_substr(trim((string) $request->query('search', '')), 0, 100);

        $range = $request->query('range');
        $range = $range !== null ? (mb_substr(trim((string) $range), 0, 32) ?: null) : null;

        $dateRange = $range !== null ? $this->normalizedDateRange($range) : null;
        if ($range !== null && $dateRange === null) {
            $range = null;
        }

        return [
            'payment' => $payment,
            'search' => $search,
            'range' => $range,
            'dateRange' => $dateRange,
        ];
    }

    private function normalizedDateRange(?string $range): ?array
    {
        if ($range === null || $range === '') {
            return null;
        }

        $dates = explode(' to ', $range);

        if (count($dates) > 2) {
            return null;
        }

        try {
            $start = Carbon::createFromFormat('Y-m-d', trim($dates[0]))->startOfDay();
            $end = isset($dates[1])
                ? Carbon::createFromFormat('Y-m-d', trim($dates[1]))->endOfDay()
                : $start->copy()->endOfDay();
        } catch (\Throwable) {
            return null;
        }

        if ($start->format('Y-m-d') !== trim($dates[0])) {
            return null;
        }

        if (isset($dates[1]) && $end->format('Y-m-d') !== trim($dates[1])) {
            return null;
        }

        if ($end->lt($start) || $start->diffInDays($end) > 366) {
            return null;
        }

        return [$start, $end];
    }

    private function exportQuery(string $eventUid, array $filters)
    {
        $dateRange = $filters['dateRange'];
        $snapshots = app(FinancialSnapshotService::class);

        $query = DB::table('carts')
            ->join('users', 'users.uid', '=', 'carts.user_uid')
            ->leftJoin('cashes', 'cashes.uid', '=', 'carts.uid')
            ->join('harga_carts', 'harga_carts.uid', '=', 'carts.uid');

        $snapshots->joinLineSnapshots($query);

        $query
            ->select([
                'carts.created_at',
                'carts.uid as cart_uid',
                'carts.invoice',
                DB::raw("CASE WHEN carts.payment_type = 'cash' THEN COALESCE(cashes.name, 'Data Pembeli Tidak Ditemukan') ELSE users.name END as buyer_name"),
                DB::raw("CASE WHEN carts.payment_type = 'cash' THEN COALESCE(cashes.email, '-') ELSE users.email END as buyer_email"),
                'harga_carts.kategori_harga',
                'harga_carts.quantity',
                'harga_carts.harga_ticket',
                'harga_carts.disc',
                'carts.payment_type',
                'carts.konfirmasi',
                'carts.scanned_at',
                'carts.pajak',
                'carts.internet_fee',
                'carts.gross_amount',
                DB::raw($snapshots->taxSnapshotSqlExpression() . ' as tax_snapshot'),
            ])
            ->where('carts.event_uid', $eventUid)
            ->where('carts.status', Cart::STATUS_SUCCESS)
            ->whereNull('carts.deleted_at')
            ->whereNull('harga_carts.deleted_at');

        if ($filters['payment'] !== 'all') {
            if ($filters['payment'] === 'cash') {
                $query->where('carts.payment_type', 'cash');
            } else {
                $query->where('carts.payment_type', '!=', 'cash');
            }
        }

        if ($dateRange) {
            $query->whereBetween('carts.created_at', $dateRange);
        }

        if ($filters['search'] !== '') {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('carts.invoice', 'like', '%' . $search . '%')
                    ->orWhere(function ($online) use ($search) {
                        $online->where('carts.payment_type', '!=', 'cash')
                            ->where(function ($user) use ($search) {
                                $user->where('users.name', 'like', '%' . $search . '%')
                                    ->orWhere('users.email', 'like', '%' . $search . '%');
                            });
                    })
                    ->orWhere(function ($cash) use ($search) {
                        $cash->where('carts.payment_type', 'cash')
                            ->where(function ($cashBuyer) use ($search) {
                                $cashBuyer->where('cashes.name', 'like', '%' . $search . '%')
                                    ->orWhere('cashes.email', 'like', '%' . $search . '%');
                            });
                    });
            });
        }

        return $query->orderBy('carts.created_at', 'desc');
    }

    private function exportSnapshotTotalsFromRows($rows): array
    {
        $cartUids = $rows->pluck('cart_uid')->filter()->unique()->values();

        if ($cartUids->isEmpty()) {
            return [
                'ticket_total' => 0,
                'discount_total' => 0,
                'total_quantity' => 0,
                'tax_total' => 0,
                'owner_revenue' => 0,
            ];
        }

        $carts = Cart::with('hargaCarts')
            ->whereIn('uid', $cartUids)
            ->get();

        return app(FinancialSnapshotService::class)->collectionTotals($carts);
    }
}
