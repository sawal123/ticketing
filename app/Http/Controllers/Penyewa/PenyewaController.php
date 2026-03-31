<?php

namespace App\Http\Controllers\Penyewa;

use App\Models\Cart;
use App\Models\User;
use App\Models\Event;
use App\Models\Harga;
use App\Models\Talent;
use App\Models\Partner;
use App\Models\Voucher;
use App\Models\HargaCart;
use App\Models\Penarikan;
use App\Models\CartVoucher;
use App\Models\Transaction;
use Illuminate\Http\Request;
// use Illuminate\Support\Facades\DB;
use App\Models\BankIndonesia;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;

class PenyewaController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $ownerId = ($user->role === 'staff') ? $user->parent_uid : $user->uid;

        // ==========================================================
        // RUMUS DASAR PERHITUNGAN (Wajib ada di sini juga)
        // ==========================================================
        $rumusDasar = "
            (
                (harga_carts.quantity * harga_carts.harga_ticket) - 
                COALESCE(
                    CASE 
                        WHEN vouchers.unit = '%' OR vouchers.unit = 'persen' 
                        THEN (harga_carts.quantity * harga_carts.harga_ticket) * (vouchers.nominal / 100)
                        ELSE vouchers.nominal 
                    END, 
                0)
            ) 
            * (1 + (COALESCE(events.fee, 0) / 100))
        ";

        // ==========================================================
        // 1 & 2. TOTAL OMSET & TIKET TERJUAL (Tanpa Filter Payment Type)
        // ==========================================================
        $omsetDanTiket = HargaCart::join('carts', 'carts.uid', '=', 'harga_carts.uid')
            ->join('events', 'events.uid', '=', 'carts.event_uid')
            ->leftJoin('vouchers', 'vouchers.code', '=', 'harga_carts.voucher')
            ->where('carts.status', 'SUCCESS')
            ->where('events.user_uid', $ownerId)
            ->select(
                DB::raw("SUM($rumusDasar) as total_amount"),
                DB::raw('SUM(harga_carts.quantity) as total_quantity')
            )->first();

        $totalAmount = $omsetDanTiket->total_amount ?? 0;
        $totalTiket = $omsetDanTiket->total_quantity ?? 0;

        // ==========================================================
        // 3. TOTAL TRANSAKSI (Menghitung jumlah Invoice SUCCESS)
        // ==========================================================
        $totalTransaksi = Cart::join('events', 'events.uid', '=', 'carts.event_uid')
            ->where('carts.status', 'SUCCESS')
            ->where('events.user_uid', $ownerId)
            ->count();

        // 4. TOTAL EVENT
        $eventCount = Event::where('user_uid', $ownerId)->count();

        // ==========================================================
        // 5. DATA UNTUK GRAFIK (Digabung 1 Kueri agar super cepat)
        // ==========================================================
        $dailyData = HargaCart::join('carts', 'carts.uid', '=', 'harga_carts.uid')
            ->join('events', 'events.uid', '=', 'carts.event_uid')
            ->leftJoin('vouchers', 'vouchers.code', '=', 'harga_carts.voucher')
            ->where('carts.status', 'SUCCESS')
            ->where('events.user_uid', $ownerId)
            ->select(
                DB::raw('DATE(carts.created_at) as date'),
                'carts.payment_type',
                DB::raw('SUM(harga_carts.quantity) as total_qty'),
                DB::raw("SUM($rumusDasar) as total_amount")
            )
            ->groupBy(DB::raw('DATE(carts.created_at)'), 'carts.payment_type')
            ->get();

        $dates = collect($dailyData->pluck('date'))->unique()->sort()->values()->toArray();

        $qtyOnline = [];
        $qtyCash = [];
        $amountOnline = [];
        $amountCash = [];
        $lastDate = null;

        foreach ($dates as $date) {
            $lastDate = $date;
            $qtyOnline[] = (int) $dailyData->where('date', $date)->where('payment_type', '!=', 'cash')->sum('total_qty');
            $qtyCash[] = (int) $dailyData->where('date', $date)->where('payment_type', 'cash')->sum('total_qty');
            $amountOnline[] = (float) $dailyData->where('date', $date)->where('payment_type', '!=', 'cash')->sum('total_amount');
            $amountCash[] = (float) $dailyData->where('date', $date)->where('payment_type', 'cash')->sum('total_amount');
        }

        // ==========================================================
        // 6. SETUP DROPDOWN UNTUK MODAL CASH 
        // ==========================================================
        $e = Event::join('hargas', 'hargas.uid', '=', 'events.uid')
            ->select('events.event', 'events.fee', 'hargas.kategori', 'hargas.harga')
            ->where('events.user_uid', $ownerId)
            ->where('events.konfirmasi', '1')
            ->get();

        $transformedEvents = [];
        foreach ($e as $item) {
            $existingEventIndex = array_search($item->event, array_column($transformedEvents, 'event'));
            if ($existingEventIndex !== false) {
                $transformedEvents[$existingEventIndex]['kategori'][] = $item->kategori;
                $transformedEvents[$existingEventIndex]['harga'][] = $item->harga;
            } else {
                $transformedEvents[] = [
                    'event' => $item->event,
                    'eventFee' => $item->fee,
                    'kategori' => [$item->kategori],
                    'harga' => [$item->harga],
                ];
            }
        }

        $ticketOptions = [];
        $hargaOption = [];
        foreach ($transformedEvents as $key => $val) {
            $ticketOptions[$key + 1] = $val['kategori'];
            $hargaOption[$key + 1] = $val['harga'];
        }

        // ==========================================================
        // 7. DATA DEMOGRAFI (GENDER & UMUR)
        // ==========================================================
        $genderCounts = Cart::join('users', 'users.uid', '=', 'carts.user_uid')
            ->join('events', 'events.uid', '=', 'carts.event_uid')
            ->select('users.gender', DB::raw('COUNT(*) as count'))
            ->where('events.user_uid', $ownerId)
            ->where('carts.status', 'SUCCESS')
            ->groupBy('users.gender')
            ->pluck('count', 'users.gender')
            ->toArray();

        $pria = $genderCounts['pria'] ?? 0;
        $wanita = $genderCounts['wanita'] ?? 0;
        $totalUsers = $wanita + $pria;
        $persenWanita = $totalUsers > 0 ? ($wanita / $totalUsers) * 100 : 0;
        $persenPria = $totalUsers > 0 ? ($pria / $totalUsers) * 100 : 0;

        $birtday = Cart::join('users', 'users.uid', '=', 'carts.user_uid')
            ->join('events', 'events.uid', '=', 'carts.event_uid')
            ->select(
                DB::raw("CASE
                    WHEN TIMESTAMPDIFF(YEAR, users.birthday, CURDATE()) < 18 THEN '18 thn ke bawah'
                    WHEN TIMESTAMPDIFF(YEAR, users.birthday, CURDATE()) BETWEEN 18 AND 25 THEN '18 s/d 25 tahun'
                    ELSE '25 tahun ke atas'
                END AS age_group"),
                'users.gender',
                DB::raw('COUNT(*) as count')
            )
            ->where('events.user_uid', $ownerId)
            ->where('carts.status', 'SUCCESS')
            ->groupBy('age_group', 'users.gender')
            ->get()->groupBy('age_group')->toArray();

        $partner = Partner::where('referensi', $ownerId)->where('status', 'active')->get();

        return view('penyewa.page.dashboard', [
            'title' => 'Dashboard',
            'totalAmount' => $totalAmount,
            'totalTiket' => $totalTiket,
            'totalTransaksi' => $totalTransaksi,
            'eventCount' => $eventCount,
            'gr' => $lastDate, // Perbaikan variabel
            'chartDates' => $dates,
            'qtyOnline' => $qtyOnline,
            'qtyCash' => $qtyCash,
            'amountOnline' => $amountOnline,
            'amountCash' => $amountCash,
            'event' => $transformedEvents,
            'ticketEvent' => $ticketOptions,
            'hargaTicket' => $hargaOption,
            'dataUser' => [$wanita, $pria, $persenWanita, $persenPria],
            'birtday' => $birtday,
            'partner' => $partner
        ]);
    }

    public function login()
    {
        return view('penyewa.auth.login', [
            'title' => 'Login',
        ]);
    }

    public function event($addEvent = null, $uid = null)
    {
        $user = Auth::user();
        $ownerId = ($user->role === 'staff') ? $user->parent_uid : $user->uid;
        error_reporting(0);
        $event = Event::where('user_uid', $ownerId)->get();
        if ($addEvent === null) {
            $pagination = Event::where('user_uid', $ownerId)->paginate(12);
            return view('penyewa.page.event', [
                'title' => 'Event',
                'event' => $event,
                'paginate' => $pagination
            ]);
        } elseif ($addEvent === 'addEvent') {
            return view('penyewa.eventSemi.addEvent', [
                'title' => 'Add Event',
            ]);
        } elseif ($addEvent === 'eventDetail') {
            $eventDetail = Event::where('uid', $uid)->where('user_uid', $ownerId)->first();

            if ($eventDetail === null) {
                abort('403');
            }

            $talent = Talent::where('uid', $uid)->get();
            $harga = Harga::where('uid', $uid)->get();
            $cart = Cart::where('event_uid', $eventDetail->uid)->where('status', '=', 'SUCCESS')->get();

            // =====================================================================
            // MENGHITUNG TOTAL TERJUAL PER TIKET (SANGAT CEPAT)
            // =====================================================================
            // Kita hitung jumlah quantity berdasarkan harga_id, lalu jadikan Array
            $terjualPerHarga = HargaCart::join('carts', 'carts.uid', '=', 'harga_carts.uid')
                ->where('carts.event_uid', $eventDetail->uid)
                ->where('carts.status', 'SUCCESS')
                ->select('harga_carts.harga_id', DB::raw('SUM(harga_carts.quantity) as total_terjual'))
                ->groupBy('harga_carts.harga_id')
                ->pluck('total_terjual', 'harga_id');
            // Hasilnya akan seperti: [ 1 => 50, 2 => 15 ] (ID 1 laku 50, ID 2 laku 15)
            if ($eventDetail === null) {
                abort('403');
            }
            // dd($cart);
            return view('penyewa.eventSemi.eventDetail', [
                // 'hargaC' => $hargaC,
                'title' => 'Event Detail',
                'eventDetail' => $eventDetail,
                'talent' => $talent,
                'harga' => $harga,
                'cart' => $cart,
                'terjualPerHarga' => $terjualPerHarga
            ]);
        }
    }


    public function ubahEvents($uid)
    {
        $user = Auth::user();
        $ownerId = ($user->role === 'staff') ? $user->parent_uid : $user->uid;
        $ubahEvent = Event::join('event_dates', 'events.uid', 'event_dates.uid')->where('events.uid', $uid)->first();

        // dd($ubahEvent);
        return view('penyewa.eventSemi.addEvent', [
            'title' => 'Ubah Event',
            'ubahEvent' => $ubahEvent
        ]);
    }

    public function transaksi(Request $request)
    {
        $user = Auth::user();
        $ownerId = ($user->role === 'staff') ? $user->parent_uid : $user->uid;
        $filter = $request->filter;
        $event = null;

        // ==========================================================
        // RUMUS PERHITUNGAN SQL (Bisa Dipakai Berulang)
        // Formula: ((Harga * Qty) - Diskon Voucher) + Fee Event (Pajak)
        // ==========================================================
        $rumusDasar = "
        (
            (harga_carts.quantity * harga_carts.harga_ticket) - 
            COALESCE(
                CASE 
                    WHEN vouchers.unit = '%' OR vouchers.unit = 'persen' 
                    THEN (harga_carts.quantity * harga_carts.harga_ticket) * (vouchers.nominal / 100)
                    ELSE vouchers.nominal 
                END, 
            0)
        ) 
        * (1 + (COALESCE(events.fee, 0) / 100))
    ";

        // ==========================================================
        // 1. KUERI TABEL TRANSAKSI (TANPA TABEL TRANSACTIONS)
        // ==========================================================
        $cartQuery = Cart::select(
            'carts.uid',
            'carts.invoice',
            'carts.status',
            'carts.payment_type',
            'carts.created_at',
            'events.event',
            'users.name as user_name',
            DB::raw("SUM($rumusDasar) as final_amount"), // <--- Tambahkan SUM manual di sini
            DB::raw('SUM(harga_carts.quantity) as total_quantity'),
            DB::raw('SUM(harga_carts.quantity * harga_carts.harga_ticket) as subtotal_harga')
        )
            ->join('harga_carts', 'harga_carts.uid', '=', 'carts.uid')
            ->join('events', 'events.uid', '=', 'carts.event_uid')
            ->join('users', 'users.uid', '=', 'carts.user_uid')
            ->leftJoin('vouchers', 'vouchers.code', '=', 'harga_carts.voucher')
            ->where('carts.status', 'SUCCESS')
            ->where('carts.payment_type', '!=', 'cash')
            ->where('events.user_uid', $ownerId)
            ->groupBy(
                'carts.uid',
                'carts.invoice',
                'carts.status',
                'carts.payment_type',
                'carts.created_at',
                'events.event',
                'users.name'
            )
            ->orderBy('carts.created_at', 'desc');

        // ==========================================================
        // 2. KUERI KARTU STATISTIK (DIPERBARUI)
        // ==========================================================
        // Omset sekarang dihitung langsung dari harga tiket, bukan transactions
        $omsetQuery = HargaCart::join('carts', 'carts.uid', '=', 'harga_carts.uid')
            ->join('events', 'events.uid', '=', 'carts.event_uid')
            ->leftJoin('vouchers', 'vouchers.code', '=', 'harga_carts.voucher')
            ->where('carts.status', 'SUCCESS')
            ->where('carts.payment_type', '!=', 'cash')
            ->where('events.user_uid', $ownerId);

        $tiketQuery = HargaCart::join('carts', 'carts.uid', '=', 'harga_carts.uid')
            ->join('events', 'events.uid', '=', 'carts.event_uid')
            ->where('carts.status', 'SUCCESS')
            ->where('carts.payment_type', '!=', 'cash')
            ->where('events.user_uid', $ownerId);

        // ==========================================================
        // 3. TERAPKAN FILTER
        // ==========================================================
        if ($request->uid) {
            $event = Event::where('uid', $request->uid)->first();
            $cartQuery->where('events.uid', $request->uid);
            $omsetQuery->where('events.uid', $request->uid);
            $tiketQuery->where('events.uid', $request->uid);
        }

        if (!empty($filter)) {
            $cartQuery->whereDate('carts.created_at', $filter);
            $omsetQuery->whereDate('carts.created_at', $filter);
            $tiketQuery->whereDate('carts.created_at', $filter);
        }

        // ==========================================================
        // 4. EKSEKUSI DATA
        // ==========================================================
        $cart = $cartQuery->get();

        // Biarkan Laravel yang membungkus rumusnya dengan SUM()
        $totalOmsetOnline = $omsetQuery->sum(DB::raw($rumusDasar));
        $totalTiketOnline = $tiketQuery->sum('harga_carts.quantity');

        // Ambil detail tiket untuk modal
        $cartUids = $cart->pluck('uid');
        $qtyTiket = HargaCart::whereIn('uid', $cartUids)->get();

        return view('penyewa.page.transaksi', [
            'title' => 'Transaksi',
            'event' => $event,
            'cart' => $cart,
            'qtyTiket' => $qtyTiket,
            'totalPenjualan' => $totalOmsetOnline,
            'totalFee' => $totalTiketOnline,
            'filter' => $filter,
            'uid' => $request->uid
        ]);
    }

    public function cash(Request $request)
    {
        $user = Auth::user();
        $ownerId = ($user->role === 'staff') ? $user->parent_uid : $user->uid;
        $filter = $request->filter;

        $use = User::all();
        $event = null;

        // ==========================================================
        // RUMUS DASAR PERHITUNGAN
        // ==========================================================
        $rumusDasar = "
            (
                (harga_carts.quantity * harga_carts.harga_ticket) - 
                COALESCE(
                    CASE 
                        WHEN vouchers.unit = '%' OR vouchers.unit = 'persen' 
                        THEN (harga_carts.quantity * harga_carts.harga_ticket) * (vouchers.nominal / 100)
                        ELSE vouchers.nominal 
                    END, 
                0)
            ) 
            * (1 + (COALESCE(events.fee, 0) / 100))
        ";

        // ==========================================================
        // 1. KUERI UNTUK TABEL DATA BAWAH
        // ==========================================================
        $cartQuery = Cart::select(
            'carts.uid',
            'carts.user_uid',
            'carts.invoice',
            'cashes.name',
            'cashes.email',
            'carts.status',
            'carts.payment_type',
            'events.event',
            'carts.created_at',
            DB::raw("SUM($rumusDasar) as total_harga"), // <--- Rumus Sakti
            DB::raw('SUM(harga_carts.quantity) as total_quantity')
        )
            ->join('harga_carts', 'harga_carts.uid', '=', 'carts.uid')
            ->join('events', 'events.uid', '=', 'carts.event_uid')
            ->leftJoin('cashes', 'cashes.uid', '=', 'carts.uid')
            ->leftJoin('vouchers', 'vouchers.code', '=', 'harga_carts.voucher')
            ->where('carts.status', 'SUCCESS')
            ->where('carts.payment_type', 'cash')
            ->where('events.user_uid', $ownerId)
            ->groupBy(
                'carts.uid',
                'carts.user_uid',
                'carts.invoice',
                'cashes.name',
                'cashes.email',
                'carts.status',
                'carts.payment_type',
                'events.event',
                'carts.created_at'
                // Dihapus: 'transactions.amount' dari groupBy
            )
            ->orderBy('carts.created_at', 'desc');

        // ==========================================================
        // 2. KUERI UNTUK KARTU TOTAL OMSET & TIKET
        // ==========================================================
        $omsetQuery = HargaCart::join('carts', 'carts.uid', '=', 'harga_carts.uid')
            ->join('events', 'events.uid', '=', 'carts.event_uid')
            ->leftJoin('vouchers', 'vouchers.code', '=', 'harga_carts.voucher')
            ->where('carts.status', 'SUCCESS')
            ->where('carts.payment_type', 'cash')
            ->where('events.user_uid', $ownerId);

        $tiketQuery = HargaCart::join('carts', 'carts.uid', '=', 'harga_carts.uid')
            ->join('events', 'events.uid', '=', 'carts.event_uid')
            ->where('carts.status', 'SUCCESS')
            ->where('carts.payment_type', 'cash')
            ->where('events.user_uid', $ownerId);

        // ==========================================================
        // 3. TERAPKAN FILTER PENCARIAN
        // ==========================================================
        if ($request->uid) {
            $event = Event::where('uid', $request->uid)->first();
            $cartQuery->where('events.uid', $request->uid);
            $omsetQuery->where('events.uid', $request->uid);
            $tiketQuery->where('events.uid', $request->uid);
        }

        if (!empty($filter)) {
            $cartQuery->whereDate('carts.created_at', $filter);
            $omsetQuery->whereDate('carts.created_at', $filter);
            $tiketQuery->whereDate('carts.created_at', $filter);
        }

        // ==========================================================
        // 4. EKSEKUSI DATA
        // ==========================================================
        $cart = $cartQuery->get();

        // Jumlahkan dengan menggunakan DB::raw
        $totalOmsetCash = $omsetQuery->sum(DB::raw($rumusDasar));
        $totalTiketCash = $tiketQuery->sum('harga_carts.quantity');

        $cartUids = $cart->pluck('uid');
        $qtyTiket = HargaCart::whereIn('uid', $cartUids)->get();

        return view('penyewa.page.cash', [
            'title' => 'Cash',
            'event' => $event,
            'cart' => $cart,
            'use' => $use,
            'totalHargaCart' => $totalOmsetCash,
            'qtyTiket' => $qtyTiket,
            'sellTiket' => $totalTiketCash,
            'filter' => $filter,
            'uid' => $request->uid
        ]);
    }



    public function voucher()
    {
        $user = Auth::user();
        $ownerId = ($user->role === 'staff') ? $user->parent_uid : $user->uid;
        $voucher = Voucher::where('vouchers.user_uid', $ownerId)
            ->get();
        $event = Event::where('user_uid', $ownerId)->get();
        // dd($event);
        return view('penyewa.page.voucher', [
            'title' => 'Voucher',
            'voucher' => $voucher,
            'event' => $event

        ]);
    }

    public function money()
    {
        $user = Auth::user();
        $ownerId = ($user->role === 'staff') ? $user->parent_uid : $user->uid;

        // 1. Hitung Total HC (Uang dari tiket non-cash) - Logic kamu sudah benar karena ada diskon
        $TotalHC = Cart::select([
            'harga_carts.harga_ticket',
            'harga_carts.quantity',
            'harga_carts.disc',
            'vouchers.unit'
        ])
            ->join('harga_carts', 'harga_carts.uid', '=', 'carts.uid')
            ->leftJoin('vouchers', 'vouchers.code', '=', 'harga_carts.voucher')
            ->join('events', 'events.uid', '=', 'carts.event_uid')
            ->where('carts.status', 'SUCCESS')
            ->where('events.user_uid', $ownerId)
            ->where('carts.payment_type', '!=', 'cash')
            ->get();

        $totalCart = 0;
        foreach ($TotalHC as $item) {
            $hargaTicket = $item->harga_ticket * $item->quantity;
            if ($item->unit === 'rupiah') {
                $hargaTicket -= $item->disc;
            } elseif ($item->unit === 'persen') {
                $hargaTicket -= ($hargaTicket * $item->disc / 100);
            }
            $totalCart += $hargaTicket;
        }

        // 2. Hitung Total Cash (LEBIH CEPAT TANPA FOREACH)
        $ars = Cart::join('harga_carts', 'harga_carts.uid', '=', 'carts.uid')
            ->join('events', 'events.uid', '=', 'carts.event_uid')
            ->where('carts.payment_type', '=', 'cash')
            ->where('carts.status', 'SUCCESS')
            ->where('events.user_uid', $ownerId)
            ->sum(\DB::raw('harga_carts.harga_ticket * harga_carts.quantity'));

        // 3. Ambil data Tabel Penarikan
        $penarikan = Penarikan::where('uid_user', $ownerId)->latest()->get();

        // 4. Hitung Total Pending (BUG DIPERBAIKI & LEBIH CEPAT TANPA FOREACH)
        $arss = Penarikan::where('uid_user', $ownerId)
            ->where('status', 'PENDING')
            ->sum('amount');

        // 5. Hitung Total Sukses/Paid (LEBIH CEPAT TANPA FOREACH)
        $sc = Penarikan::where('uid_user', $ownerId)
            ->where('status', 'SUCCESS')
            ->sum('amount');

        return view(
            'penyewa.page.money',
            [
                'title' => 'Money',
                'money' => $penarikan,
                'totalMoney' => $totalCart,
                'cash' => $ars,
                'pending' => $arss,
                'paid' => $sc
            ]
        );
    }
    public function partner()
    {
        $user = Auth::user();
        $ownerId = ($user->role === 'staff') ? $user->parent_uid : $user->uid;
        error_reporting(0);
        $http = Http::get('https://www.emsifa.com/api-wilayah-indonesia/api/provinces.json');
        if ($http->successful()) {
            $provinsi = $http->json();
        } else {
            $provinsi = ['null', 'data'];
        }


        $partner = Partner::where('referensi', $ownerId)->get();
        // dd($provinsi[]);
        return view(
            'penyewa.page.partner',
            [
                'title' => 'Partner',
                'partner' => $partner,
                'provinsi' => $provinsi,
                'prop' => $provinsi,
            ]
        );
    }

    public function profile()
    {
        $user = Auth::user();
        $ownerId = ($user->role === 'staff') ? $user->parent_uid : $user->uid;

        // 1. KUERI LEBIH AMAN: Gunakan alias agar nama kolom database kamu yang asli 
        // ('bank', 'norek', 'nama') bisa dibaca oleh file Blade yang baru.
        $data = User::select(
            'users.*',
            'banks.bank as nama_bank',
            'banks.norek as no_rek',
            'banks.nama as nama_pemilik'
        )
            ->leftJoin('banks', 'banks.uid', '=', 'users.uid')
            ->where('users.uid', $ownerId)
            ->first();

        // 2. FETCH API AMAN: Siapkan array kosong sebagai default jika API mati
        $provinsi = [];
        try {
            // Tambahkan timeout agar loading website tidak muter-muter lama jika API down
            $http = Http::timeout(3)->get('https://www.emsifa.com/api-wilayah-indonesia/api/provinces.json');
            if ($http->successful()) {
                $provinsi = $http->json();
            }
        } catch (\Exception $e) {
            // Jika error/timeout, $provinsi tetap berupa array kosong sehingga Blade tidak crash
        }

        $bi = BankIndonesia::all();

        return view('penyewa.page.profile', [
            'title' => 'Profile',
            'profile' => $data,
            'bi' => $bi,
            'pr' => $provinsi
        ]);
    }
}
