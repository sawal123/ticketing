<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Harga;
use App\Models\HargaCart;
use Illuminate\Support\Facades\Auth;

class BuyTicketController extends Controller
{
    public function index($uid, $user)
    {
        $cart = Cart::where('uid', $uid)->where('user_uid', $user)->first();
        // dd($cart);
        if ($cart == null) {
            return redirect('/');
        }
        if ($user != Auth::user()->uid) {
            return redirect('/');
        }
        $event = Event::where('uid', $cart->event_uid)->first();
        $harga = HargaCart::where('uid', $cart->uid)->get();
        // dd($harga);
        $counts = [];
        foreach ($harga as $count) {
            $counts[] = $count->harga_ticket * $count->quantity;
        }
        $jumlah = array_sum($counts);
        $fee = '5';
        return view('frontend.page.bayartiket', [
            'title' => 'Detail Ticket',
            'event' => $event,
            'harga' => $harga,
            'cart' => $cart,
            'total' => $jumlah,
            'fee' => $fee,
            'uid' => $uid
        ]);
    }


    public function checkout(Request $request)
    {
        error_reporting(0);
        $kode = Str::random(10);
        $number = mt_rand(1000, 9999);
        $invoice = str_pad($number, 5, '0', STR_PAD_LEFT);
        $ticketValue = [];
        $hargaValue = [];
        $kategoriValue = [];
        $orderByInput = [];
        $req = [];
        for ($i = 0; $i < 5; $i++) {
            $ticketValue[] = $request->input('ticket' . $i);
            $hargaValue[] = $request->input('harga' . $i);
            $kategoriValue[] = $request->input('kategori' . $i);
            $orderByInput[] = $request->input('orderBy' . $i);
        }
        
        $ticketValue = array_filter($ticketValue);
        $hargaValue = array_filter($hargaValue);
        $kategoriValue = array_filter($kategoriValue);
        $orderByInput = array_filter($orderByInput);


        if ($ticketValue == [] || $hargaValue == [] ||  $kategoriValue == []) {
            return redirect()->back()->with('error', 'Harap pilih ticket anda!');
        } else {

            $event = Event::where('uid', $request->eventUid)->first();
            $carts = Cart::where('event_uid', $event->uid)->where('user_uid', Auth::user()->uid)->first();
        }

// dd($carts->status);
        if ($carts->status === 'Belum Bayar') {
            $hargaCart = HargaCart::where('uid', $carts->uid)->orderBy('orderBy')->get();
            $hargaArray = [];
            foreach ($hargaCart as $hargaCarts) {
                $hargaArray[] = $hargaCarts;
            }
            foreach ($ticketValue as $index => $value) {
                $harga = $hargaValue[$index] ?? null; // Mengambil harga sesuai indeks
                $kategori = $kategoriValue[$index] ?? null; // Mengambil kategori sesuai indeks
                $orderBy = $orderByInput[$index];
                if ($hargaCarts->kategori_harga == $kategori) {
                    $newQuantity = $hargaCarts->quantity + $value; // Menambahkan quantity baru
                    $up = $hargaCarts->update(['quantity' => $newQuantity]); // Update ke database
                    return redirect('/detail-ticket/' . $carts->uid . '/' . Auth::user()->uid);
                }
                if (isset($hargaArray[$index])) {
                    if ($hargaArray[$index]->kategori_harga === $kategori) {
                        // Update jumlah quantity jika sudah ada entri dengan harga yang sama
                        $existingQuantity = $hargaArray[$index]->quantity; // Jumlah quantity yang sudah ada
                        $newQuantity = $existingQuantity + $value; // Menambahkan quantity baru
                        $up = $hargaArray[$index]->update(['quantity' => $newQuantity]); // Update ke database
                    } else {
                        HargaCart::create([
                            'uid' => $carts->uid,
                            'orderBy' => $orderBy,
                            'event_uid' => $event->uid,
                            'quantity' => $value,
                            'harga_ticket' => $harga,
                            'kategori_harga' => $kategori
                        ]);
                    }
                } else {
                    HargaCart::create([
                        'uid' => $carts->uid,
                        'orderBy' => $orderBy,
                        'event_uid' => $event->uid,
                        'quantity' => $value,
                        'harga_ticket' => $harga,
                        'kategori_harga' => $kategori
                    ]);
                }
            }
            return redirect('/detail-ticket/' . $carts->uid . '/' . Auth::user()->uid);
        } else {
            Cart::create([
                'uid' => $kode,
                'user_uid' => Auth::user()->uid,
                'event_uid' => $event->uid,
                'invoice' => 'INV-' . $invoice,
                'status' => 'Belum Bayar'
            ]);

            foreach ($ticketValue as $index => $value) {
                $harga = $hargaValue[$index] ?? null; // Mengambil harga sesuai indeks
                $kategori = $kategoriValue[$index] ?? null; // Mengambil kategori sesuai indeks
                $orderBy = $orderByInput[$index];
                HargaCart::create([
                    'uid' => $kode,
                    'orderBy' => $orderBy,
                    'event_uid' => $event->uid,
                    'quantity' => $value,
                    'harga_ticket' => $harga,
                    'kategori_harga' => $kategori
                ]);
            }
            return redirect('/detail-ticket/' . $kode . '/' . Auth::user()->uid);
        }
    }
}
