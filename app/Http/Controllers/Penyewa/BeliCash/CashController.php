<?php

namespace App\Http\Controllers\Penyewa\BeliCash;

use App\Http\Controllers\Controller;
use App\Jobs\sendEmailTrnsaksi;
use App\Models\Cart;
use App\Models\Cash;
use App\Models\Event;
use App\Models\Harga;
use App\Models\HargaCart;
use App\Models\Transaction;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class CashController extends Controller
{
    public function createCash(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'uid' => 'string|required',
            'event' => 'string|required|max:255',
            'ticket' => 'string|required',
            'qty' => 'numeric|required',
            'name' => 'required|string',
            'email' => 'required|email',
            'alamat' => 'nullable|string',
            'ttl' => 'required|string',
            'total' => 'nullable|numeric',
            'gender' => 'required',
            'nomor' => 'nullable|numeric',
        ]);
        $validate->validate();

        $string = Str::random(3);
        $string2 = Str::random(2);
        $date = date('Ymd');
        $number = mt_rand(1000, 9999999999);
        $invoice = str_pad($string.$number, 10, '0', STR_PAD_LEFT);
        $str = Str::uuid();
        $order_id = 'CASH-'.$date.$invoice;

        $uid = $request->uid;
        $partner = $request->partner;
        $event_name = $request->event;
        $ticket_name = $request->ticket;
        $qty = (int) $request->qty;
        $nama = $request->name;
        $email = $request->email;
        $alamat = $request->alamat ?? '-';
        $ttl = $request->ttl;
        $total = $request->total ?? 0;
        $gender = $request->gender;
        $nomor = $request->nomor ?? '080000000000';
        $konfirmasi = $request->konfirmasi;

        // 1. Ambil Data Event untuk dapet % Pajak
        $events = Event::where('event', $event_name)->first();
        if (! $events) {
            return back()->with('error', 'Event tidak ditemukan');
        }

        // 2. Ambil Harga Tiket
        $kategoriTicket = Harga::where('uid', $events->uid)
            ->where('kategori', $ticket_name)
            ->first();

        // 3. LOGIKA HITUNG ULANG PAJAK (BACKEND)
        $subtotal = $kategoriTicket->harga * $qty;
        $pajakPersen = $events->fee ?? 0;
        $nilaiPajak = ($pajakPersen / 100) * $subtotal;
        $totalFinal = $subtotal + $nilaiPajak;

        $str = Str::uuid();
        $date = date('Ymd');
        $invoice = 'CASH-'.$date.Str::upper(Str::random(10));

        $cart = new Cart([
            'uid' => $str,
            'user_uid' => $uid,
            'event_uid' => $events->uid,
            'invoice' => $invoice,
            'status' => 'SUCCESS',
            'konfirmasi' => $konfirmasi,
            'payment_type' => 'cash',
        ]);

        $hargaCart = new HargaCart([
            'orderBy' => '1',
            'uid' => $str,
            'event_uid' => $events->uid,
            'quantity' => $qty,
            'harga_ticket' => $kategoriTicket->harga,
            'kategori_harga' => $kategoriTicket->kategori,
            // Jika tabel HargaCart punya kolom disc/tax, simpan di sana juga
        ]);

        $transaksi = new Transaction([
            'uid' => $str,
            'user_uid' => $uid,
            'event_uid' => $events->uid,
            'amount' => $totalFinal, // SIMPAN TOTAL YANG SUDAH TERMASUK PAJAK
            'invoice' => $invoice,
            'payment_type' => 'cash',
            'status_transaksi' => 'SUCCESS',
        ]);

        // ... (Proses simpan Cash, User, dan Email tetap sama)

        $cash = new Cash([
            'uid' => $str,
            'uid_partner' => $partner,
            'uid_user' => $uid,
            'uid_event' => $events->uid,
            'name' => $nama,
            'email' => $email,
            'nomor' => $nomor,
            'alamat' => $alamat,
            'lahir' => $ttl,
            'gender' => $gender,
        ]);

        $cekEmail = User::where('email', $email)->first();
        // dd($cekEmail);
        if (! $cekEmail) {
            $user = User::create([
                'uid' => $str,
                'name' => $nama,
                'email' => $email,
                'nomor' => $nomor,
                'birthday' => $ttl,
                'alamat' => $alamat,
                'kota' => $alamat,
                'gender' => $gender,
                'gambar' => '',
                'role' => User::USER_ROLE,
                'password' => '12345678',
            ]);
        } else {
            $user = $cekEmail;
        }

        try {
            DB::beginTransaction();
            $cart->save();
            $hargaCart->save();
            $transaksi->save();
            // save cash & user...
            $cash->save();
            DB::commit();

            $send = new sendEmailTrnsaksi($user, $cart, $invoice);
            dispatch($send);

            return redirect()->back()->with('success', 'Pembelian Cash Berhasil (Termasuk Pajak '.$pajakPersen.'%)');
        } catch (Exception $e) {
            DB::rollback();

            return back()->with('error', $e->getMessage());
        }
    }
}
