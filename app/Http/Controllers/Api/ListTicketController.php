<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use Illuminate\Http\Request;

class ListTicketController extends Controller
{
    public function listTiketVerifikasi(Request $request, $event_uid)
    {
        // 1. Ambil data transaksi yang status konfirmasinya sudah '1' (Terverifikasi)
        // Kita tambahkan subtotal quantity tiket dari tabel harga_carts
        // dd($event_uid);
        $verifiedTickets = Cart::select([
            'carts.uid',
            'carts.invoice',
            'carts.konfirmasi',
            'carts.updated_at as waktu_verifikasi',
            'events.event as event_name',
            'events.cover as event_cover',
            'users.name as user_name',
            'users.gambar as user_image'
        ])
            ->join('events', 'events.uid', '=', 'carts.event_uid')
            ->join('users', 'users.uid', '=', 'carts.user_uid')
            ->where('carts.event_uid', $event_uid)
            ->where('carts.konfirmasi', '1') // Pastikan '1' adalah flag untuk 'Terverifikasi'
            ->where('carts.status', 'SUCCESS')
            // Menjumlahkan quantity tiket dari relasi hargaCarts
            ->withSum('hargaCarts as total_qty', 'quantity')
            ->orderBy('carts.updated_at', 'desc') // Tampilkan yang baru di-scan di urutan teratas
            ->get();

        // 2. Berikan Response JSON
        if ($verifiedTickets->isNotEmpty()) {
            return response()->json([
                'success' => true,
                'message' => 'Data tiket terverifikasi ditemukan',
                'data' => $verifiedTickets
            ], 200);
        }

        return response()->json([
            'success' => true,
            'message' => 'Belum ada tiket yang terverifikasi untuk event ini',
            'data' => []
        ], 200);
    }

    public function showTicketDetail($uid)
    {
        // Ambil detail cart beserta relasi yang diperlukan
        $ticket = Cart::with([
            'event:uid,event,cover', // Ambil nama event
            'users:uid,name,email', // Ambil nama & email pemesan
            'hargaCarts:uid,kategori_harga,quantity' // Ambil rincian jenis tiket & qty
        ])
            ->select([
                'uid',
                'user_uid',
                'event_uid',
                'invoice',
                'konfirmasi',
                'created_at',
                'status'
            ])
            ->where('uid', $uid)
            ->first();

        if (!$ticket) {
            return response()->json([
                'success' => false,
                'message' => 'Data tiket tidak ditemukan'
            ], 404);
        }

        // Format data agar mudah dibaca di Vue.js
        return response()->json([
            'success' => true,
            'message' => 'Detail tiket berhasil dimuat',
            'data' => [
                'invoice' => $ticket->invoice,
                'event_name' => $ticket->event->event ?? '-',
                'event_cover' => $ticket->event->cover ?? '-',
                'buyer_name' => $ticket->users->name ?? '-',
                'email' => $ticket->users->email ?? '-',
                'order_date' => $ticket->created_at->format('d M Y H:i'),
                'status_verifikasi' => $ticket->konfirmasi == '1' ? 'Terverifikasi' : 'Belum Terverifikasi',
                'ticket_items' => $ticket->hargaCarts->map(function ($item) {
                    return [
                        'jenis_tiket' => $item->kategori_harga,
                        'qty' => $item->quantity
                    ];
                }),
                'total_qty' => $ticket->hargaCarts->sum('quantity')
            ]
        ], 200);
    }
}
