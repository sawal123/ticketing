<?php

use App\Models\Cart;
use App\Models\HargaCart;
use Illuminate\Support\Facades\DB;

// Transaksi mulai dari 27 Maret 2026
$carts = Cart::where('created_at', '>=', '2026-03-27 00:00:00')
    ->where('status', 'SUCCESS')
    ->get();

echo 'Menganalisis '.$carts->count()." transaksi sejak 27 Maret 2026...\n";

$count = 0;
foreach ($carts as $cart) {
    // 1. Hitung Subtotal
    $subtotal = HargaCart::where('uid', $cart->uid)->sum(DB::raw('quantity * harga_ticket'));

    if ($subtotal <= 0) {
        continue;
    }

    // 2. Hitung Pajak (10%)
    $newPajak = $subtotal * 0.1;

    // 3. Hitung Internet Fee (Sisa dari total fee yang tersimpan)
    // Saat ini kemungkinan besar: current_pajak = 0, current_internet_fee = (Pajak + Fee Asli)
    $totalRecordedFee = $cart->internet_fee + $cart->pajak;
    $newInternetFee = $totalRecordedFee - $newPajak;

    // Pastikan tidak negatif (antisipasi data aneh)
    if ($newInternetFee < 0) {
        // Jika sisa negatif, mungkin ini data lama yang belum terpengaruh logic gabung
        // Lewati saja atau beri peringatan
        echo 'Skip Invoice '.$cart->invoice.' (Fee sisa negatif: '.$newInternetFee.")\n";

        continue;
    }

    // 4. Update data
    $cart->update([
        'pajak' => $newPajak,
        'pajak_persen' => 10,
        'internet_fee' => $newInternetFee,
    ]);

    $count++;
    echo 'Fixed ['.$cart->invoice.']: Subtotal '.number_format($subtotal).' | Pajak 10%: '.number_format($newPajak).' | Fee: '.number_format($newInternetFee)."\n";
}

echo "\nSelesai! Berhasil memperbaiki ".$count." data transaksi.\n";

// php artisan tinker resources/legacy
// php artisan tinker resources/fixnominaltax
// php artisan tinker "resources/fix pre2026"

// php artisan migrate
// php artisan fix:tax-split
