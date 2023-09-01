<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\User;
use App\Models\Event;
use App\Models\Harga;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\Slider;
use Illuminate\Support\Facades\Auth;
// use \Illuminate\Database\Eloquent\Collection;


class landingController extends Controller
{
    public function home()
    {
        $event = Event::all(['*']);
        $harga = Harga::select('uid', 'harga')->orderBy('harga', 'asc')->get();
        $slide = Slider::all(['*']);
        return view('frontend.page.home', [
            'title' => 'Home || Beli Tiket',
            'event' => $event,
            'harga' => $harga,
            'slide' => $slide
        ]);
    }

    public function ticket($event)
    {

        $ticket = Event::where('slug', $event)->first();
        $tickets = Event::select('events.*', 'talent.*')->join('talent', 'events.uid', '=', 'talent.uid')->where('slug', $event)->get();
        $harga = Event::select('events.*', 'hargas.*')->join('hargas', 'events.uid', '=', 'hargas.uid')->where('slug', $event)->get();
        // dd($harga);
        $list = [];
        foreach ($harga as $harga) {
            $list[] = [
                'uid' => $harga->uid,
                'kategori' => $harga->kategori,
                'qty' => $harga->qty,
                'harga' => $harga->harga,
            ];
        }
        return view('frontend.page.ticket', [
            'title' => 'Ticket',
            'ticket' => $ticket,
            'tickets' => $tickets,
            'list' => $list,
            'lists' => $list
            // 'harga1'=> $harga1

        ]);
    }

    public function listTransaksi()
    {
        $user = Auth::user();
        // dd(Auth::user()->uid);
        $cart = Cart::where('carts.user_uid', $user->uid)->select(
            'carts.uid',
            'carts.invoice',
            'carts.status',
            'events.cover',
            'carts.created_at',
            DB::raw('SUM(harga_carts.quantity * harga_carts.harga_ticket) as total_harga'),
            DB::raw('SUM(harga_carts.quantity) as total_quantity')
        )
            ->join('harga_carts', 'harga_carts.uid', '=', 'carts.uid')
            ->join('events', 'events.uid', '=', 'carts.event_uid')
            ->groupBy('carts.uid', 'carts.invoice', 'carts.status', 'carts.created_at', 'events.cover')
            ->get();
        // dd($cart);
        return view('frontend.page.transaksi.list-transaksi', [
            'title' => 'Transaksi',
            'transaksi' => $cart
        ]);
    }
}
