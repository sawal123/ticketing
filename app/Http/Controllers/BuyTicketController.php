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
        // Ambil Internet Fee (Gunakan yang sudah disimpan di DB jika ada, agar tetap muncul setelah transaksi)
        if (isset($cart->internet_fee) && $cart->internet_fee > 0) {
            $selectInternetFee = $cart->internet_fee;
        } else {
            if ($iFee) {
                // Jalur Normal (Data baru atau iFee ketemu)
                if ($iFee->biaya_type === 'persen') {
                    $selectInternetFee = ($iFee->biaya / 100) * $jumlah;
                } else {
                    $selectInternetFee = $iFee->biaya;
                }
            } else {
                // LOGIC FALLBACK UNTUK TRANSAKSI LAMA (PRODUCTION)
                if ($cart->status !== 'UNPAID') {
                    // Cek berdasarkan payment_type Midtrans (Generic)
                    if (str_contains($cart->payment_type, 'qris') || str_contains($cart->payment_type, 'gopay')) {
                        $selectInternetFee = round(0.05 * $jumlah); // 5% untuk e-wallet/qris
                    } elseif (str_contains($cart->payment_type, 'transfer') || str_contains($cart->payment_type, 'va') || str_contains($cart->payment_type, 'echannel')) {
                        $selectInternetFee = 7200; // Flat Rp 7.200 untuk bank transfer/VA
                    } elseif ($cart->payment_type === 'cash') {
                        $selectInternetFee = 0;
                    } else {
                        // Default fallback jika tidak ada yang cocok
                        $selectInternetFee = 0;
                    }
                } else {
                    $selectInternetFee = 0;
                }
            }
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

        // Ambil Pajak (Fee) dari tabel Event (Gunakan yang sudah disimpan di DB jika transaksi sudah diproses)
        if ($cart->status !== 'UNPAID' && isset($cart->pajak)) {
            $nilaiPajak = $cart->pajak;
            $pajakPersen = $cart->pajak_persen;
        } else {
            $eventFee = $event->fee ?? 0;
            if ($eventFee > 100) {
                // Legacy nominal fee (Pajak/Fee Rupiah)
                $pajakPersen = 0;
                $nilaiPajak = $eventFee;
            } else {
                // New percentage tax
                $pajakPersen = $eventFee;
                $nilaiPajak = ($pajakPersen / 100) * $subtotal;
            }
        }

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
        $request->validate([
            'eventUid' => 'required|exists:events,uid',
        ]);

        $event = Event::where('uid', $request->eventUid)->firstOrFail();

        // 1. Kumpulkan data tiket dari input (ticket0, ticket1, dst)
        $selectedTickets = [];
        $totalRequestedQty = 0;

        for ($i = 0; $i < 10; $i++) {
            $qty = (int) $request->input("ticket$i", 0);
            if ($qty > 0) {
                $selectedTickets[] = [
                    'qty' => $qty,
                    'harga' => $request->input("harga$i"),
                    'kategori' => $request->input("kategori$i"),
                    'orderBy' => $request->input("orderBy$i", $i + 1),
                ];
                $totalRequestedQty += $qty;
            }
        }

        // 2. Validasi: Minimal 1 tiket, Maksimal 5 tiket secara keseluruhan
        if ($totalRequestedQty <= 0) {
            return redirect()->back()->with('error', 'Harap pilih minimal 1 tiket!');
        }

        if ($totalRequestedQty > 5) {
            return redirect()->back()->with('error', 'Maksimal total pemesanan adalah 5 tiket.');
        }

        // 3. Validasi Stok per Kategori
        foreach ($selectedTickets as $item) {
            $masterHarga = Harga::where('uid', $event->uid)
                ->where('kategori', trim($item['kategori']))
                ->first();

            if (! $masterHarga) {
                return redirect()->back()->with('error', 'Kategori tiket tidak valid.');
            }

            // Hitung tiket terjual (Status SUCCESS)
            $soldCount = HargaCart::where('event_uid', $event->uid)
                ->where('kategori_harga', $masterHarga->kategori)
                ->whereHas('cart', function ($q) {
                    $q->where('status', 'SUCCESS');
                })
                ->sum('quantity');

            if (($soldCount + $item['qty']) > $masterHarga->qty) {
                return redirect()->back()->with('error', "Stok tiket {$masterHarga->kategori} tidak mencukupi!");
            }

            // Tambahkan ID master harga ke array data
            $item['harga_id'] = $masterHarga->id;
        }

        // 4. Manajemen Cart (Buat baru atau gunakan UNPAID yang ada)
        $cart = Cart::where('event_uid', $event->uid)
            ->where('user_uid', Auth::user()->uid)
            ->where('status', 'UNPAID')
            ->first();

        if (! $cart) {
            $invoice = 'INV-'.Str::random(3).mt_rand(1000, 99999999);
            $cart = Cart::create([
                'uid' => Str::uuid(),
                'user_uid' => Auth::user()->uid,
                'event_uid' => $event->uid,
                'invoice' => $invoice,
                'status' => 'UNPAID',
            ]);
        } else {
            // Jika ada cart UNPAID, bersihkan detail lamanya agar sesuai dengan pilihan baru
            HargaCart::where('uid', $cart->uid)->delete();
        }

        // 5. Simpan Detail Tiket (HargaCart)
        foreach ($selectedTickets as $item) {
            $masterHarga = Harga::where('uid', $event->uid)
                ->where('kategori', trim($item['kategori']))
                ->first();

            HargaCart::create([
                'uid' => $cart->uid,
                'orderBy' => $item['orderBy'],
                'event_uid' => $event->uid,
                'harga_id' => $masterHarga->id,
                'quantity' => $item['qty'],
                'harga_ticket' => $item['harga'],
                'kategori_harga' => $item['kategori'],
                'voucher' => null,
                'disc' => 0,
            ]);
        }

        return redirect('/detail-ticket/'.$cart->uid.'/'.Auth::user()->uid);
    }
}
