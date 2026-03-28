<?php

namespace App\Http\Controllers\Api;

use App\Models\Cart;
use App\Models\Cash;
use App\Models\User;
use App\Models\Event;
use App\Models\HargaCart;
use Illuminate\Http\Request;
// use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class ConfirmController extends Controller
{
    //
    public function cekData($data = null)
    {
        if ($data !== null) {
            $cart = Cart::join('harga_carts', 'harga_carts.uid', '=', 'carts.uid')
                ->select(['carts.uid', 'carts.user_uid', 'carts.event_uid', 'carts.invoice', 'carts.konfirmasi',  'carts.payment_type', 'carts.status', DB::raw('SUM(harga_carts.quantity) as qty')])
                ->where('carts.invoice', $data)
                ->groupBy('carts.uid', 'carts.user_uid', 'carts.event_uid', 'carts.invoice', 'carts.status', 'carts.konfirmasi', 'carts.payment_type')
                ->first();
            if ($cart !== null && $cart->status === 'SUCCESS') {
                if ($cart->payment_type === 'cash') {
                    $user = Cash::select(['uid', 'name'])->where('uid_user', $cart->user_uid)->first();
                } else {
                    $user = User::select(['uid', 'name'])->where('uid', $cart->user_uid)->first();
                }
                $event = Event::select(['event'])->where('uid', $cart->event_uid)->first();
                $harga = HargaCart::select(['quantity', 'kategori_harga'])->where('uid', $cart->uid)->get();
                $tes = [];
                foreach ($harga as $hargas) {
                    $tes[] = $hargas;
                }
                return response()->json([
                    'cart' => $cart,
                    'event' => $event,
                    'user' => $user,
                    'harga' => $tes
                ], 200);
            } else {
                echo "Akses Di Tolak";
            }
        } else {
            echo "Tidak Ada Data";
        }
    }

    public function upKonfirmasi(Request $request,  $data)
    {
        $req = $request->konfirmasi;
        // dd($req);
        if ($data !== null) {
            $cart = Cart::where('invoice', $data)->first();
            if ($cart->konfirmasi === null) {
                $cart->konfirmasi = "1";
                $cart->save();
                return response()->json([
                    'carts' => $cart
                ], 200);
                // return redirect('api/confirm/' . $data);
            } else {
                // Data tidak ditemukan, kirim respons 404
                return response()->json([
                    'message' => 'Data Tidak Ditemukan',
                ], 404);
            }
        } else {
            return response()->json([
                'message' => 'Invoice harus diberikan',
            ], 400);
        }
    }

    public function verfikasi($data)
    {
        $data;
        $cart = Cart::select(['carts.uid', 'carts.konfirmasi', 'events.event', 'users.name', 'users.gambar'])
            ->where('event_uid', $data)
            ->where('carts.konfirmasi', '1')
            ->join('events', 'events.uid', '=', 'carts.event_uid')
            ->join('users', 'users.uid', '=', 'carts.user_uid')
            ->get();
        if ($data !== null) {
            return response()->json([
                'cart' => $cart,

            ], 200);
        }
    }

    // FUNGSI 1: Hanya untuk cek data berdasarkan Invoice (Tanpa Update)
    public function checkTicketByInvoice(Request $request)
    {
        $request->validate([
            'invoice' => 'required|string',
        ]);

        $user = $request->user();
        $ownerId = ($user->role === 'staff') ? $user->parent_uid : $user->uid;

        // Cari tiket dengan relasi lengkap
        $ticket = Cart::with([
            'event:uid,event,cover',
            'users:uid,name,email',
            'hargaCarts:uid,kategori_harga,quantity'
        ])
            ->where('invoice', $request->invoice)
            ->whereHas('event', function ($query) use ($ownerId) {
                $query->where('user_uid', $ownerId);
            })
            ->first();

        // Validasi Keberadaan
        if (!$ticket) {
            return response()->json([
                'success' => false,
                'message' => 'Tiket tidak ditemukan atau Anda tidak memiliki akses!'
            ], 404);
        }

        // Validasi Pembayaran
        if ($ticket->status !== 'SUCCESS') {
            return response()->json([
                'success' => false,
                'message' => 'Tiket ini belum lunas (Status: ' . $ticket->status . ')'
            ], 422);
        }

        // Format data untuk ResultQrcode.vue
        return response()->json([
            'success' => true,
            'message' => 'Tiket ditemukan',
            'data' => [
                'uid' => $ticket->uid, // Penting untuk proses verifikasi nanti
                'invoice' => $ticket->invoice,
                'event_name' => $ticket->event->event ?? '-',
                'cover' => $ticket->event->cover ?? null,
                'buyer_name' => $ticket->users->name ?? '-',
                'email' => $ticket->users->email ?? '-',
                'order_date' => $ticket->created_at->format('d M Y H:i'),
                'konfirmasi' => $ticket->konfirmasi, // '0' atau '1'
                'status_label' => $ticket->konfirmasi == '1' ? 'Terverifikasi' : 'Belum Terverifikasi',
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

    // FUNGSI 2: Untuk update status konfirmasi (Dipanggil saat tombol "Verifikasi" diklik)
    public function confirmTicketStatus($uid)
    {
        $ticket = Cart::where('uid', $uid)->first();

        if (!$ticket) {
            return response()->json(['success' => false, 'message' => 'Tiket tidak ditemukan'], 404);
        }

        if ($ticket->konfirmasi == '1') {
            return response()->json(['success' => false, 'message' => 'Tiket sudah pernah digunakan sebelumnya!'], 409);
        }

        $ticket->konfirmasi = '1';
        $ticket->save();

        return response()->json([
            'success' => true,
            'message' => 'Check-in Berhasil! Selamat datang.',
        ], 200);
    }

    public function listEvent(Request $request)
    {
        // 1. Ambil data user yang sedang login dari token Sanctum
        $user = $request->user();

        // 2. Tentukan ID Pemilik Event (Penyewa)
        $ownerId = ($user->role === 'staff') ? $user->parent_uid : $user->uid;

        // 3. Ambil event + Hitung Total Tiket Terjual & Terverifikasi dalam 1 Kueri!
        $events = Event::where('user_uid', $ownerId)
            ->whereNotNull('konfirmasi')
            ->addSelect([
                // Menghitung TOTAL TIKET TERJUAL (Semua transaksi SUCCESS)
                'tiket_terjual' => Cart::selectRaw('COALESCE(SUM(harga_carts.quantity), 0)')
                    ->join('harga_carts', 'harga_carts.uid', '=', 'carts.uid')
                    ->whereColumn('carts.event_uid', 'events.uid')
                    ->where('carts.status', 'SUCCESS'),

                // Menghitung TOTAL TIKET TERVERIFIKASI (Sudah di-scan/Hadir)
                'tiket_terverifikasi' => Cart::selectRaw('COALESCE(SUM(harga_carts.quantity), 0)')
                    ->join('harga_carts', 'harga_carts.uid', '=', 'carts.uid')
                    ->whereColumn('carts.event_uid', 'events.uid')
                    ->where('carts.status', 'SUCCESS')
                    // PERHATIAN: Ganti 'Hadir' dengan string status yang kamu pakai saat tiket di-scan 
                    // (misal: 'Terverifikasi', 'Scanned', atau cek di kolom 'status'/'konfirmasi' kamu)
                    ->where('carts.konfirmasi', 1)
            ])
            ->orderBy('created_at', 'desc')
            ->get();

        // 4. Return Response
        if ($events->isNotEmpty()) {
            return response()->json([
                'success' => true,
                'message' => 'Berhasil mengambil daftar event',
                'data' => $events
            ], 200);
        } else {
            return response()->json([
                'success' => true,
                'message' => 'Belum ada event yang dikonfirmasi',
                'data' => []
            ], 200);
        }
    }
}
