<?php

namespace App\Console\Commands;

use App\Models\Cart;
use App\Models\CartVoucher;
use App\Models\HargaCart;
use App\Models\Transaction;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixTaxSplit extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fix:tax-split {--date=2026-03-27 : Tanggal mulai perbaikan (YYYY-MM-DD)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Memisahkan Pajak (10%) dan Internet Fee yang tergabung di kolom internet_fee sejak tanggal tertentu.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $startDate = $this->option('date');

        $this->info('Mencari transaksi sukses sejak '.$startDate.'...');

        $carts = Cart::where('created_at', '>=', $startDate.' 00:00:00')
            ->where('status', 'SUCCESS')
            ->get();

        if ($carts->isEmpty()) {
            $this->warn('Tidak ada transaksi ditemukan.');

            return;
        }

        $this->info('Ditemukan '.$carts->count().' transaksi. Memulai proses perbaikan...');

        $bar = $this->output->createProgressBar($carts->count());
        $bar->start();

        $count = 0;
        foreach ($carts as $cart) {
            // 1. Hitung Subtotal
            $subtotal = HargaCart::where('uid', $cart->uid)->sum(DB::raw('quantity * harga_ticket'));

            if ($subtotal <= 0) {
                $bar->advance();

                continue;
            }

            // 2. Hitung Pajak (10%)
            $newPajak = $subtotal * 0.1;

            // 3. Rekonstruksi Total Fee dari tabel transactions
            $trx = Transaction::where('uid', $cart->uid)
                ->orWhere('invoice', $cart->invoice)
                ->first();

            $totalRecordedFee = 0;

            if ($trx) {
                // Gross Amount - Subtotal = Total Fee (Pajak + Internet Fee)
                $grossAmount = (int) $trx->amount;

                // Kurangi diskon voucher jika ada
                $diskon = 0;
                $cartV = CartVoucher::where('uid', $cart->uid)->first();
                if ($cartV) {
                    $diskon = $cartV->nominal; // Asumsikan nominal sudah dihitung
                }

                $totalRecordedFee = $grossAmount - ($subtotal - $diskon);
            } else {
                // Fallback jika tidak ada di tabel transaction (misal: Cash)
                if (strtolower($cart->payment_type) !== 'cash') {
                    // Jika non-cash tapi tidak ada trx, asumsikan fee default (pajak + 7200)
                    $totalRecordedFee = $newPajak + 7200;
                } else {
                    // Cash biasanya tidak ada internet fee, hanya pajak
                    $totalRecordedFee = $newPajak;
                }
            }

            // 4. Hitung Internet Fee Asli
            $newInternetFee = $totalRecordedFee - $newPajak;

            // Pastikan tidak negatif akibat presisi / data legacy
            if ($newInternetFee < 0) {
                $newInternetFee = 0;
            }

            // 5. Update data
            $cart->update([
                'pajak' => $newPajak,
                'pajak_persen' => 10,
                'internet_fee' => $newInternetFee,
            ]);

            $count++;
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
        $this->info('Berhasil memperbaiki '.$count.' data transaksi!');
    }
}
