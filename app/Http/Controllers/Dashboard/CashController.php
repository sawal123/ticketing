<?php

namespace App\Http\Controllers\Dashboard;

use App\Models\Cart;
use App\Models\User;
use App\Models\Event;
use App\Models\HargaCart;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;

class CashController extends Controller
{
    public function cash(Request $request)
    {
        // transaksi();
        $filter = $request->filter;

        if ($request->uid !== null) {
            $nameEvent = Event::where('uid', $request->uid)->firstOrFail();
            $use = User::all();

            $cartQuery = Cart::with('event', 'hargaCarts')
                ->join('harga_carts', 'harga_carts.uid', '=', 'carts.uid')
                ->join('events', 'events.uid', '=', 'carts.event_uid')
                ->join('cashes', 'cashes.uid', '=', 'carts.uid')
                ->select(
                    'carts.uid',
                    'carts.user_uid',
                    'cashes.name',
                    'cashes.email',
                    'carts.invoice',
                    'events.event',
                    'carts.status',
                    DB::raw('SUM(events.fee * harga_carts.quantity) as fee'),
                    'carts.created_at',
                    'carts.payment_type',
                    DB::raw('SUM(harga_carts.quantity * harga_carts.harga_ticket) as total_harga'),
                    DB::raw('SUM(harga_carts.quantity) as total_quantity')
                )

                ->where('events.uid', $request->uid)
                ->where('carts.payment_type', '=', 'cash')
                ->orderBy('carts.created_at', 'desc')
                ->groupBy('carts.uid', 'carts.user_uid','cashes.name','cashes.email', 'carts.invoice', 'carts.status', 'events.event', 'events.fee', 'carts.payment_type', 'carts.created_at');

            if ($filter === null) {
                $cart = $cartQuery->paginate(50); // Paginasi untuk performa lebih baik
            } else {
                $cart = $cartQuery->whereDate('carts.created_at', $filter)->get();
            }
            // dd($cart);


            $Thc = Cart::select(['harga_carts.uid', 'harga_carts.harga_ticket', 'harga_carts.quantity', 'harga_carts.disc', 'kategori_harga'])
                ->join('harga_carts', 'harga_carts.uid', '=', 'carts.uid')
                ->join('events', 'events.uid', '=', 'carts.event_uid')
                ->where('carts.status', 'SUCCESS')
                ->where('carts.payment_type', '=', 'cash')
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
                ->where('carts.payment_type', '=', 'cash')
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
            // dd($jml);
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

    public function editCash(Request $request){
        return abort('403');
    }
}
