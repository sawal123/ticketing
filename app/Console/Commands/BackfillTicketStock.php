<?php

namespace App\Console\Commands;

use App\Models\Cart;
use App\Models\Harga;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BackfillTicketStock extends Command
{
    protected $signature = 'tickets:backfill-stock {--dry-run : Report changes without writing sold_qty} {--batch=100 : Number of hargas per batch}';

    protected $description = 'Backfill hargas.sold_qty from SUCCESS carts safely and idempotently.';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $batch = max(1, min(500, (int) $this->option('batch')));
        $updated = 0;
        $checked = 0;

        Harga::orderBy('id')->chunkById($batch, function ($hargas) use ($dryRun, &$updated, &$checked) {
            $ids = $hargas->pluck('id');
            $soldByHarga = DB::table('harga_carts')
                ->join('carts', 'carts.uid', '=', 'harga_carts.uid')
                ->whereIn('harga_carts.harga_id', $ids)
                ->where('carts.status', Cart::STATUS_SUCCESS)
                ->whereNull('carts.deleted_at')
                ->whereNull('harga_carts.deleted_at')
                ->groupBy('harga_carts.harga_id')
                ->select('harga_carts.harga_id', DB::raw('SUM(CAST(harga_carts.quantity AS UNSIGNED)) as total'))
                ->pluck('total', 'harga_id');

            foreach ($hargas as $harga) {
                $checked++;
                $expected = (int) ($soldByHarga[$harga->id] ?? 0);

                if ((int) $harga->sold_qty === $expected) {
                    continue;
                }

                $updated++;
                $this->line(sprintf(
                    '%s harga_id=%d kategori=%s sold_qty %d -> %d',
                    $dryRun ? '[dry-run]' : '[update]',
                    $harga->id,
                    $harga->kategori,
                    (int) $harga->sold_qty,
                    $expected
                ));

                if (! $dryRun) {
                    DB::transaction(function () use ($harga, $expected) {
                        $locked = Harga::whereKey($harga->id)->lockForUpdate()->first();
                        $locked->sold_qty = $expected;
                        $locked->save();
                    }, 3);
                }
            }
        });

        $this->info("Checked {$checked} ticket categories. ".($dryRun ? 'Would update' : 'Updated')." {$updated} rows.");

        return self::SUCCESS;
    }
}
