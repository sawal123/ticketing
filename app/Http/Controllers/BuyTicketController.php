<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\CartVoucher;
use App\Models\Event;
use App\Models\Harga;
use App\Models\HargaCart;
use App\Models\PaymentGateway;
use App\Models\Voucher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class BuyTicketController extends Controller
{
    protected $data_pay = 1;

    protected function payment()
    {
        $this->data_pay = PaymentGateway::where('is_active', '1')->get();
    }

    public function index($uid, $user)
    {
        error_reporting(0);
        $this->payment();
        // dd($this->data_pay);
        // ?order_id=INV-05484&status_code=200&transaction_status=settlement
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
        $cartV = CartVoucher::where('uid', $cart->uid)->first();
        $voucher = Voucher::where('code', $cartV->code)->first();
        // dd($voucher);

        $iFee = PaymentGateway::where('slug', $cart->payment_type)->first();

        $counts = [];
        foreach ($harga as $count) {
            $counts[] = $count->harga_ticket * $count->quantity;
        }
        $jumlah = array_sum($counts);
        // dd($jumlah);
        if ($iFee->biaya_type === 'persen') {
            $selectInternetFee = ($iFee->biaya / 100) * $jumlah;
        } else {
            $selectInternetFee = $iFee->biaya;
        }
        // dd($selectInternetFee);
        $diskon = 0;
        if ($voucher->unit === 'rupiah') {
            $diskon = $voucher->nominal;
        } elseif ($voucher->unit === 'persen') {
            $diskon = ($voucher->nominal / 100) * $jumlah;
        }
        // dd($diskon);

        // 1. Hitung Subtotal (Jumlah Tiket - Diskon)
        $subtotal = $jumlah - $diskon;

        // 2. Ambil Pajak (Fee) dari tabel Event (asumsi dalam persen)
        $pajakPersen = $event->fee ?? 0;
        $nilaiPajak = ($pajakPersen / 100) * $subtotal;

        return view('frontend.page.bayartiket', [
            'title' => 'Detail Ticket',
            'event' => $event,
            'harga' => $harga,
            'cart' => $cart,
            'total' => $jumlah,
            'uid' => $uid,
            'voucher' => $voucher,
            'payment' => $this->data_pay,
            'selectInternetFee' => $selectInternetFee,
            'iFee' => $iFee,
            'diskon' => $diskon,
            'pajakPersen' => $pajakPersen, // Kirim persen pajak
            'nilaiPajak' => $nilaiPajak,   // Kirim nominal pajak
            'subtotal' => $subtotal,
        ]);
    }

    public function checkVoucher(Request $request)
    {

        $vali = Validator::make($request->all(), [
            'code' => 'required|alpha_num',
        ]);
        $code = $request->code;
        $cart = $request->cartUid;
        $event = $request->event;
        // $cekEvent = Event::

        $cVoucher = CartVoucher::where('uid', $cart)->first();
        $voucher = Voucher::where('code', $code)->first();
        $carts = HargaCart::where('uid', $cart)->first();
        // dd($voucher->event_uid .' + ' . $event);
        if ($voucher->event_uid !== $event) {
            return redirect()->back()->with('vError', 'Voucher '.$code.' Invalid');
        }
        if ($code === null && $cVoucher) {
            $cVoucher->code = '';
            $carts->voucher = null;
            $carts->disc = '0';
            $cVoucher->save();
            $carts->save();

            return redirect()->back()->with('voucher', 'Voucher dihapus');
        }
        if ($voucher) {
            if ($cVoucher) {
                $cVoucher->code = $code;
                $carts->voucher = $code;
                $carts->disc = $voucher->nominal;
                $cVoucher->save();
                $carts->save();

                return redirect()->back()->with('voucher', 'Voucher berhasil digunakan');
            }
            // dd($voucher->digunakan);
            if ($voucher->digunakan < $voucher->limit) {
                $cartV = new CartVoucher([
                    'uid' => $cart,
                    'uid_vouchers' => $voucher->uid,
                    'user_uid' => Auth::user()->uid,
                    'event_uid' => $request->event,
                    'code' => $code,
                ]);
                $cartV->save();

                return redirect()->back()->with('voucher', 'Voucher berhasil digunakan');
            } else {
                // dd('Tidak Ada Voucher');
                return redirect()->back()->with('vError', 'Voucher Expired');
            }
        } else {
            return redirect()->back()->with('vError', 'Voucher '.$code.' Invalid');
        }
    }

    public function closeVoucher(Request $request)
    {
        $vali = Validator::make($request->all(), [
            'code' => 'required|alpha_num',
        ]);
        $code = $request->code;
        $cart = $request->cartUid;

        $cVoucher = CartVoucher::where('uid', $cart)->first();
        $voucher = Voucher::where('code', $code)->first();
        $carts = HargaCart::where('uid', $cart)->first();
        // dd($cVoucher);
        $cVoucher->code = '';
        $cVoucher->save();

        return redirect()->back()->with('voucher', 'Voucher berhasil dihapus!');
    }

    public function checkout(Request $request)
    {
        // dd($request);
        error_reporting(0);
        $kode = Str::uuid();
        $str = Str::random(3);
        $number = mt_rand(1000, 9999999999);
        $invoice = str_pad($str.$number, 10, '0', STR_PAD_LEFT);
        $ticketValue = [];
        $hargaValue = [];
        $kategoriValue = [];
        $orderByInput = [];
        $req = [];

        for ($i = 0; $i < 10; $i++) {
            $ticketValue[] = $request->input('ticket'.$i);
            $hargaValue[] = $request->input('harga'.$i);
            $kategoriValue[] = $request->input('kategori'.$i);
            $orderByInput[] = $request->input('orderBy'.$i);
        }

        $ticketValue = array_filter($ticketValue);
        $hargaValue = array_filter($hargaValue);
        $kategoriValue = array_filter($kategoriValue);
        $orderByInput = array_filter($orderByInput);

        if ($ticketValue == [] || $hargaValue == [] || $kategoriValue == []) {
            return redirect()->back()->with('error', 'Harap pilih ticket anda!');
        } else {
            $hargaCartsArray = [];
            $event = Event::where('uid', $request->eventUid)->first();
            $carts = Cart::where('event_uid', $event->uid)->where('user_uid', Auth::user()->uid)->first();

            $harga_ticket = Harga::where('uid', $event->uid)->get();

            foreach ($kategoriValue as $index => $value) {
                $hargaCarts = HargaCart::join('carts', 'carts.uid', '=', 'harga_carts.uid')
                    ->select('carts.uid', 'carts.status', 'harga_carts.quantity', 'harga_carts.kategori_harga')
                    ->where('carts.status', '=', 'SUCCESS')
                    ->where('harga_carts.kategori_harga', $harga_ticket[$index]->kategori)
                    ->get();
                $totalQuantity = $hargaCarts->sum('quantity');
                $hargaCartsArray[$harga_ticket[$index]->kategori] = $totalQuantity;
            }

            $cek = 0;
            $cekLagi = [];
            $arrayHarga = [];
            foreach ($harga_ticket as $index => $harga_t) {
                $HargaIndex = array_keys($hargaCartsArray);
                if ($harga_t->kategori === $HargaIndex[$index]) {
                    $cek = $ticketValue[$index] + $hargaCartsArray[$harga_t->kategori];
                    if ($cek <= $harga_t->qty) {

                    } else {
                        return redirect()->back()->with('error', 'Ticket Tidak Cukup!');
                    }
                    $cekLagi[] = $cek;
                }
            }
        }

        if ($carts && $carts->status === 'UNPAID') { // Tambahkan cek $carts agar tidak error jika null
            $hargaCart = HargaCart::where('uid', $carts->uid)->orderBy('orderBy')->get();
            $hargaArray = [];
            foreach ($hargaCart as $hargaCarts) {
                $hargaArray[] = $hargaCarts;
            }

            foreach ($ticketValue as $index => $value) {
                $harga = $hargaValue[$index] ?? null;
                // Gunakan trim() untuk membersihkan spasi kiri/kanan yang tidak sengaja terketik
                $kategori = isset($kategoriValue[$index]) ? trim($kategoriValue[$index]) : null;
                $orderBy = $orderByInput[$index];

                // <--- PERBAIKAN: Cari langsung ke DB dengan kategori yang sudah di-trim --->
                $masterHarga = Harga::where('uid', $event->uid)
                    ->where('kategori', $kategori)
                    ->first();

                $hargaId = $masterHarga ? $masterHarga->id : null;
                // <----------------------------------------------------------->
                // dd([
                //     'kategori_dari_form' => $kategori,
                //     'id_yang_ditemukan' => $hargaId
                // ]);

                if ($hargaCarts->kategori_harga == $kategori) {
                    $newQuantity = $hargaCarts->quantity + $value;
                    $up = $hargaCarts->update(['quantity' => $newQuantity]);

                    return redirect('/detail-ticket/'.$carts->uid.'/'.Auth::user()->uid);
                }

                if (isset($hargaArray[$index])) {
                    if ($hargaArray[$index]->kategori_harga === $kategori) {
                        $existingQuantity = $hargaArray[$index]->quantity;
                        $newQuantity = $existingQuantity + $value;
                        $up = $hargaArray[$index]->update(['quantity' => $newQuantity]);
                    } else {
                        HargaCart::create([
                            'uid' => $carts->uid,
                            'orderBy' => $orderBy,
                            'event_uid' => $event->uid,
                            'harga_id' => $hargaId, // <--- TAMBAHAN DI SINI
                            'quantity' => $value,
                            'harga_ticket' => $harga,
                            'voucher' => null,
                            'disc' => '0',
                            'kategori_harga' => $kategori,
                        ]);
                    }
                } else {
                    HargaCart::create([
                        'uid' => $carts->uid,
                        'orderBy' => $orderBy,
                        'event_uid' => $event->uid,
                        'harga_id' => $hargaId, // <--- TAMBAHAN DI SINI
                        'quantity' => $value,
                        'harga_ticket' => $harga,
                        'voucher' => null,
                        'disc' => '0',
                        'kategori_harga' => $kategori,
                    ]);
                }
            }

            return redirect('/detail-ticket/'.$carts->uid.'/'.Auth::user()->uid);

        } else {
            Cart::create([
                'uid' => $kode,
                'user_uid' => Auth::user()->uid,
                'event_uid' => $event->uid,
                'invoice' => 'INV-'.$invoice,
                'status' => 'UNPAID',
            ]);

            foreach ($ticketValue as $index => $value) {
                $harga = $hargaValue[$index] ?? null;
                $kategori = $kategoriValue[$index] ?? null;
                $orderBy = $orderByInput[$index];

                // <--- TAMBAHAN: Cari ID Master Harga berdasarkan kategori --->
                $masterHarga = $harga_ticket->firstWhere('kategori', $kategori);
                $hargaId = $masterHarga ? $masterHarga->id : null;
                // <----------------------------------------------------------->

                HargaCart::create([
                    'uid' => $kode,
                    'orderBy' => $orderBy,
                    'event_uid' => $event->uid,
                    'harga_id' => $hargaId, // <--- TAMBAHAN DI SINI
                    'quantity' => $value,
                    'harga_ticket' => $harga,
                    'voucher' => null,
                    'disc' => '0',
                    'kategori_harga' => $kategori,
                ]);
            }

            return redirect('/detail-ticket/'.$kode.'/'.Auth::user()->uid);
        }
    }
}
