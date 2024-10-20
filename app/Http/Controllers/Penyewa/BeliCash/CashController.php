<?php

namespace App\Http\Controllers\Penyewa\BeliCash;

use Exception;
use App\Models\Cart;
use App\Models\Cash;
use App\Models\User;
use App\Models\Event;
use App\Models\Harga;
use App\Models\HargaCart;
use App\Models\Transaction;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Jobs\sendEmailTrnsaksi;
use App\Mail\CashNotifikasiMail;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

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
            'alamat' => 'required|string',
            'ttl' => 'required|string',
            'total' => 'required|numeric',
            'gender' => 'required',
            'nomor' => 'required|numeric'
        ]);
        $validate->validate();

        $string = Str::random(3);
        $string2 = Str::random(2);
        $date = date('Ymd');
        $number = mt_rand(1000, 9999999999);
        $invoice = str_pad($string . $number, 10, '0', STR_PAD_LEFT);
        $str = Str::uuid();
        $order_id = 'CASH-' . $date . $invoice;

        // dd($str);
        $uid =  $request->uid;
        $partner =  $request->partner;
        $event =  $request->event;
        $ticket =  $request->ticket;
        $qty =  $request->qty;
        $nama =  $request->name;
        $email =  $request->email;
        $alamat =  $request->alamat;
        $ttl =  $request->ttl;
        $total =  $request->total;
        $gender = $request->gender;
        $nomor = $request->nomor;
        $konfirmasi = $request->konfirmasi;


        $events = Event::where('event', $event)->first();
        // dd($event);
        $kategoriTicket = Harga::where('uid', $events->uid)->where('kategori', $ticket)->first();

        $cart = new Cart([
            'uid' => $str,
            'user_uid' => $uid,
            'event_uid' => $events->uid,
            'invoice' => $order_id,
            'status' => 'SUCCESS',
            'konfirmasi' => $konfirmasi,
            'link' => null,
            'payment_type' => 'cash',
        ]);
        $hargaCart = new HargaCart([
            'orderBy' => '1',
            'uid' => $str,
            'event_uid' => $events->uid,
            'quantity' => $qty,
            'harga_ticket' => $kategoriTicket->harga,
            'kategori_harga' => $kategoriTicket->kategori,
        ]);
        // dd($hargaCart);
        $transaksi = new Transaction([
            'uid' => $str,
            'user_uid' => $uid,
            'event_uid' => $events->uid,
            'amount' => $total,
            'invoice' => $order_id,
            'payment_type' => 'cash',
            'status_transaksi' => 'SUCCESS'
        ]);

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
        if (!$cekEmail) {
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
                'password' => "12345678"
            ]);
        }

        try {
            // Mail::to($email)->send(new CashNotifikasiMail($nama, $order_id));
            $send = new sendEmailTrnsaksi($email, $nama, $order_id, $event);
            dispatch($send);
            $cart->save();
            $hargaCart->save();
            $cash->save();
            $transaksi->save();
            return redirect()->back()->with('success', 'Pembelian Cash Berhasil');
        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
