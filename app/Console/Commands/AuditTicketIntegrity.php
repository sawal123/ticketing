<?php

namespace App\Console\Commands;

use App\Models\Cart;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AuditTicketIntegrity extends Command
{
    protected $signature = 'tickets:audit-integrity {--limit=20 : Maximum sample rows per section}';

    protected $description = 'Report ticketing data integrity issues without modifying production data.';

    public function handle(): int
    {
        $limit = max(1, min(100, (int) $this->option('limit')));

        $this->duplicates('Duplicate cart UID', 'carts', 'uid', $limit);
        $this->duplicates('Duplicate cart invoice', 'carts', 'invoice', $limit);
        $this->duplicates('Duplicate transaction invoice', 'transactions', 'invoice', $limit);

        $this->report('Cart tanpa harga_carts', DB::table('carts')
            ->leftJoin('harga_carts', 'harga_carts.uid', '=', 'carts.uid')
            ->whereNull('harga_carts.id')
            ->select('carts.uid', 'carts.invoice', 'carts.status')
            ->limit($limit)
            ->get());

        $this->report('Harga_carts tanpa cart', DB::table('harga_carts')
            ->leftJoin('carts', 'carts.uid', '=', 'harga_carts.uid')
            ->whereNull('carts.id')
            ->select('harga_carts.id', 'harga_carts.uid', 'harga_carts.harga_id')
            ->limit($limit)
            ->get());

        $this->report('Harga_carts tanpa harga', DB::table('harga_carts')
            ->leftJoin('hargas', 'hargas.id', '=', 'harga_carts.harga_id')
            ->whereNull('hargas.id')
            ->select('harga_carts.id', 'harga_carts.uid', 'harga_carts.harga_id')
            ->limit($limit)
            ->get());

        $this->stockMismatch('sold_qty tidak cocok dengan transaksi SUCCESS', Cart::STATUS_SUCCESS, 'sold_qty', $limit);
        $this->stockMismatch('reserved_qty tidak cocok dengan cart aktif', Cart::ACTIVE_RESERVATION_STATUSES, 'reserved_qty', $limit);

        $this->report('Transaksi SUCCESS melebihi kuota', DB::table('hargas')
            ->whereColumn('sold_qty', '>', 'qty')
            ->select('id', 'uid', 'kategori', 'qty', 'sold_qty', 'reserved_qty')
            ->limit($limit)
            ->get());

        $this->report('Cart SUCCESS tanpa transaction SUCCESS', DB::table('carts')
            ->leftJoin('transactions', function ($join) {
                $join->on('transactions.invoice', '=', 'carts.invoice')
                    ->where('transactions.status_transaksi', Cart::STATUS_SUCCESS);
            })
            ->where('carts.status', Cart::STATUS_SUCCESS)
            ->whereNull('transactions.id')
            ->select('carts.uid', 'carts.invoice', 'carts.status')
            ->limit($limit)
            ->get());

        $this->report('Transaction SUCCESS tanpa cart SUCCESS', DB::table('transactions')
            ->leftJoin('carts', function ($join) {
                $join->on('carts.invoice', '=', 'transactions.invoice')
                    ->where('carts.status', Cart::STATUS_SUCCESS);
            })
            ->where('transactions.status_transaksi', Cart::STATUS_SUCCESS)
            ->whereNull('carts.id')
            ->select('transactions.uid', 'transactions.invoice', 'transactions.status_transaksi')
            ->limit($limit)
            ->get());

        return self::SUCCESS;
    }

    protected function duplicates(string $title, string $table, string $column, int $limit): void
    {
        $rows = DB::table($table)
            ->whereNotNull($column)
            ->where($column, '!=', '')
            ->groupBy($column)
            ->havingRaw('COUNT(*) > 1')
            ->select($column, DB::raw('COUNT(*) as duplicate_count'))
            ->limit($limit)
            ->get();

        $this->report($title, $rows);
    }

    protected function stockMismatch(string $title, $statuses, string $column, int $limit): void
    {
        $statuses = (array) $statuses;
        $aggregates = DB::table('harga_carts')
            ->join('carts', 'carts.uid', '=', 'harga_carts.uid')
            ->whereIn('carts.status', $statuses)
            ->whereNull('carts.deleted_at')
            ->whereNull('harga_carts.deleted_at')
            ->whereNotNull('harga_carts.harga_id')
            ->groupBy('harga_carts.harga_id')
            ->select('harga_carts.harga_id', DB::raw('SUM(CAST(harga_carts.quantity AS UNSIGNED)) as expected'));

        $rows = DB::table('hargas')
            ->leftJoinSub($aggregates, 'agg', 'agg.harga_id', '=', 'hargas.id')
            ->whereRaw("COALESCE(agg.expected, 0) != hargas.{$column}")
            ->select('hargas.id', 'hargas.kategori', 'hargas.qty', "hargas.{$column}", DB::raw('COALESCE(agg.expected, 0) as expected'))
            ->limit($limit)
            ->get();

        $this->report($title, $rows);
    }

    protected function report(string $title, $rows): void
    {
        $count = $rows->count();
        $this->line('');
        $this->info($title.": {$count} sample(s)");

        if ($count > 0) {
            $this->table(array_keys((array) $rows->first()), $rows->map(fn ($row) => (array) $row)->all());
        }
    }
}
