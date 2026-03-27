<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\Term;
use App\Models\Event;
use App\Models\Harga;
use App\Models\Slider;
use App\Models\HargaCart;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class landingController extends Controller
{
    public function home()
    {
        // AJAIBNYA ELOQUENT: Cukup panggil with(['harga', 'user']) 
        // Tidak perlu lagi join manual ke tabel users!
        $events = Event::with(['harga', 'user'])
            ->where('konfirmasi', '1')
            ->orderBy('created_at', 'desc')
            ->take(9)
            ->get();

        $slide = Slider::all();

        // Saya hapus $harga global di sini karena biasanya harga itu nempel per-event.
        // Jika view kamu masih butuh $harga global, bisa dikembalikan.
        return view('frontend.page.home', [
            'title' => 'Tiket',
            'events' => $events,
            'slide' => $slide
        ]);
    }

    public function ticket($slug)
    {
        // Hapus error_reporting(0) karena kode kita sekarang sudah aman

        // Ambil event beserta SEMUA relasinya (Talent & Hargas)
        // firstOrFail() akan memunculkan halaman 404 otomatis jika event tidak ada
        $ticket = Event::with(['talents', 'hargas'])->where('slug', $slug)->firstOrFail();

        // MENGHITUNG TIKET TERJUAL: Jauh lebih singkat dengan Query Builder Laravel
        $soldTickets = HargaCart::select('kategori_harga', DB::raw('SUM(quantity) as total_sold'))
            ->where('event_uid', $ticket->uid)
            ->whereHas('cart', function ($query) {
                // Pastikan hanya menghitung yang status transaksinya SUCCESS
                $query->where('status', 'SUCCESS');
            })
            ->groupBy('kategori_harga')
            ->pluck('total_sold', 'kategori_harga')
            ->toArray();
        // Hasilnya langsung berbentuk array: ['VIP' => 10, 'Reguler' => 25]

        return view('frontend.page.ticket', [
            'title'   => $ticket->event,
            'ticket'  => $ticket,

            // Variabel di bawah saya buat menyesuaikan view lama kamu agar tidak error.
            // Walaupun sebenarnya di view kamu cukup panggil $ticket->talents dan $ticket->hargas
            'tickets' => $ticket->talents,
            'list'    => $ticket->hargas,
            'lists'   => $ticket->hargas,
            'jmlhQty' => $soldTickets
        ]);
    }

    public function listTransaksi()
    {
        $user = Auth::user();

        // Mengambil transaksi user dengan memanfaatkan relasi dan collection
        $transaksi = Cart::with(['event', 'hargaCarts'])
            ->where('user_uid', $user->uid)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($cart) {
                // Menghitung total quantity dan harga secara otomatis via Collection Laravel
                $cart->total_quantity = $cart->hargaCarts->sum('quantity');
                $cart->total_harga = $cart->hargaCarts->sum(function ($hc) {
                    return $hc->quantity * $hc->harga_ticket;
                });
                return $cart;
            });

        return view('frontend.page.transaksi.list-transaksi', [
            'title' => 'Transaksi',
            'transaksi' => $transaksi
        ]);
    }

    public function search($search = null)
    {
        // Siapkan query dasar
        $query = Event::with(['harga', 'user'])->where('konfirmasi', '1');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('event', 'LIKE', "%$search%")
                    ->orWhere('alamat', 'LIKE', "%$search%")
                    ->orWhere('slug', 'LIKE', "%$search%")
                    // Mencari berdasarkan nama talent menggunakan nama relasi yang baru: talents
                    ->orWhereHas('talents', function ($q2) use ($search) {
                        $q2->where('talent', 'LIKE', "%$search%");
                    });
            });
        }

        $events = $query->get();

        return view('frontend.page.post.post', [
            'title' => 'Search Event',
            'event' => $events,
        ]);
    }

    public function cari(Request $request)
    {
        // Best practice Laravel: gunakan class Request daripada $_GET native php
        $cari = $request->input('cari');
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
        return view('frontend.page.contact', ['title' => 'Contact']);
    }
}
