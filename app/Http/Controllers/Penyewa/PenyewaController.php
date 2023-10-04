<?php

namespace App\Http\Controllers\Penyewa;

use App\Models\Cart;
use App\Models\HargaCart;
use App\Models\User;
use App\Models\Event;
use App\Models\Harga;
use App\Models\Voucher;
use App\Models\Talent;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
// use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\CartVoucher;

class PenyewaController extends Controller
{
    public function index()
    {
        $user = User::where('role', 'user')->count();
        $transaksi = Transaction::select(['amount'])->where('status_transaksi', 'SUCCESS')->get();

        $tra = 0;
        foreach ($transaksi as $key => $tr) {
            $tra += $transaksi[$key]->amount;
        }

        $totalTransaksi = Transaction::where('status_transaksi', 'SUCCESS')->count();
        return view(
            'penyewa.page.dashboard',
            [
                'title' => 'Dashboard',
                'countUser' => $user,
                'transaction' => $tra,
                'totalTransaksi' => $totalTransaksi
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
        // dd($event);
        if ($addEvent === null) {
            $pagination = Event::where('user_uid', Auth::user()->uid)->paginate(12);
            // dd($pagination);
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
            // $id = $_GET['id'];
            $eventDetail = Event::where('uid', $uid)->where('user_uid', Auth::user()->uid)->first();
            $talent = Talent::where('uid', $uid)->get();
            $harga = Harga::where('uid', $uid)->get();
            // dd($eventDetail);
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
            'events.event',
            'events.fee',
            'events.cover',
            'carts.created_at',
            DB::raw('SUM(harga_carts.quantity * harga_carts.harga_ticket) as total_harga'),
            DB::raw('SUM(harga_carts.quantity) as total_quantity')
        )
            ->join('harga_carts', 'harga_carts.uid', '=', 'carts.uid')
            ->join('events', 'events.uid', '=', 'carts.event_uid')
            // ->join('users', 'users.uid', '=', 'events.user_uid')
            ->where('carts.status', 'SUCCESS')
            ->where('events.user_uid', Auth::user()->uid)
            ->groupBy('carts.user_uid', 'carts.invoice', 'carts.status', 'events.event', 'events.fee', 'carts.created_at', 'events.cover');

        $cart = $cartQuery->get();
        // dd($cart);
        $totalHargaCart = Cart::select(['harga_carts.harga_ticket', 'harga_carts.quantity'])
            ->join('harga_carts', 'harga_carts.uid', '=', 'carts.uid')
            ->join('events', 'events.uid', '=', 'carts.event_uid')
            ->where('carts.status', 'SUCCESS')
            ->where('events.user_uid', Auth::user()->uid)
            ->get();
        $ar = 0;
        // dd($totalHargaCart);
        foreach ($totalHargaCart as $key => $tHC) {
            $ar += ($totalHargaCart[$key]->harga_ticket * $totalHargaCart[$key]->quantity);
        }
        // dd($ar);

        // $totalFee = Event::select(['fee'])->where('carts.status', 'SUCCESS')
        //     ->join('carts', 'carts.event_uid', '=', 'events.uid')
        //     ->get();
        // $fe = 0;
        // foreach ($totalFee as $key => $tfe) {
        //     $fe += $totalFee[$key]->fee;
        // }

        $harga_cart = HargaCart::select(['quantity'])
            ->join('carts', 'carts.uid', '=', 'harga_carts.uid')
            ->join('events', 'events.uid', '=', 'carts.event_uid')
            ->where('carts.status', 'SUCCESS')
            ->where('events.user_uid', Auth::user()->uid)
            ->get();
        // dd($harga_cart);
        $jml = 0;
        foreach ($harga_cart as $hs) {
            $jml += (int)$hs->quantity;
        }
        // dd($jml);

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


    public function voucher()
    {
        $voucher = Voucher::where('vouchers.user_uid', Auth::user()->uid)
        ->get();
        return view('penyewa.page.voucher', [
            'title' => 'Voucher',
            'voucher' => $voucher,
            
        ]);
    }
}
