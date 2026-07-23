<?php

namespace App\Services\Tickets;

use App\Models\Cart;
use App\Models\CartVoucher;
use App\Models\Event;
use App\Models\Harga;
use App\Models\HargaCart;
use App\Models\Transaction;
use App\Models\Voucher;
use App\Models\VoucherUsage;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class TicketReservationService
{
    public const RESERVATION_MINUTES = 15;

    public function reserve(Event $event, string $userUid, array $items): Cart
    {
        if ($event->status !== 'active' || (string) $event->konfirmasi !== '1') {
            throw ValidationException::withMessages([
                'event_uid' => 'Event tidak aktif atau belum dikonfirmasi.',
            ]);
        }

        return DB::transaction(function () use ($event, $userUid, $items) {
            $hargaIds = collect($items)->pluck('harga_id')->unique()->sort()->values()->all();
            $hargas = Harga::whereIn('id', $hargaIds)
                ->where('uid', $event->uid)
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            if ($hargas->count() !== count($hargaIds)) {
                throw ValidationException::withMessages([
                    'harga_id' => 'Kategori tiket tidak valid untuk event ini.',
                ]);
            }

            foreach ($items as $item) {
                /** @var Harga $harga */
                $harga = $hargas->get($item['harga_id']);

                if ($harga->status !== 'active') {
                    throw ValidationException::withMessages([
                        'harga_id' => "Tiket {$harga->kategori} tidak aktif.",
                    ]);
                }

                if ($harga->remainingQty() < $item['quantity']) {
                    throw ValidationException::withMessages([
                        'quantity' => "Stok tiket {$harga->kategori} baru saja habis.",
                    ]);
                }
            }

            foreach ($items as $item) {
                $harga = $hargas->get($item['harga_id']);
                $harga->reserved_qty = (int) $harga->reserved_qty + (int) $item['quantity'];
                $harga->save();
            }

            $cart = Cart::create([
                'uid' => (string) Str::uuid(),
                'user_uid' => $userUid,
                'event_uid' => $event->uid,
                'invoice' => $this->generateInvoice(),
                'status' => Cart::STATUS_RESERVED,
                'expires_at' => now()->addMinutes(self::RESERVATION_MINUTES),
            ]);

            foreach ($items as $index => $item) {
                $harga = $hargas->get($item['harga_id']);

                HargaCart::create([
                    'uid' => $cart->uid,
                    'orderBy' => $item['order_by'] ?? ($index + 1),
                    'event_uid' => $event->uid,
                    'harga_id' => $harga->id,
                    'quantity' => (int) $item['quantity'],
                    'harga_ticket' => (int) $harga->harga,
                    'kategori_harga' => $harga->kategori,
                    'voucher' => null,
                    'disc' => 0,
                ]);
            }

            return $cart;
        }, 3);
    }

    public function releaseExpiredBatch(int $limit = 100): int
    {
        $cartIds = Cart::whereIn('status', Cart::ACTIVE_RESERVATION_STATUSES)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->whereNull('reservation_released_at')
            ->orderBy('id')
            ->limit($limit)
            ->pluck('id');

        $released = 0;

        foreach ($cartIds as $cartId) {
            DB::transaction(function () use ($cartId, &$released) {
                $cart = Cart::whereKey($cartId)->lockForUpdate()->first();

                if (! $cart || ! $this->canRelease($cart)) {
                    return;
                }

                $this->releaseLockedCart($cart, Cart::STATUS_EXPIRED);
                $released++;
            }, 3);
        }

        return $released;
    }

    public function releaseLockedCart(Cart $cart, string $status, ?string $reason = null): void
    {
        if (! $this->canRelease($cart)) {
            return;
        }

        $items = $this->lockHargaRowsForCart($cart);

        foreach ($items as $item) {
            $harga = $item->masterHarga;

            if (! $harga) {
                continue;
            }

            $harga->reserved_qty = max(0, (int) $harga->reserved_qty - (int) $item->quantity);
            $harga->save();
        }

        $cart->status = $status;
        $cart->reservation_released_at = now();

        if ($reason) {
            $cart->review_reason = $reason;
        }

        $cart->save();
    }

    public function settleLockedCart(Cart $cart, string $paymentType, ?string $midtransTransactionId = null): bool
    {
        if ($cart->status === Cart::STATUS_SUCCESS) {
            return false;
        }

        $items = $this->lockHargaRowsForCart($cart);
        $reservedIsStillHeld = $cart->reservation_released_at === null
            && in_array($cart->status, Cart::ACTIVE_RESERVATION_STATUSES, true);

        if (! $reservedIsStillHeld && ! $this->hasAvailableStockForLatePayment($items)) {
            $cart->status = Cart::STATUS_PAYMENT_REVIEW;
            $cart->review_reason = 'Pembayaran masuk setelah reservation dilepas dan stok tidak mencukupi.';
            $cart->midtrans_transaction_id = $midtransTransactionId;
            $cart->midtrans_status = Cart::STATUS_PAYMENT_REVIEW;
            $cart->save();

            Transaction::where('invoice', $cart->invoice)->update([
                'status_transaksi' => Cart::STATUS_PAYMENT_REVIEW,
                'payment_type' => $paymentType,
            ]);

            return false;
        }

        if (! $this->markVoucherUsage($cart)) {
            if ($reservedIsStillHeld) {
                $this->releaseStockRows($items);
            }

            $cart->status = Cart::STATUS_PAYMENT_REVIEW;
            $cart->reservation_released_at = $cart->reservation_released_at ?: now();
            $cart->review_reason = 'Voucher tidak tersedia saat settlement diproses.';
            $cart->midtrans_transaction_id = $midtransTransactionId;
            $cart->midtrans_status = Cart::STATUS_PAYMENT_REVIEW;
            $cart->save();

            Transaction::where('invoice', $cart->invoice)->update([
                'status_transaksi' => Cart::STATUS_PAYMENT_REVIEW,
                'payment_type' => $paymentType,
            ]);

            return false;
        }

        foreach ($items as $item) {
            $harga = $item->masterHarga;

            if (! $harga) {
                continue;
            }

            if ($reservedIsStillHeld) {
                $harga->reserved_qty = max(0, (int) $harga->reserved_qty - (int) $item->quantity);
            }

            $harga->sold_qty = (int) $harga->sold_qty + (int) $item->quantity;
            $harga->save();
        }

        $cart->status = Cart::STATUS_SUCCESS;
        $cart->paid_at = now();
        $cart->reservation_released_at = $cart->reservation_released_at ?: now();
        $cart->payment_type = $paymentType;
        $cart->midtrans_transaction_id = $midtransTransactionId;
        $cart->midtrans_status = Cart::STATUS_SUCCESS;
        $cart->save();

        Transaction::updateOrCreate(
            ['invoice' => $cart->invoice],
            [
                'uid' => $cart->uid,
                'user_uid' => $cart->user_uid,
                'event_uid' => $cart->event_uid,
                'amount' => (string) $cart->gross_amount,
                'gross_amount' => (int) $cart->gross_amount,
                'status_transaksi' => Cart::STATUS_SUCCESS,
                'payment_type' => $paymentType,
                'paid_at' => now(),
            ]
        );

        return true;
    }

    public function markPendingLockedCart(Cart $cart, string $paymentType): void
    {
        if ($cart->status === Cart::STATUS_SUCCESS) {
            return;
        }

        if (in_array($cart->status, [Cart::STATUS_RESERVED, Cart::STATUS_PENDING], true)) {
            $cart->status = Cart::STATUS_PENDING;
            $cart->payment_type = $paymentType;
            $cart->midtrans_status = Cart::STATUS_PENDING;
            $cart->save();

            Transaction::where('invoice', $cart->invoice)->update([
                'status_transaksi' => Cart::STATUS_PENDING,
                'payment_type' => $paymentType,
            ]);
        }
    }

    public function generateInvoice(): string
    {
        do {
            $invoice = 'INV-'.now()->format('ymd').'-'.Str::upper(Str::random(6)).mt_rand(100, 999);
        } while (Cart::where('invoice', $invoice)->exists());

        return $invoice;
    }

    protected function canRelease(Cart $cart): bool
    {
        return in_array($cart->status, Cart::ACTIVE_RESERVATION_STATUSES, true)
            && $cart->reservation_released_at === null;
    }

    protected function lockHargaRowsForCart(Cart $cart): Collection
    {
        $items = HargaCart::where('uid', $cart->uid)
            ->whereNotNull('harga_id')
            ->orderBy('harga_id')
            ->get();

        $hargaIds = $items->pluck('harga_id')->unique()->sort()->values();
        $hargas = Harga::whereIn('id', $hargaIds)
            ->orderBy('id')
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        return $items->each(function (HargaCart $item) use ($hargas) {
            $item->setRelation('masterHarga', $hargas->get($item->harga_id));
        });
    }

    protected function hasAvailableStockForLatePayment(Collection $items): bool
    {
        foreach ($items as $item) {
            $harga = $item->masterHarga;

            if (! $harga || $harga->remainingQty() < (int) $item->quantity) {
                return false;
            }
        }

        return true;
    }

    protected function releaseStockRows(Collection $items): void
    {
        foreach ($items as $item) {
            $harga = $item->masterHarga;

            if (! $harga) {
                continue;
            }

            $harga->reserved_qty = max(0, (int) $harga->reserved_qty - (int) $item->quantity);
            $harga->save();
        }
    }

    protected function markVoucherUsage(Cart $cart): bool
    {
        $cartVoucher = CartVoucher::where('uid', $cart->uid)
            ->whereNotNull('code')
            ->where('code', '!=', '')
            ->first();

        if (! $cartVoucher) {
            return true;
        }

        if (VoucherUsage::where('cart_uid', $cart->uid)->exists()) {
            return true;
        }

        $voucher = Voucher::where('code', $cartVoucher->code)
            ->where('event_uid', $cart->event_uid)
            ->lockForUpdate()
            ->first();

        if (! $voucher || $voucher->status !== 'active') {
            return false;
        }

        if ((int) $voucher->digunakan >= (int) $voucher->limit) {
            return false;
        }

        $discount = app(TicketPricingService::class)->calculateVoucherDiscount($cart, (int) $cart->hargaCarts()->sum(DB::raw('quantity * harga_ticket')));

        VoucherUsage::create([
            'voucher_id' => $voucher->id,
            'voucher_uid' => $voucher->uid,
            'cart_uid' => $cart->uid,
            'invoice' => $cart->invoice,
            'code' => $voucher->code,
            'discount_amount' => $discount,
            'used_at' => now(),
        ]);

        $voucher->digunakan = (int) $voucher->digunakan + 1;
        $voucher->save();

        return true;
    }
}
