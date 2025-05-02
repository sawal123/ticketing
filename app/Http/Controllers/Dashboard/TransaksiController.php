<?php

namespace App\Http\Controllers\Dashboard;

use App\Models\Cart;
use App\Models\User;
use App\Models\Event;
use App\Models\HargaCart;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Defuse\Crypto\Key;

class TransaksiController extends Controller
{
    public function transaksi(Request $request)
    {
        // transaksi();
        $filter = $request->filter;
        $status = $request->status;

        if ($request->uid !== null) {
            $nameEvent = Event::where('uid', $request->uid)->firstOrFail();
            $use = User::all();

            #Query Untuk Table
            $cartQuery = Cart::with('event', 'hargaCarts')
                ->select(
                    'carts.uid',
                    'carts.user_uid',
                    'carts.invoice',
                    'events.event',
                    'carts.status',
                    DB::raw('SUM(events.fee * harga_carts.quantity) as fee'),
                    'events.cover',
                    'carts.created_at',
                    'carts.payment_type',
                    DB::raw('MAX(harga_carts.disc) as disc'),
                    DB::raw('MAX(vouchers.unit) as unit'),
                    DB::raw('MAX(harga_carts.voucher) as voucher'),
                    DB::raw('SUM(harga_carts.quantity * harga_carts.harga_ticket) as total_harga'),
                    DB::raw('SUM(harga_carts.quantity) as total_quantity')
                )
                ->join('harga_carts', 'harga_carts.uid', '=', 'carts.uid')
                ->join('events', 'events.uid', '=', 'carts.event_uid')
                ->leftJoin('vouchers', 'vouchers.code', '=', 'harga_carts.voucher') // Join ke tabel vouchers
                ->where('events.uid', $request->uid)
                ->where('carts.payment_type', '!=', 'cash')
                ->orderBy('carts.created_at', 'desc')
                ->groupBy('carts.uid', 'carts.user_uid', 'carts.invoice', 'carts.status', 'events.event', 'events.fee', 'carts.payment_type', 'carts.created_at', 'events.cover');

            if ($filter === null && $status === null) {
                $cart = $cartQuery->paginate(20); // Paginasi untuk performa lebih baik
            } elseif ($filter === null && $status) {
                $cart = $cartQuery->where('carts.status', $status)->get();
            } elseif ($filter && $status === null) {
                $cart = $cartQuery->whereDate('carts.created_at', $filter)->get();
            } else {
                $cart = $cartQuery->whereDate('carts.created_at', $filter)->where('carts.status', $status)->get();
            }
            #End Query

            #Query Untuk Penghitungan 
            $Thc = Cart::select(['harga_carts.uid', 'harga_carts.harga_ticket', 'harga_carts.quantity', 'harga_carts.disc', 'vouchers.unit', 'kategori_harga'])
                ->join('harga_carts', 'harga_carts.uid', '=', 'carts.uid')
                ->leftJoin('vouchers', 'vouchers.code', '=', 'harga_carts.voucher')
                ->join('events', 'events.uid', '=', 'carts.event_uid')
                ->where('carts.status', 'SUCCESS')
                ->where('carts.payment_type', '!=', 'cash')
                ->where('carts.event_uid', '=', $request->uid);
            if ($filter === null) {
                $dataTiket = $Thc->get();
            } else {
                $dataTiket = $Thc->whereDate('carts.created_at', '=', $filter)->get();
            }
            #End Query

            #Menghitung Total Uang Penjualan Tiket
            $totalPenjualan = 0;
            foreach ($dataTiket as $item) {
                $hargaTicket = $item->harga_ticket * $item->quantity;

                if ($item->unit === 'rupiah') {
                    $hargaTicket -= $item->disc; // Diskon langsung dikurangkan
                } elseif ($item->unit === 'persen') {
                    $hargaTicket -= ($hargaTicket * $item->disc / 100); // Diskon persen dikonversikan
                }

                $totalPenjualan += $hargaTicket;
            }
            #End Penghitungan

            #Query Uang Fee & Perhitungan
            $tfe = Event::join('carts', 'carts.event_uid', '=', 'events.uid')
                ->join('harga_carts', 'harga_carts.uid', '=', 'carts.uid')
                ->select(DB::raw('SUM(events.fee * harga_carts.quantity) as total_fee'))->where('carts.status', 'SUCCESS')
                ->where('carts.payment_type', '!=', 'cash')
                ->where('carts.event_uid', '=', $request->uid);
            $fe = 0;
            if ($filter === null) {
                $totalFee = $tfe->get();
            } else {
                $totalFee = $tfe->whereDate('carts.created_at', '=', $filter)->get();
            }
            foreach ($totalFee as $key => $tfe) {
                $fe += $totalFee[$key]->total_fee;
            }
            #End Perhitungan Dan Query

            $user = [];
            foreach ($use as $users) {
                $user[] = $users;
            }

            $hcart = HargaCart::select(['quantity'])
                ->join('carts', 'carts.uid', '=', 'harga_carts.uid')
                ->join('events', 'events.uid', '=', 'carts.event_uid')
                ->where('carts.status', 'SUCCESS')
                ->where('carts.payment_type', '!=', 'cash')
                ->where('events.uid', $request->uid);
            $jml = 0;
            if ($filter === null) {
                $harga_cart = $hcart->get();
            } else {
                $harga_cart = $hcart->whereDate('carts.created_at', '=', $filter)->get();
            }

            foreach ($harga_cart as $hs) {
                $jml += (int)$hs->quantity;
            }
        } else {
            return abort('403');
        }


        // dd($cart);
        return view(
            'backend.content.transaksi',
            [
                'title' => 'Transaksi Dashboard',
                'cart' => $cart,
                'use' => $use,
                'qtyTiket' => $dataTiket,
                'totalPenjualan' => $totalPenjualan,
                'totalFee' => $fe,
                'filter' => $filter,
                'uidEvent' => $request->uid,
                'jmlh' => $jml,
                'nameEvent' => $nameEvent,
                'status' => $request->status
            ]
        );
    }
}
