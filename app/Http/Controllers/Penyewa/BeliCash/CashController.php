<?php

namespace App\Http\Controllers\Penyewa\BeliCash;

use App\Http\Controllers\Controller;
use App\Jobs\sendEmailTrnsaksi;
use App\Models\Cart;
use App\Models\Cash;
use App\Models\Event;
use App\Models\Harga;
use App\Models\HargaCart;
use App\Models\Partner;
use App\Models\Transaction;
use App\Services\Tickets\GateTokenService;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class CashController extends Controller
{
    public function createCash(Request $request)
    {
        $validated = $request->validate([
            'event_uid' => 'required|string',
            'harga_id' => 'required|integer',
            'qty' => 'required|integer|min:1|max:5',
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'alamat' => 'nullable|string|max:500',
            'ttl' => 'required|string|max:100',
            'gender' => 'required|string|max:50',
            'nomor' => 'nullable|string|max:30',
            'partner' => 'nullable|string|max:100',
        ]);

        $ownerUid = Auth::user()->uid;
        $qty = (int) $validated['qty'];
        $nama = $validated['name'];
        $email = $validated['email'];
        $alamat = $validated['alamat'] ?? '-';
        $ttl = $validated['ttl'];
        $gender = $validated['gender'];
        $nomor = $validated['nomor'] ?? '080000000000';
        $partnerUid = $validated['partner'] ?? null;
        $cart = null;
        $pajakPersen = 0;

        try {
            DB::transaction(function () use (
                &$cart,
                &$pajakPersen,
                $validated,
                $ownerUid,
                $qty,
                $nama,
                $email,
                $alamat,
                $ttl,
                $gender,
                $nomor,
                $partnerUid
            ) {
                $event = Event::query()
                    ->where('uid', $validated['event_uid'])
                    ->where('user_uid', $ownerUid)
                    ->where('konfirmasi', '1')
                    ->where('status', 'active')
                    ->firstOrFail();

                $partner = null;
                if (filled($partnerUid)) {
                    $partner = Partner::query()
                        ->where('uid', $partnerUid)
                        ->where('user_uid', $ownerUid)
                        ->where('status', 'active')
                        ->first();

                    if (! $partner) {
                        throw ValidationException::withMessages([
                            'partner' => 'Partner tidak valid.',
                        ]);
                    }
                }

                $harga = Harga::query()
                    ->whereKey($validated['harga_id'])
                    ->where('uid', $event->uid)
                    ->where('status', 'active')
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($qty > $harga->remainingQty()) {
                    return back()->with('error', 'Stok tiket tidak mencukupi.')->throwResponse();
                }

                $subtotal = (int) $harga->harga * $qty;
                $pajakPersen = (int) ($event->fee ?? 0);
                $nilaiPajak = (int) round(($pajakPersen / 100) * $subtotal);
                $totalFinal = $subtotal + $nilaiPajak;
                $cartUid = (string) Str::uuid();
                $invoice = 'CASH-'.date('Ymd').Str::upper(Str::random(10));

                $cart = new Cart([
                    'uid' => $cartUid,
                    'user_uid' => $ownerUid,
                    'event_uid' => $event->uid,
                    'invoice' => $invoice,
                    'status' => Cart::STATUS_SUCCESS,
                    'konfirmasi' => null,
                    'payment_type' => 'cash',
                    'gross_amount' => $totalFinal,
                    'paid_at' => now(),
                    'pajak' => $nilaiPajak,
                    'pajak_persen' => $pajakPersen,
                ]);

                $hargaCart = new HargaCart([
                    'orderBy' => '1',
                    'uid' => $cartUid,
                    'harga_id' => $harga->id,
                    'event_uid' => $event->uid,
                    'quantity' => $qty,
                    'harga_ticket' => $harga->harga,
                    'kategori_harga' => $harga->kategori,
                    'voucher' => null,
                    'disc' => 0,
                ]);

                $transaksi = new Transaction([
                    'uid' => $cartUid,
                    'user_uid' => $ownerUid,
                    'event_uid' => $event->uid,
                    'amount' => $totalFinal,
                    'gross_amount' => $totalFinal,
                    'invoice' => $invoice,
                    'payment_type' => 'cash',
                    'status_transaksi' => Cart::STATUS_SUCCESS,
                    'paid_at' => now(),
                ]);

                $cash = new Cash([
                    'uid' => $cartUid,
                    'uid_partner' => $partner?->uid,
                    'uid_user' => $ownerUid,
                    'uid_event' => $event->uid,
                    'name' => $nama,
                    'email' => $email,
                    'nomor' => $nomor,
                    'alamat' => $alamat,
                    'lahir' => $ttl,
                    'gender' => $gender,
                ]);

                $cart->save();
                $hargaCart->save();
                $transaksi->save();
                $cash->save();
                $harga->increment('sold_qty', $qty);
                app(GateTokenService::class)->issueIfEnabled($cart);
            }, 3);
        } catch (HttpResponseException $e) {
            throw $e;
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        try {
            dispatch(new sendEmailTrnsaksi($email, $nama, $cart->uid));

            return redirect()->back()->with('success', 'Pembelian Cash Berhasil (Termasuk Pajak '.$pajakPersen.'%). Email barcode telah dijadwalkan.');
        } catch (Throwable $e) {
            Log::error('Gagal menjadwalkan email barcode cash dari controller legacy.', [
                'cart_uid' => $cart->uid,
                'recipient' => $email,
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()->with('success', 'Pembelian Cash Berhasil (Termasuk Pajak '.$pajakPersen.'%). Email barcode perlu dikirim ulang.');
        }
    }
}
