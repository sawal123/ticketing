<?php

namespace App\Http\Controllers\Dashboard;

use App\Models\Cart;
use App\Models\User;
use App\Models\Event;
use App\Models\HargaCart;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;

class TransaksiController extends Controller
{
    public function transaksi(Request $request)
    {
        // transaksi();
        $filter = $request->filter;
        $status = $request->status;

        // if ($filter === null) {
        //     $filter = date('Y-m-d');
        // }
        if ($request->uid !== null) {
            $nameEvent = Event::where('uid', $request->uid)->firstOrFail();
            $use = User::all();

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
                    DB::raw('SUM(harga_carts.quantity * harga_carts.harga_ticket) as total_harga'),
                    DB::raw('SUM(harga_carts.quantity) as total_quantity')
                )
                ->join('harga_carts', 'harga_carts.uid', '=', 'carts.uid')
                ->join('events', 'events.uid', '=', 'carts.event_uid')
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


            $Thc = Cart::select(['harga_carts.uid', 'harga_carts.harga_ticket', 'harga_carts.quantity', 'harga_carts.disc', 'kategori_harga'])
                ->join('harga_carts', 'harga_carts.uid', '=', 'carts.uid')
                ->join('events', 'events.uid', '=', 'carts.event_uid')
                ->where('carts.status', 'SUCCESS')
                ->where('carts.payment_type', '!=', 'cash')
                ->where('carts.event_uid', '=', $request->uid);

            $ar = 0;
            if ($filter === null) {
                $totalHargaCart = $Thc->get();
            } else {
                $totalHargaCart = $Thc->whereDate('carts.created_at', '=', $filter)->get();
            }
            foreach ($totalHargaCart as $key => $tHC) {
                $ar += ($totalHargaCart[$key]->harga_ticket * $totalHargaCart[$key]->quantity);
            }

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


        // dd($fe);
        return view('backend.content.transaksi',
            [
                'title' => 'Transaksi Dashboard',
                'cart' => $cart,
                'use' => $use,
                'qtyTiket' => $totalHargaCart,
                'totalHargaCart' => $ar,
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
