<?php

namespace App\Http\Controllers\Dashboard;

use App\Models\Event;
use App\Models\Harga;
use App\Models\HargaCart;
use App\Models\Talent;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\Slider;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function dashboard()
    {
        $user = User::where('role', 'user')->count();
        $transaksi = Transaction::select(['amount'])->where('status_transaksi', 'SUCCESS')->get();
        
        $tra = 0;
        foreach ($transaksi as $key => $tr) {
            $tra += $transaksi[$key]->amount;
        }

        $totalTransaksi = Transaction::where('status_transaksi', 'SUCCESS')->count();
        // dd($tr);
        // dd($user);
        return view('backend.content.dashboard', [
            'title' => 'Dashboard',
            'countUser' => $user,
            'transaction' => $tra,
            'totalTransaksi' => $totalTransaksi
        ]);
    }
    public function event($addEvent = null, $uid = null)
    {
        error_reporting(0);
        $event = Event::where('user_uid', Auth::user()->uid)->get();
        // dd($event);
        // dd($addEvent);
        if ($addEvent === null) {
            return view('backend.content.event', [
                'title' => 'Event',
                'event' => $event
            ]);
        } elseif ($addEvent === 'addEvent') {

            return view('backend.semiPage.addEvent', [
                'title' => 'Add Event',

            ]);
        } elseif ($addEvent === 'eventDetail') {
            // $id = $_GET['id'];
            $eventDetail = Event::where('uid', $uid)->first();
            $talent = Talent::where('uid', $uid)->get();
            $harga = Harga::where('uid', $uid)->get();
            // dd($eventDetail);
            if ($eventDetail === null) {
                abort('403');
            }
            return view('backend.semiPage.eventDetail', [
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
        return view('backend.semiPage.addEvent', [
            'title' => 'Ubah Event',
            'ubahEvent' => $ubahEvent
        ]);
    }

    public function landing(Request $request)
    {
        $slide = Slider::orderBy('sort', 'asc')->get();

        return view('backend.content.landing', [
            'title' => 'Landing',
            'slide' => $slide,
            'slider' => $slide,
        ]);
    }
    public function transaksi()
    {
        $use = User::all();
        $cartQuery = Cart::select(
            // 'users.name',
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
            // Menggunakan LEFT JOIN
            ->groupBy('carts.user_uid', 'carts.invoice', 'carts.status', 'events.event', 'events.fee', 'carts.created_at', 'events.cover');

        $cart = $cartQuery->get();
        $totalHargaCart = Cart::select(['harga_carts.harga_ticket'])->where('carts.status', 'SUCCESS')
            ->join('harga_carts', 'harga_carts.uid', '=', 'carts.uid')
            ->get();
        $ar = 0;
        // dd($totalHargaCart);
        foreach ($totalHargaCart as $key => $tHC) {
            $ar += $totalHargaCart[$key]->harga_ticket;
        }

        $totalFee = Event::select(['fee'])->where('carts.status', 'SUCCESS')
            ->join('carts', 'carts.event_uid', '=', 'events.uid')
            ->get();
        $fe = 0;
        // dd($totalHargaCart);
        foreach ($totalFee as $key => $tfe) {
            $fe += $totalFee[$key]->fee;
        }



        $user = [];
        foreach ($use as $users) {
            $user[] = $users;
        }


        return view(
            'backend.content.transaksi',
            [
                'title' => 'Transaksi Dashboard',
                'cart' => $cart,
                'use' => $use,
                'totalHargaCart' => $ar,
                'totalFee' => $fe
            ]
        );
    }
}
