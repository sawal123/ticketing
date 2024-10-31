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
        // $user = User::where('role', 'user')->count();

        // $user = User::where()

        $transaksi = Transaction::select('amount')->where('status_transaksi', 'SUCCESS')->get();
        $event = Event::where('user_uid', Auth::user()->uid)->count();
        // dd($transaksi);

        $tra = 0;
        foreach ($transaksi as $key => $tr) {
            $tra += $transaksi[$key]->amount;
        }

        $totalHargaCart = Cart::select(['harga_carts.harga_ticket', 'harga_carts.quantity', 'harga_carts.disc'])
            ->join('harga_carts', 'harga_carts.uid', '=', 'carts.uid')
            ->join('events', 'events.uid', '=', 'carts.event_uid')
            ->where('carts.status', 'SUCCESS')
            ->where('carts.payment_type', '!=', 'cash')
            ->where('events.user_uid', Auth::user()->uid)
            ->get();
        $ar = 0;
        $discounts = 0;

        foreach ($totalHargaCart as $key => $tHC) {
            $ar += ($totalHargaCart[$key]->harga_ticket * $totalHargaCart[$key]->quantity);
        }
        foreach ($totalHargaCart as $key => $discount) {
            $discounts += $totalHargaCart[$key]->disc;
        }

        $gr = HargaCart::join('carts', 'carts.uid', '=', 'harga_carts.uid',)
            ->join('events', 'events.uid', '=', 'carts.event_uid')
            ->where('carts.status', 'SUCCESS')
            ->where('events.user_uid', Auth::user()->uid)
            ->select(DB::raw('DATE(carts.created_at) as hargadate'), DB::raw('SUM(harga_carts.quantity) as total_qty'), DB::raw('SUM((harga_carts.quantity * harga_carts.harga_ticket) - COALESCE(harga_carts.disc, 0) ) as total_amount'))
            ->groupBy(DB::raw('DATE(carts.created_at)'))
            ->get();

        $date = [];
        $qty = [];
        $amount = [];
        $totalAmount = 0;
        foreach ($gr as $grs) {
            $date[] = $grs->hargadate;
        }
        foreach ($gr as $grs) {
            $qty[] = $grs->total_qty;
        }
        foreach ($gr as $grs) {
            $amount[] = $grs->total_amount;
            $totalAmount +=   $grs->total_amount;
        }
        // dd($gr);
        $totalTransaksi = Cart::where('carts.status', 'SUCCESS')
            ->join('events', 'events.uid', '=', 'carts.event_uid')
            ->where('events.user_uid', Auth::user()->uid)
            ->count();
        // dd($totalTransaksi);
        $partner = Partner::where('referensi', Auth::user()->uid)->where('status', 'active')->get();

        $e = Event::join('hargas', 'hargas.uid', '=', 'events.uid')
            ->select('events.event', 'events.fee', 'hargas.kategori', 'hargas.harga')
            ->where('events.user_uid', '=', Auth::user()->uid)->where('events.konfirmasi', '=', '1')->get();
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
        // dd($transformedEvents);
        $i = [];
        $j = [];
        foreach ($transformedEvents as $key => $eventz) {
            $i[] = $transformedEvents[$key]['kategori'];
        }
        foreach ($transformedEvents as $key => $eventz) {
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
            $hargaOption[$key + 1] = $options;
        }

        $harga_cart = HargaCart::select(['quantity'])
            ->join('carts', 'carts.uid', '=', 'harga_carts.uid')
            ->join('events', 'events.uid', '=', 'carts.event_uid')
            ->where('carts.status', 'SUCCESS')
            ->where('carts.payment_type', '!=', 'cash')
            ->where('events.user_uid', Auth::user()->uid)
            // ->whereDate('carts.created_at', '=', $filter)
            ->get();
        // dd($ar);
        $jml = 0;
        foreach ($harga_cart as $hs) {
            $jml += (int)$hs->quantity;
        }

        $genderCounts = Cart::join('users', 'users.uid', '=', 'carts.user_uid')
            // ->join('cashes', 'uid_user', '=', 'carts.user_uid')
            ->join('events', 'events.uid', '=', 'carts.event_uid')
            ->select('users.gender', DB::raw('COUNT(*) as count'))
            ->where('events.user_uid', Auth::user()->uid)
            ->where('carts.status', 'SUCCESS')
            ->groupBy('users.gender')
            ->pluck('count', 'users.gender')
            ->toArray();
        $pria = $genderCounts['wanita'] ?? 0;
        $wanita = $genderCounts['pria'] ?? 0;




        // Hitung total pengguna
        $totalUsers = $wanita + $pria;

        // Hitung persentase
        $persentaseWanita = $totalUsers > 0 ? ($wanita / $totalUsers) * 100 : 0;
        $persentasePria = $totalUsers > 0 ? ($pria / $totalUsers) * 100 : 0;

        $dataUser = [$wanita, $pria, $persentaseWanita, $persentasePria];

        $birtday = Cart::join('users', 'users.uid', '=', 'carts.user_uid')
            // ->join('cashes', 'uid_user', '=', 'carts.user_uid')
            ->join('events', 'events.uid', '=', 'carts.event_uid')

            ->select(
                DB::raw(
                    "
        CASE
            WHEN TIMESTAMPDIFF(YEAR, users.birthday, users.created_at) < 18 THEN '18 thn ke bawah'
            WHEN TIMESTAMPDIFF(YEAR, users.birthday, users.created_at) BETWEEN 18 AND 25 THEN '18 s/d 25 tahun'
            ELSE '25 tahun ke atas'
        END AS age_group"
                ),
                'users.gender',
                DB::raw('COUNT(*) as count')
            )
            ->where('events.user_uid', Auth::user()->uid)
            ->where('carts.status', 'SUCCESS')
            ->groupBy('age_group', 'users.gender')
            ->get()
            ->groupBy('age_group')
            ->map(function ($group) {
                return $group->groupBy('gender')->mapWithKeys(function ($item, $key) {
                    return [$key => $item->sum('count')];
                });
            })
            ->toArray();
        // dd($birtday);

        // dd($dataUser);
        return view('penyewa.page.dashboard',
            [
                'title' => 'Dashboard',
                'dataUser' => $dataUser,
                'transaction' => $ar - $discounts,
                'totalTransaksi' => $totalTransaksi,
                'gr' => $date,
                'qty' => $qty,
                'amount' => $amount,
                'totalAmount' => $totalAmount,
                'eventCount' => $event,
                'event' => $transformedEvents,
                'ticketEvent' => $ticketOptions,
                'hargaTicket' => $hargaOption,
                'partner' => $partner,
                'totalTiket' => $jml,
                'birtday' => $birtday
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
            $hargaC = HargaCart::join('carts', 'carts.uid', '=', 'harga_carts.uid')->where('carts.event_uid', $eventDetail->uid)->where('carts.status', 'SUCCESS')->get();
            $cart = Cart::where('event_uid', $eventDetail->uid)->where('status', '=', 'SUCCESS')->get();
            // dd($hargaC);
            if ($eventDetail === null) {
                abort('403');
            }
            // dd($cart);
            return view('penyewa.eventSemi.eventDetail', [
                'hargaC' => $hargaC,
                'title' => 'Event Detail',
                'eventDetail' => $eventDetail,
                'talent' => $talent,
                'harga' => $harga,
                'cart' => $cart
            ]);
        }
    }


    public function ubahEvents($uid)
    {
        $ubahEvent = Event::join('event_dates', 'events.uid', 'event_dates.uid')->where('events.uid', $uid)->first();

        // dd($ubahEvent);
        return view('penyewa.eventSemi.addEvent', [
            'title' => 'Ubah Event',
            'ubahEvent' => $ubahEvent
        ]);
    }

    public function transaksi(Request $request)
    {
        $filter = $request->filter;
        // if ($filter === null) {
        //     $filter = date('Y-m-d');
        // }
        // dd($request->uid);
        $event = null;
        if ($request->uid !== null) {
            $use = User::all();
            $event = Event::where('uid', $request->uid)->first();
            $cartQuery = Cart::select(
                'carts.uid',
                'carts.user_uid',
                'carts.invoice',
                'carts.status',
                'carts.payment_type',
                'events.event',
                'events.fee',
                'events.cover',
                'carts.created_at',
                'harga_carts.disc',
                'harga_carts.voucher',
                DB::raw('SUM(harga_carts.quantity * harga_carts.harga_ticket) as total_harga'),
                DB::raw('SUM(harga_carts.quantity) as total_quantity')
            )
                ->join('harga_carts', 'harga_carts.uid', '=', 'carts.uid')
                ->join('events', 'events.uid', '=', 'carts.event_uid')
                ->where('carts.status', 'SUCCESS')
                ->where('carts.payment_type', '!=', 'cash')
                ->where('events.user_uid', Auth::user()->uid)
                ->where('carts.event_uid', $request->uid)
                ->groupBy('carts.uid', 'carts.user_uid', 'carts.invoice', 'carts.status', 'carts.payment_type', 'harga_carts.disc', 'harga_carts.voucher', 'events.event', 'events.fee', 'carts.created_at', 'events.cover');


            // dd($cart);
            $totalHargaCart = Cart::select(['harga_carts.uid', 'harga_carts.harga_ticket', 'harga_carts.quantity', 'harga_carts.disc', 'kategori_harga'])
                ->join('harga_carts', 'harga_carts.uid', '=', 'carts.uid')
                ->join('events', 'events.uid', '=', 'carts.event_uid')
                ->where('carts.status', 'SUCCESS')
                ->where('carts.payment_type', '!=', 'cash')
                ->where('events.user_uid', Auth::user()->uid)
                ->where('carts.event_uid', '=', $request->uid);
            $ar = 0;
            $discounts = 0;
            // dd($totalHargaCart);



            $harga_cart = HargaCart::select(['quantity'])
                ->join('carts', 'carts.uid', '=', 'harga_carts.uid')
                ->join('events', 'events.uid', '=', 'carts.event_uid')
                ->where('carts.status', 'SUCCESS')
                ->where('carts.payment_type', '!=', 'cash')
                ->where('events.user_uid', Auth::user()->uid);

            if ($filter === null) {
                $cart = $cartQuery->get();
                $totalHargaCart =  $totalHargaCart->get();
                $harga_cart = $harga_cart->get();
            } else {
                $cart = $cartQuery->whereDate('carts.created_at', '=', $filter)->get();
                $totalHargaCart =  $totalHargaCart->whereDate('carts.created_at', '=', $filter)->get();
                $harga_cart = $harga_cart->whereDate('carts.created_at', '=', $filter)->get();
            }

            foreach ($totalHargaCart as $key => $tHC) {
                $ar += ($totalHargaCart[$key]->harga_ticket * $totalHargaCart[$key]->quantity);
            }

            foreach ($totalHargaCart as $key => $discount) {
                $discounts += $totalHargaCart[$key]->disc;
            }

            $jml = 0;
            foreach ($harga_cart as $hs) {
                $jml += (int)$hs->quantity;
            }
        } else {
            $use = User::all();
            $cartQuery = Cart::select(
                'carts.uid',
                'carts.user_uid',
                'carts.invoice',
                'carts.status',
                'carts.payment_type',
                'events.event',
                'events.fee',
                'events.cover',
                'carts.created_at',
                'harga_carts.disc',
                'harga_carts.voucher',
                DB::raw('SUM(harga_carts.quantity * harga_carts.harga_ticket) as total_harga'),
                DB::raw('SUM(harga_carts.quantity) as total_quantity')
            )
                ->join('harga_carts', 'harga_carts.uid', '=', 'carts.uid')
                ->join('events', 'events.uid', '=', 'carts.event_uid')
                ->where('carts.status', 'SUCCESS')
                ->where('carts.payment_type', '!=', 'cash')
                ->where('events.user_uid', Auth::user()->uid)
                ->groupBy('carts.uid', 'carts.user_uid', 'carts.invoice', 'carts.status', 'carts.payment_type', 'harga_carts.disc', 'harga_carts.voucher', 'events.event', 'events.fee', 'carts.created_at', 'events.cover');

            $totalHargaCart = Cart::select(['harga_carts.uid', 'harga_carts.harga_ticket', 'harga_carts.quantity', 'harga_carts.disc', 'kategori_harga'])
                ->join('harga_carts', 'harga_carts.uid', '=', 'carts.uid')
                ->join('events', 'events.uid', '=', 'carts.event_uid')
                ->where('carts.status', 'SUCCESS')
                ->where('carts.payment_type', '!=', 'cash')
                ->where('events.user_uid', Auth::user()->uid);
            $ar = 0;
            $discounts = 0;

            $harga_cart = HargaCart::select(['quantity'])
                ->join('carts', 'carts.uid', '=', 'harga_carts.uid')
                ->join('events', 'events.uid', '=', 'carts.event_uid')
                ->where('carts.status', 'SUCCESS')
                ->where('carts.payment_type', '!=', 'cash')
                ->where('events.user_uid', Auth::user()->uid);

            if ($filter === null) {
                $cart = $cartQuery->get();
                $totalHargaCart =  $totalHargaCart->get();
                $harga_cart = $harga_cart->get();
            } else {
                $cart = $cartQuery->whereDate('carts.created_at', '=', $filter)->get();
                $totalHargaCart =  $totalHargaCart->whereDate('carts.created_at', '=', $filter)->get();
                $harga_cart = $harga_cart->whereDate('carts.created_at', '=', $filter)->get();
            }

            foreach ($totalHargaCart as $key => $tHC) {
                $ar += ($totalHargaCart[$key]->harga_ticket * $totalHargaCart[$key]->quantity);
            }

            foreach ($totalHargaCart as $key => $discount) {
                $discounts += $totalHargaCart[$key]->disc;
            }

            $jml = 0;
            foreach ($harga_cart as $hs) {
                $jml += (int)$hs->quantity;
            }
        }

        return view(
            'penyewa.page.transaksi',
            [
                'title' => 'Transaksi',
                'cart' => $cart,
                'event' => $event,
                'use' => $use,
                'qtyTiket' => $totalHargaCart,
                'totalHargaCart' => $ar - $discounts,
                'totalFee' => $jml,
                'filter' => $filter,
                'uid' => $request->uid
            ]
        );
    }

    public function cash(Request $request)
    {
        $filter = $request->filter;
        // if ($filter === null) {
        //     $filter = date('Y-m-d');
        // }
        // dd($filter);
        $use = User::all();
        $event = null;
        // dd($request->uid);
        if ($request->uid) {
            $event = Event::where('uid', $request->uid)->first();
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
                DB::raw('SUM(harga_carts.quantity * harga_carts.harga_ticket) as total_harga'),
                DB::raw('SUM(harga_carts.quantity) as total_quantity')
            )
                ->join('harga_carts', 'harga_carts.uid', '=', 'carts.uid')
                ->join('events', 'events.uid', '=', 'carts.event_uid')
                ->join('cashes', 'cashes.uid', '=', 'carts.uid')
                ->where('carts.status', 'SUCCESS')
                ->where('carts.payment_type', 'cash')
                ->where('events.user_uid', Auth::user()->uid)
                ->where('events.uid', $request->uid)
                ->groupBy('carts.uid', 'carts.user_uid', 'carts.invoice', 'cashes.name', 'cashes.email', 'carts.status', 'carts.payment_type', 'events.event', 'carts.created_at',);

            if ($filter === null) {
                $cart = $cartQuery->get();
            } else {
                $cart = $cartQuery->whereDate('carts.created_at', $filter)->get();
            }
            // dd($cart);
            $totalHargaCart = Cart::select(['harga_carts.uid', 'harga_carts.harga_ticket', 'harga_carts.quantity', 'harga_carts.kategori_harga'])
                ->join('harga_carts', 'harga_carts.uid', '=', 'carts.uid')
                ->join('events', 'events.uid', '=', 'carts.event_uid')
                ->where('carts.status', 'SUCCESS')
                ->where('carts.payment_type', 'cash')
                ->where('events.user_uid', Auth::user()->uid)
                ->where('events.uid', $request->uid)
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
                ->where('events.uid', $request->uid)
                ->get();
            // dd($harga_cart);
            $jml = 0;
            foreach ($harga_cart as $hs) {
                $jml += (int)$hs->quantity;
            }
        } else {

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
                DB::raw('SUM(harga_carts.quantity * harga_carts.harga_ticket) as total_harga'),
                DB::raw('SUM(harga_carts.quantity) as total_quantity')
            )
                ->join('harga_carts', 'harga_carts.uid', '=', 'carts.uid')
                ->join('events', 'events.uid', '=', 'carts.event_uid')
                ->join('cashes', 'cashes.uid', '=', 'carts.uid')
                ->where('carts.status', 'SUCCESS')
                ->where('carts.payment_type', 'cash')
                ->where('events.user_uid', Auth::user()->uid)
                ->groupBy('carts.uid', 'carts.user_uid', 'carts.invoice', 'cashes.name', 'cashes.email', 'carts.status', 'carts.payment_type', 'events.event', 'carts.created_at',);

            $totalHargaCart = Cart::select(['harga_carts.uid', 'harga_carts.harga_ticket', 'harga_carts.quantity', 'harga_carts.kategori_harga'])
                ->join('harga_carts', 'harga_carts.uid', '=', 'carts.uid')
                ->join('events', 'events.uid', '=', 'carts.event_uid')
                ->where('carts.status', 'SUCCESS')
                ->where('carts.payment_type', 'cash')
                ->where('events.user_uid', Auth::user()->uid);
            // ->get();
            $ar = 0;

            $harga_cart = HargaCart::select(['quantity'])
                ->join('carts', 'carts.uid', '=', 'harga_carts.uid')
                ->join('events', 'events.uid', '=', 'carts.event_uid')
                ->where('carts.status', 'SUCCESS')
                ->where('carts.payment_type', 'cash')
                ->where('events.user_uid', Auth::user()->uid);

            $jml = 0;

            if ($filter === null) {
                $harga_cart = $harga_cart->get();
                $totalHargaCart = $totalHargaCart->get();
                $cart = $cartQuery->get();
            } else {
                $harga_cart = $harga_cart->whereDate('carts.created_at', '=', $filter)->get();
                $cart = $cartQuery->whereDate('carts.created_at', '=', $filter)->get();
                $totalHargaCart = $totalHargaCart->whereDate('carts.created_at', '=', $filter)->get();
            }

            foreach ($totalHargaCart as $key => $tHC) {
                $ar += ($totalHargaCart[$key]->harga_ticket * $totalHargaCart[$key]->quantity);
            }

            foreach ($harga_cart as $hs) {
                $jml += (int)$hs->quantity;
            }
        }

        // dd($harga_cart);



        return view('penyewa.page.cash', [
            'title' => 'Cash',
            'event' => $event,
            'cart' => $cart,
            'use' => $use,
            'totalHargaCart' => $ar,
            'qtyTiket' => $totalHargaCart,
            'sellTiket' => $jml,
            'filter' => $filter,
            'uid' => $request->uid
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

    public function partner()
    {
        error_reporting(0);
        $http = Http::get('https://www.emsifa.com/api-wilayah-indonesia/api/provinces.json');
        if ($http->successful()) {
            $provinsi = $http->json();
        } else {
            $provinsi = ['null', 'data'];
        }


        $partner = Partner::where('referensi', Auth::user()->uid)->get();
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

        $data = User::where('users.uid', Auth::user()->uid)
            ->join('banks', 'banks.uid', '=', 'users.uid')
            ->first();
        if ($data === null) {
            $data = User::where('uid', Auth::user()->uid)->first();
        }
        // dd($data);
        $http = Http::get('https://www.emsifa.com/api-wilayah-indonesia/api/provinces.json');
        if ($http->successful()) {
            $provinsi = $http->json();
        }
        // dd($provinsi);
        $bi = BankIndonesia::all();
        // dd($data);

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
