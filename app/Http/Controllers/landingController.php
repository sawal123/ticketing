<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\Term;
use App\Models\User;
use App\Models\Event;
use App\Models\Harga;
use App\Models\Slider;
use App\Models\Landing;
use App\Models\HargaCart;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\Contact;
use Illuminate\Support\Facades\Auth;
// use \Illuminate\Database\Eloquent\Collection;


class landingController extends Controller
{
    public function home()
    {
        $events = Event::with('harga')
            ->where('konfirmasi', '1')
            ->join('users', 'events.user_uid', '=', 'users.uid')
            ->select(
                'events.uid',
                'events.user_uid',
                'events.event',
                'events.alamat',
                'events.tanggal',
                'events.status',
                'events.cover',
                'events.map',
                'events.slug',
                'events.konfirmasi',
                'users.name'
            )
            ->orderBy('events.created_at', 'desc')
            ->take(9)
            ->get();
        // $harga = Harga::select('uid', 'harga')->orderBy('harga', 'asc')->get();
        $slide = Slider::all(['*']);
        // dd($harga);

        return view('frontend.page.home', [
            'title' => 'Tiket',
            'events' => $events,
            // 'harga' => $harga,
            'slide' => $slide
        ]);
    }

    public function ticket($event)
    {

        error_reporting(0);
        $ticket = Event::where('slug', $event)->first();
        $tickets = Event::select('events.*', 'talent.*')->join('talent', 'events.uid', '=', 'talent.uid')->where('slug', $event)->get();

        $harga = Event::select('events.*', 'hargas.*')
            ->join('hargas', 'events.uid', '=', 'hargas.uid')
            ->where('slug', $event)->get();
        $list = [];
        foreach ($harga as $harga) {
            $list[] = [
                'uid' => $harga->uid,
                'kategori' => $harga->kategori,
                'qty' => $harga->qty,
                'harga' => $harga->harga,
            ];
        }
        $hc = HargaCart::select(['harga_carts.*'])
            ->where('harga_carts.event_uid', $ticket->uid)->where('carts.status', 'SUCCESS')
            ->join('carts', 'carts.uid', 'harga_carts.uid')
            ->get();
        $jml = 0;
        $dataList = [];
        foreach ($hc as $hcs) {
            $dataList[] =
                [
                    'quantity' => $hcs->quantity,
                    'kategori' => $hcs->kategori_harga,
                ];
        }

        $jmlByCategory = [];

        foreach ($dataList as $item) {
            $kategori = $item['kategori'];
            $quantity = $item['quantity'];
            if (!isset($jmlByCategory[$kategori])) {
                $jmlByCategory[$kategori] = 0;
            }
            $jmlByCategory[$kategori] += $quantity;
        }
        // dd($jmlByCategory);


        return view('frontend.page.ticket', [
            'title' => $ticket->event,
            'ticket' => $ticket,
            'tickets' => $tickets,
            'list' => $list,
            'lists' => $list,
            'jmlhQty' => $jmlByCategory
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

    public function search($search = null)
    {
        // $search = $request->search;

        if ($search) {
            $event = Event::where('event', 'LIKE', "%$search%")
                ->orWhere('alamat', 'LIKE', "%$search%")->orWhere('slug', 'LIKE', "%$search%")
                ->orWhereHas('talent', function ($query) use ($search) {
                    $query->where('talent', 'LIKE', "%$search%");
                })
                ->get();
            // return redirect('/search/'. $event);
        } else {
            $event = Event::where('konfirmasi', '1')
                ->join('users', 'events.user_uid', '=', 'users.uid')
                ->select(
                    'events.uid',
                    'events.user_uid',
                    'events.event',
                    'events.alamat',
                    'events.tanggal',
                    'events.status',
                    'events.cover',
                    'events.map',
                    'events.slug',
                    'events.konfirmasi',
                    'users.name'

                )
                ->get();
        }
        //  dd($event);
        $harga = Harga::select('uid', 'harga')->orderBy('harga', 'asc')->get();
        return view(
            'frontend.page.post.post',
            [
                'title' => 'Search Event',
                'event' => $event,
                'harga' => $harga,
                // 'search' =>$search
            ]
        );
    }
    public function cari()
    {
        $cari = $_GET['cari'];
        return redirect('/search/' . $cari)->withInput();
    }

    public function term()
    {
        $term = Term::all();
        return view('frontend.page.term', [
            'title' => 'Term and Condition',
            'term' => $term
        ]);
    }

    public function contact()
    {

        // dd($contact);
        return view('frontend.page.contact', ['title' => 'Contact',]);
    }
}
