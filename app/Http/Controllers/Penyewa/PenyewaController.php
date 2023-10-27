<?php

namespace App\Http\Controllers\Penyewa;

use App\Models\Cart;
use App\Models\User;
use App\Models\Event;
use App\Models\Harga;
use App\Models\Talent;
use App\Models\Voucher;
use App\Models\HargaCart;
use App\Models\Penarikan;
use App\Models\CartVoucher;
use App\Models\Transaction;
use Illuminate\Http\Request;
use App\Models\BankIndonesia;
// use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;

class PenyewaController extends Controller
{
    public function index()
    {
        $user = User::where('role', 'user')->count();
        $transaksi = Transaction::select(['amount'])->where('status_transaksi', 'SUCCESS')->get();
        $event = Event::where('user_uid', Auth::user()->uid)->count();
        // dd($event);

        $tra = 0;
        foreach ($transaksi as $key => $tr) {
            $tra += $transaksi[$key]->amount;
        }

        $gr = HargaCart::join('carts', 'carts.uid', '=', 'harga_carts.uid',)
            ->join('events', 'events.uid', '=', 'carts.event_uid')
            ->where('carts.status', 'SUCCESS')
            ->where('events.user_uid', Auth::user()->uid)
            ->select(DB::raw('DATE(carts.created_at) as hargadate'), DB::raw('SUM(harga_carts.quantity) as total_qty'), DB::raw('SUM(harga_carts.quantity * harga_carts.harga_ticket) as total_amount'))
            ->groupBy(DB::raw('DATE(carts.created_at)'))
            ->get();

        $date = [];
        $qty = [];
        $amount = [];
        foreach ($gr as $grs) {
            $date[] = $grs->hargadate;
        }
        foreach ($gr as $grs) {
            $qty[] = $grs->total_qty;
        }
        foreach ($gr as $grs) {
            $amount[] = $grs->total_amount;
        }
        $totalTransaksi = Transaction::where('status_transaksi', 'SUCCESS')->count();

        $e = Event::join('hargas', 'hargas.uid', '=', 'events.uid')
            ->select('events.event', 'events.fee', 'hargas.kategori', 'hargas.harga')
            ->where('events.user_uid', '=', Auth::user()->uid)->get();

        $transformedEvents = [];
        $ubahStruktur = [];

        foreach ($e as $key => $eventss) {
            $eventName = $eventss->event;
            $eventFee = $eventss->fee;
            $kategori = $eventss->kategori;
            $harga = $eventss->harga;

            // Mencari indeks event yang sudah ada dalam $transformedEvents
            $existingEventIndex = array_search($eventName, array_column($transformedEvents, 'event'));

            // Jika event sudah ada dalam $transformedEvents, tambahkan tiket ke kategori yang ada
            if ($existingEventIndex !== false) {
                $transformedEvents[$existingEventIndex]['kategori'][] = $kategori;
                $transformedEvents[$existingEventIndex]['harga'][] = $harga;
            } else {
                // Jika event belum ada dalam $transformedEvents, buat elemen baru
                $ubahStruktur = [
                    'event' => $eventName,
                    'eventFee' => $eventFee,
                    'kategori' => [$kategori],
                    'harga' => [$harga],
                ];
                $transformedEvents[] = $ubahStruktur;
            }
        }
        $i =[];
        $j =[];
        foreach ($transformedEvents as $key=>$eventz) {
          $i[] = $transformedEvents[$key]['kategori'];
        }
        foreach ($transformedEvents as $key=>$eventz) {
          $j[] = $transformedEvents[$key]['harga'];
        }
        // dd($j);
        
        $ticketOptions = [];
        $hargaOption = [];
        foreach ($i as $key => $values) {
            $options = [];
            foreach ($values as $value) {
                $options[] = $value;
            }
            $ticketOptions[$key + 1] = $options;
        }
        foreach ($j as $key => $values) {
            $options = [];
            foreach ($values as $value) {
                $options[] = $value;
            }
            $hargaOption[$key + 1 ] = $options;
        }

        return view('penyewa.page.dashboard',
            [
                'title' => 'Dashboard',
                'countUser' => $user,
                'transaction' => $tra,
                'totalTransaksi' => $totalTransaksi,
                'gr' => $date,
                'qty' => $qty,
                'amount' => $amount,
                'eventCount' => $event,
                'event' => $transformedEvents,
                'ticketEvent' => $ticketOptions ,
                'hargaTicket' => $hargaOption ,
            ]
        );
    }
    public function login()
    {
        return  view('penyewa.auth.login', [
            'title' => 'Login',
        ]);
    }

    public function event($addEvent = null, $uid = null)
    {
        error_reporting(0);
        $event = Event::where('user_uid', Auth::user()->uid)->get();
        if ($addEvent === null) {
            $pagination = Event::where('user_uid', Auth::user()->uid)->paginate(12);
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
            $eventDetail = Event::where('uid', $uid)->where('user_uid', Auth::user()->uid)->first();
            $talent = Talent::where('uid', $uid)->get();
            $harga = Harga::where('uid', $uid)->get();
            if ($eventDetail === null) {
                abort('403');
            }
            return view('penyewa.eventSemi.eventDetail', [
                'title' => 'Event Detail',
                'eventDetail' => $eventDetail,
                'talent' => $talent,
                'harga' => $harga
            ]);
        }
    }


    public function ubahEvents($uid)
    {
        $ubahEvent = Event::where('uid', $uid)->first();
        return view('penyewa.eventSemi.addEvent', [
            'title' => 'Ubah Event',
            'ubahEvent' => $ubahEvent
        ]);
    }

    public function transaksi()
    {
        $use = User::all();
        $cartQuery = Cart::select(
            'carts.user_uid',
            'carts.invoice',
            'carts.status',
            'carts.payment_type',
            'events.event',
            'events.fee',
            'events.cover',
            'carts.created_at',
            DB::raw('SUM(harga_carts.quantity * harga_carts.harga_ticket) as total_harga'),
            DB::raw('SUM(harga_carts.quantity) as total_quantity')
        )
            ->join('harga_carts', 'harga_carts.uid', '=', 'carts.uid')
            ->join('events', 'events.uid', '=', 'carts.event_uid')
            ->where('carts.status', 'SUCCESS')
            ->where('carts.payment_type', '!=', 'cash')
            ->where('events.user_uid', Auth::user()->uid)
            ->groupBy('carts.user_uid', 'carts.invoice', 'carts.status', 'carts.payment_type', 'events.event', 'events.fee', 'carts.created_at', 'events.cover');

        $cart = $cartQuery->get();
        // dd($cart);
        $totalHargaCart = Cart::select(['harga_carts.harga_ticket', 'harga_carts.quantity'])
            ->join('harga_carts', 'harga_carts.uid', '=', 'carts.uid')
            ->join('events', 'events.uid', '=', 'carts.event_uid')
            ->where('carts.status', 'SUCCESS')
            ->where('carts.payment_type', '!=', 'cash')
            ->where('events.user_uid', Auth::user()->uid)
            ->get();
        $ar = 0;

        foreach ($totalHargaCart as $key => $tHC) {
            $ar += ($totalHargaCart[$key]->harga_ticket * $totalHargaCart[$key]->quantity);
        }

        $harga_cart = HargaCart::select(['quantity'])
            ->join('carts', 'carts.uid', '=', 'harga_carts.uid')
            ->join('events', 'events.uid', '=', 'carts.event_uid')
            ->where('carts.status', 'SUCCESS')
            ->where('carts.payment_type', '!=', 'cash')
            ->where('events.user_uid', Auth::user()->uid)
            ->get();
        // dd($harga_cart);
        $jml = 0;
        foreach ($harga_cart as $hs) {
            $jml += (int)$hs->quantity;
        }

        return view(
            'penyewa.page.transaksi',
            [
                'title' => 'Transaksi',
                'cart' => $cart,
                'use' => $use,
                'totalHargaCart' => $ar,
                'totalFee' => $jml
            ]
        );
        // return view('penyewa.page.transaksi', ['title', 'Transaksi']);
    }

    public function cash()
    {
        $use = User::all();
        $cartQuery = Cart::select(
            'carts.user_uid',
            'carts.invoice',
            'carts.status',
            'carts.payment_type',
            'events.event',
            'events.fee',
            'events.cover',
            'carts.created_at',
            DB::raw('SUM(harga_carts.quantity * harga_carts.harga_ticket) as total_harga'),
            DB::raw('SUM(harga_carts.quantity) as total_quantity')
        )
            ->join('harga_carts', 'harga_carts.uid', '=', 'carts.uid')
            ->join('events', 'events.uid', '=', 'carts.event_uid')
            ->where('carts.status', 'SUCCESS')
            ->where('carts.payment_type', 'cash')
            ->where('events.user_uid', Auth::user()->uid)
            ->groupBy('carts.user_uid', 'carts.invoice', 'carts.status', 'carts.payment_type', 'events.event', 'events.fee', 'carts.created_at', 'events.cover');

        $cart = $cartQuery->get();
        // dd($cart);
        $totalHargaCart = Cart::select(['harga_carts.harga_ticket', 'harga_carts.quantity'])
            ->join('harga_carts', 'harga_carts.uid', '=', 'carts.uid')
            ->join('events', 'events.uid', '=', 'carts.event_uid')
            ->where('carts.status', 'SUCCESS')
            ->where('carts.payment_type', 'cash')
            ->where('events.user_uid', Auth::user()->uid)
            ->get();
        $ar = 0;

        foreach ($totalHargaCart as $key => $tHC) {
            $ar += ($totalHargaCart[$key]->harga_ticket * $totalHargaCart[$key]->quantity);
        }

        $harga_cart = HargaCart::select(['quantity'])
            ->join('carts', 'carts.uid', '=', 'harga_carts.uid')
            ->join('events', 'events.uid', '=', 'carts.event_uid')
            ->where('carts.status', 'SUCCESS')
            ->where('carts.payment_type', 'cash')
            ->where('events.user_uid', Auth::user()->uid)
            ->get();
        // dd($harga_cart);
        $jml = 0;
        foreach ($harga_cart as $hs) {
            $jml += (int)$hs->quantity;
        }

     

        return view('penyewa.page.cash', [
            'title' => 'Cash',
            // 'event'=> $event
            'cart' => $cart,
            'use' => $use,
            'totalHargaCart' => $ar,
            'totalFee' => $jml
        ]);
    }



    public function voucher()
    {
        $voucher = Voucher::where('vouchers.user_uid', Auth::user()->uid)
            ->get();
        return view('penyewa.page.voucher', [
            'title' => 'Voucher',
            'voucher' => $voucher,

        ]);
    }

    public function money()
    {

        $totalHargaCart = Cart::select(['harga_carts.harga_ticket', 'harga_carts.quantity'])
            ->join('harga_carts', 'harga_carts.uid', '=', 'carts.uid')
            ->join('events', 'events.uid', '=', 'carts.event_uid')
            ->where('carts.payment_type', '!=', 'cash')
            ->where('carts.status', 'SUCCESS')
            ->where('events.user_uid', Auth::user()->uid)
            ->get();
        $ar = 0;
        foreach ($totalHargaCart as $key => $tHC) {
            $ar += ($totalHargaCart[$key]->harga_ticket * $totalHargaCart[$key]->quantity);
        }
        // dd($ar);
        $totalHargaCarts = Cart::select(['harga_carts.harga_ticket', 'harga_carts.quantity'])
            ->join('harga_carts', 'harga_carts.uid', '=', 'carts.uid')
            ->join('events', 'events.uid', '=', 'carts.event_uid')
            ->where('carts.payment_type', '=', 'cash')
            ->where('carts.status', 'SUCCESS')
            ->where('events.user_uid', Auth::user()->uid)
            ->get();
        $ars = 0;
        foreach ($totalHargaCarts as $key => $tHCs) {
            $ars += ($totalHargaCarts[$key]->harga_ticket * $totalHargaCarts[$key]->quantity);
        }
        // dd($ars);
        $penarikan = Penarikan::where('uid_user', Auth::user()->uid)->get();
        $pending = Penarikan::where('status', 'PENDING')->get();
        $arss = 0;
        foreach ($pending as $key => $pendings) {

            $arss += $pending[$key]->amount;
        }

        $success = Penarikan::where('status', 'SUCCESS')
            ->where('uid_user', Auth::user()->uid)
            ->get();
        $sc = 0;
        foreach ($success as $key => $scs) {
            $sc += (int) $success[$key]->amount;
        }

        return view('penyewa.page.money', [
            'title' => 'Money',
            'money' => $penarikan,
            'totalMoney' => $ar,
            'cash' => $ars,
            'pending' => $arss,
            'paid' => $sc
        ]);
    }

    public function profile()
    {

        $data = User::where('users.uid', Auth::user()->uid)
            ->join('banks', 'banks.uid', '=', 'users.uid')
            ->first();
        // dd($data);
        $http = Http::get('https://www.emsifa.com/api-wilayah-indonesia/api/provinces.json');
        if ($http->successful()) {
            $provinsi = $http->json();
        }
        // dd($provinsi);

        $bi = BankIndonesia::all();
        // dd($bi);

        return view(
            'penyewa.page.profile',
            [
                'title' => 'profile',
                'profile' => $data,
                'bi' => $bi,
                'pr' => $provinsi
            ]
        );
    }
}
