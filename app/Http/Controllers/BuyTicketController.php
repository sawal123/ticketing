<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\CartVoucher;
use App\Models\Event;
use App\Models\HargaCart;
use App\Models\PaymentGateway;
use App\Models\Voucher;
use App\Services\Tickets\TicketPricingService;
use App\Services\Tickets\TicketReservationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

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
        $voucher = $cartV && $cartV->code ? Voucher::where('code', $cartV->code)->first() : null;
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
                if ($cart->status !== Cart::STATUS_UNPAID) {
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
        $diskon = app(TicketPricingService::class)->calculateVoucherDiscount($cart, $jumlah);
        // dd($diskon);

        // 1. Hitung Subtotal (Jumlah Tiket - Diskon)
        $subtotal = $jumlah - $diskon;

        // Ambil Pajak (Fee) dari tabel Event (Gunakan yang sudah disimpan di DB jika transaksi sudah diproses)
        if (in_array($cart->status, [Cart::STATUS_PENDING, Cart::STATUS_SUCCESS], true) && isset($cart->pajak)) {
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

        $request->validate([
            'code' => 'required|alpha_num',
            'cartUid' => 'required',
        ]);
        $code = $request->code;
        $cart = $request->cartUid;

        $cartModel = Cart::where('uid', $cart)
            ->where('user_uid', Auth::user()->uid)
            ->whereIn('status', [Cart::STATUS_RESERVED, Cart::STATUS_PENDING, Cart::STATUS_UNPAID])
            ->first();

        if (! $cartModel || $cartModel->isReservationExpired()) {
            return redirect()->back()->with('vError', 'Reservation sudah expired atau cart tidak valid.');
        }

        $voucher = Voucher::where('code', $code)
            ->where('event_uid', $cartModel->event_uid)
            ->where('status', 'active')
            ->first();

        if (! $voucher) {
            return redirect()->back()->with('vError', 'Voucher '.$code.' Invalid');
        }

        $pricing = app(TicketPricingService::class)->calculateCart($cartModel);
        if ($pricing['ticket_total'] < (int) $voucher->min_beli) {
            return redirect()->back()->with('vError', 'Minimal pembelian voucher belum terpenuhi.');
        }

        if ((int) $voucher->digunakan >= (int) $voucher->limit) {
            return redirect()->back()->with('vError', 'Voucher Expired');
        }

        $cVoucher = CartVoucher::where('uid', $cart)->first();
        $carts = HargaCart::where('uid', $cart)->orderBy('id')->first();

        if ($code === null && $cVoucher) {
            $cVoucher->code = '';
            if ($carts) {
                $carts->voucher = null;
                $carts->disc = 0;
                $carts->save();
            }
            $cVoucher->save();

            return redirect()->back()->with('voucher', 'Voucher dihapus');
        }

        if ($cVoucher) {
            $cVoucher->code = $code;
            $cVoucher->uid_vouchers = $voucher->uid;
            $cVoucher->save();
        } else {
            CartVoucher::create([
                'uid' => $cart,
                'uid_vouchers' => $voucher->uid,
                'user_uid' => Auth::user()->uid,
                'event_uid' => $cartModel->event_uid,
                'code' => $code,
            ]);
        }

        if ($carts) {
            $carts->voucher = $code;
            $carts->disc = app(TicketPricingService::class)->calculateVoucherDiscount($cartModel, $pricing['ticket_total']);
            $carts->save();
        }

        return redirect()->back()->with('voucher', 'Voucher berhasil digunakan');
    }

    public function closeVoucher(Request $request)
    {
        $request->validate([
            'cartUid' => 'required',
        ]);

        $cart = Cart::where('uid', $request->cartUid)
            ->where('user_uid', Auth::user()->uid)
            ->whereIn('status', [Cart::STATUS_RESERVED, Cart::STATUS_PENDING, Cart::STATUS_UNPAID])
            ->first();

        if (! $cart || $cart->isReservationExpired()) {
            return redirect()->back()->with('vError', 'Reservation sudah expired atau cart tidak valid.');
        }

        $cVoucher = CartVoucher::where('uid', $cart->uid)->first();
        if ($cVoucher) {
            $cVoucher->code = '';
            $cVoucher->save();
        }

        HargaCart::where('uid', $cart->uid)->update([
            'voucher' => null,
            'disc' => 0,
        ]);

        return redirect()->back()->with('voucher', 'Voucher berhasil dihapus!');
    }

    public function checkout(Request $request, TicketReservationService $reservationService)
    {
        $eventUid = $request->input('event_uid', $request->input('eventUid'));

        $request->merge(['event_uid' => $eventUid]);
        $request->validate([
            'event_uid' => 'required|exists:events,uid',
        ]);

        $items = $this->parseCheckoutItems($request);
        $totalRequestedQty = collect($items)->sum('quantity');

        if ($totalRequestedQty <= 0) {
            return redirect()->back()->with('error', 'Harap pilih minimal 1 tiket!');
        }

        if ($totalRequestedQty > 5) {
            return redirect()->back()->with('error', 'Maksimal total pemesanan adalah 5 tiket.');
        }

        $event = Event::where('uid', $eventUid)->firstOrFail();

        $activeCart = Cart::where('event_uid', $event->uid)
            ->where('user_uid', Auth::user()->uid)
            ->whereIn('status', Cart::ACTIVE_RESERVATION_STATUSES)
            ->where(function ($query) {
                $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->first();

        if ($activeCart) {
            return redirect('/detail-ticket/'.$activeCart->uid.'/'.Auth::user()->uid)
                ->with('error', 'Anda masih memiliki reservation aktif untuk event ini.');
        }

        try {
            $cart = $reservationService->reserve($event, Auth::user()->uid, $items);
        } catch (ValidationException $exception) {
            return redirect()->back()->with('error', collect($exception->errors())->flatten()->first());
        }

        return redirect('/detail-ticket/'.$cart->uid.'/'.Auth::user()->uid);
    }

    protected function parseCheckoutItems(Request $request): array
    {
        $items = [];

        foreach ((array) $request->input('tickets', []) as $index => $ticket) {
            $quantity = (int) ($ticket['quantity'] ?? 0);
            $hargaId = (int) ($ticket['harga_id'] ?? 0);

            if ($quantity > 0 && $hargaId > 0) {
                $items[] = [
                    'harga_id' => $hargaId,
                    'quantity' => $quantity,
                    'order_by' => (int) ($ticket['orderBy'] ?? $ticket['order_by'] ?? ($index + 1)),
                ];
            }
        }

        foreach ((array) $request->input('harga_id', []) as $index => $hargaId) {
            $quantity = (int) data_get((array) $request->input('quantity', []), $index, 0);

            if ($quantity > 0 && (int) $hargaId > 0) {
                $items[] = [
                    'harga_id' => (int) $hargaId,
                    'quantity' => $quantity,
                    'order_by' => $index + 1,
                ];
            }
        }

        for ($i = 0; $i < 10; $i++) {
            $quantity = (int) $request->input("ticket$i", 0);
            $hargaId = (int) $request->input("harga_id$i", 0);

            if ($quantity > 0 && $hargaId > 0) {
                $items[] = [
                    'harga_id' => $hargaId,
                    'quantity' => $quantity,
                    'order_by' => (int) $request->input("orderBy$i", $i + 1),
                ];
            }
        }

        return collect($items)
            ->groupBy('harga_id')
            ->map(function ($group, $hargaId) {
                return [
                    'harga_id' => (int) $hargaId,
                    'quantity' => (int) $group->sum('quantity'),
                    'order_by' => (int) $group->min('order_by'),
                ];
            })
            ->sortBy('harga_id')
            ->values()
            ->all();
    }
}
