<?php

namespace App\Services\Tickets;

use App\Models\Cart;
use App\Models\CartVoucher;
use App\Models\Event;
use App\Models\Harga;
use App\Models\HargaCart;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Voucher;
use App\Models\VoucherReservation;
use App\Models\VoucherUsage;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class TicketReservationService
{
    public const RESERVATION_MINUTES = 15;

    public function reserveForUserEvent(Event $event, string $userUid, array $items): array
    {
        return DB::transaction(function () use ($event, $userUid, $items) {
            User::where('uid', $userUid)->lockForUpdate()->firstOrFail();

            $expiredCarts = Cart::where('event_uid', $event->uid)
                ->where('user_uid', $userUid)
                ->whereIn('status', Cart::ACTIVE_RESERVATION_STATUSES)
                ->whereNotNull('expires_at')
                ->where('expires_at', '<=', now())
                ->whereNull('reservation_released_at')
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            foreach ($expiredCarts as $expiredCart) {
                $this->releaseLockedCart($expiredCart, Cart::STATUS_EXPIRED);
            }

            $activeCart = Cart::where('event_uid', $event->uid)
                ->where('user_uid', $userUid)
                ->whereIn('status', Cart::ACTIVE_RESERVATION_STATUSES)
                ->where(function ($query) {
                    $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
                })
                ->orderBy('id')
                ->lockForUpdate()
                ->first();

            if ($activeCart) {
                return ['cart' => $activeCart, 'created' => false];
            }

            return [
                'cart' => $this->reserve($event, $userUid, $items),
                'created' => true,
            ];
        }, 3);
    }

    public function reserveVoucherForCart(string $cartUid, string $userUid, string $code): array
    {
        return DB::transaction(function () use ($cartUid, $userUid, $code) {
            $cart = Cart::where('uid', $cartUid)
                ->where('user_uid', $userUid)
                ->lockForUpdate()
                ->first();

            if (! $cart
                || ! in_array($cart->status, Cart::ACTIVE_RESERVATION_STATUSES, true)
                || $cart->isReservationExpired()) {
                throw ValidationException::withMessages([
                    'voucher' => 'Reservation sudah expired atau cart tidak valid.',
                ]);
            }

            $cartVoucher = CartVoucher::where('uid', $cart->uid)
                ->where('event_uid', $cart->event_uid)
                ->lockForUpdate()
                ->first();
            $reservation = VoucherReservation::where('cart_uid', $cart->uid)
                ->lockForUpdate()
                ->first();
            $candidate = Voucher::where('code', $code)
                ->where('event_uid', $cart->event_uid)
                ->first();

            if (! $candidate) {
                throw ValidationException::withMessages(['voucher' => 'Voucher '.$code.' Invalid']);
            }

            $voucherIds = collect([$candidate->id, $reservation?->voucher_id])
                ->filter()
                ->unique()
                ->sort()
                ->values();
            $lockedVouchers = Voucher::whereIn('id', $voucherIds)
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');
            $voucher = $lockedVouchers->get($candidate->id);

            if (! $voucher
                || $voucher->status !== 'active'
                || $voucher->event_uid !== $cart->event_uid
                || $voucher->code !== $code) {
                throw ValidationException::withMessages(['voucher' => 'Voucher '.$code.' Invalid']);
            }

            $ticketTotal = (int) $cart->hargaCarts()->sum(DB::raw('quantity * harga_ticket'));
            if ($ticketTotal < (int) $voucher->min_beli) {
                throw ValidationException::withMessages([
                    'voucher' => 'Minimal pembelian voucher belum terpenuhi.',
                ]);
            }

            $alreadyHeld = $reservation
                && $reservation->status === VoucherReservation::STATUS_ACTIVE
                && (int) $reservation->voucher_id === (int) $voucher->id;

            if (! $alreadyHeld && $this->voucherIsLimited($voucher)) {
                $activeReservations = VoucherReservation::where('voucher_id', $voucher->id)
                    ->where('status', VoucherReservation::STATUS_ACTIVE)
                    ->lockForUpdate()
                    ->get()
                    ->count();

                if (($this->committedVoucherUsageCount($voucher) + $activeReservations) >= (int) $voucher->limit) {
                    throw ValidationException::withMessages(['voucher' => 'Voucher Expired']);
                }
            }

            if ($reservation && $reservation->status === VoucherReservation::STATUS_COMMITTED) {
                throw ValidationException::withMessages([
                    'voucher' => 'Voucher pada transaksi ini sudah digunakan.',
                ]);
            }

            $reservation ??= new VoucherReservation(['cart_uid' => $cart->uid]);
            $reservation->fill([
                'voucher_id' => $voucher->id,
                'voucher_uid' => $voucher->uid,
                'invoice' => $cart->invoice,
                'code' => $voucher->code,
                'status' => VoucherReservation::STATUS_ACTIVE,
                'reserved_at' => $alreadyHeld ? $reservation->reserved_at : now(),
                'released_at' => null,
                'committed_at' => null,
            ]);
            $reservation->save();

            $cartVoucher ??= new CartVoucher([
                'uid' => $cart->uid,
                'event_uid' => $cart->event_uid,
            ]);
            $cartVoucher->fill([
                'uid_vouchers' => $voucher->uid,
                'user_uid' => $cart->user_uid,
                'code' => $voucher->code,
            ]);
            $cartVoucher->save();

            $discount = app(TicketPricingService::class)->calculateVoucherDiscount($cart, $ticketTotal);
            HargaCart::where('uid', $cart->uid)->update(['voucher' => null, 'disc' => 0]);
            $firstItem = HargaCart::where('uid', $cart->uid)->orderBy('id')->first();

            if ($firstItem) {
                $firstItem->voucher = $voucher->code;
                $firstItem->disc = $discount;
                $firstItem->save();
            }

            return ['voucher' => $voucher, 'discount' => $discount];
        }, 3);
    }

    public function releaseVoucherForCart(string $cartUid, string $userUid): void
    {
        DB::transaction(function () use ($cartUid, $userUid) {
            $cart = Cart::where('uid', $cartUid)
                ->where('user_uid', $userUid)
                ->lockForUpdate()
                ->first();

            if (! $cart
                || ! in_array($cart->status, Cart::ACTIVE_RESERVATION_STATUSES, true)
                || $cart->isReservationExpired()) {
                throw ValidationException::withMessages([
                    'voucher' => 'Reservation sudah expired atau cart tidak valid.',
                ]);
            }

            $this->releaseVoucherReservationLocked($cart);

            CartVoucher::where('uid', $cart->uid)
                ->where('event_uid', $cart->event_uid)
                ->update(['code' => '', 'uid_vouchers' => null]);
            HargaCart::where('uid', $cart->uid)->update([
                'voucher' => null,
                'disc' => 0,
            ]);
        }, 3);
    }

    public function cancelOwnedReservation(string $cartUid, string $userUid): Cart
    {
        return DB::transaction(function () use ($cartUid, $userUid) {
            $cart = Cart::where('uid', $cartUid)
                ->where('user_uid', $userUid)
                ->lockForUpdate()
                ->firstOrFail();

            if ($cart->status !== Cart::STATUS_RESERVED || $cart->hasActivePaymentLink()) {
                throw ValidationException::withMessages([
                    'transaction' => 'Transaksi pada status ini tidak dapat dibatalkan.',
                ]);
            }

            $this->releaseLockedCart($cart, Cart::STATUS_CANCELLED);

            Transaction::where('invoice', $cart->invoice)->update([
                'status_transaksi' => Cart::STATUS_CANCELLED,
            ]);

            return $cart;
        }, 3);
    }

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

                if ((int) $item['quantity'] > $harga->maxOrderQty()) {
                    throw ValidationException::withMessages([
                        'quantity' => "Maksimal pemesanan tiket {$harga->kategori} adalah {$harga->maxOrderQty()}.",
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

        $this->releaseVoucherReservationLocked($cart);

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

            $this->releaseVoucherReservationLocked($cart);

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

        app(GateTokenService::class)->ensureTicketAccessReady($cart);

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
            ->where('event_uid', $cart->event_uid)
            ->whereNotNull('code')
            ->where('code', '!=', '')
            ->first();

        if (! $cartVoucher) {
            return true;
        }

        if (VoucherUsage::where('cart_uid', $cart->uid)->exists()) {
            return true;
        }

        if (! Schema::hasTable('voucher_reservations')) {
            return $this->markLegacyVoucherUsage($cart, $cartVoucher);
        }

        $reservation = VoucherReservation::where('cart_uid', $cart->uid)
            ->lockForUpdate()
            ->first();

        $voucher = Voucher::where('code', $cartVoucher->code)
            ->where('event_uid', $cart->event_uid)
            ->lockForUpdate()
            ->first();

        if (! $voucher) {
            return false;
        }

        $holdsReservation = $reservation
            && $reservation->status === VoucherReservation::STATUS_ACTIVE
            && (int) $reservation->voucher_id === (int) $voucher->id;
        $alreadyCommitted = $reservation
            && $reservation->status === VoucherReservation::STATUS_COMMITTED
            && (int) $reservation->voucher_id === (int) $voucher->id;

        if (! $holdsReservation && ! $alreadyCommitted) {
            if ($voucher->status !== 'active') {
                return false;
            }

            if ($this->voucherIsLimited($voucher)) {
                $activeReservations = VoucherReservation::where('voucher_id', $voucher->id)
                    ->where('status', VoucherReservation::STATUS_ACTIVE)
                    ->lockForUpdate()
                    ->get()
                    ->count();

                if (($this->committedVoucherUsageCount($voucher) + $activeReservations) >= (int) $voucher->limit) {
                    return false;
                }
            }
        }

        $discount = app(TicketPricingService::class)->calculateVoucherDiscount($cart, (int) $cart->hargaCarts()->sum(DB::raw('quantity * harga_ticket')));
        $committedUsage = $this->committedVoucherUsageCount($voucher);

        VoucherUsage::create([
            'voucher_id' => $voucher->id,
            'voucher_uid' => $voucher->uid,
            'cart_uid' => $cart->uid,
            'invoice' => $cart->invoice,
            'code' => $voucher->code,
            'discount_amount' => $discount,
            'used_at' => now(),
        ]);

        if (! $alreadyCommitted) {
            $voucher->digunakan = $committedUsage + 1;
            $voucher->save();
        }

        $reservation ??= new VoucherReservation(['cart_uid' => $cart->uid]);
        $reservation->fill([
            'voucher_id' => $voucher->id,
            'voucher_uid' => $voucher->uid,
            'invoice' => $cart->invoice,
            'code' => $voucher->code,
            'status' => VoucherReservation::STATUS_COMMITTED,
            'reserved_at' => $reservation->reserved_at ?: now(),
            'released_at' => null,
            'committed_at' => now(),
        ]);
        $reservation->save();

        return true;
    }

    protected function releaseVoucherReservationLocked(Cart $cart): void
    {
        if (! Schema::hasTable('voucher_reservations')) {
            return;
        }

        $reservation = VoucherReservation::where('cart_uid', $cart->uid)
            ->lockForUpdate()
            ->first();

        if (! $reservation || $reservation->status !== VoucherReservation::STATUS_ACTIVE) {
            return;
        }

        if ($reservation->voucher_id) {
            Voucher::whereKey($reservation->voucher_id)->lockForUpdate()->first();
        }

        $reservation->status = VoucherReservation::STATUS_RELEASED;
        $reservation->released_at = now();
        $reservation->save();
    }

    protected function voucherIsLimited(Voucher $voucher): bool
    {
        return (int) $voucher->limit > 0;
    }

    protected function committedVoucherUsageCount(Voucher $voucher): int
    {
        $recordedUsage = VoucherUsage::where(function ($query) use ($voucher) {
            $query->where('voucher_id', $voucher->id)
                ->orWhere('voucher_uid', $voucher->uid);
        })->count();

        return max((int) $voucher->digunakan, $recordedUsage);
    }

    protected function markLegacyVoucherUsage(Cart $cart, CartVoucher $cartVoucher): bool
    {
        $voucher = Voucher::where('code', $cartVoucher->code)
            ->where('event_uid', $cart->event_uid)
            ->lockForUpdate()
            ->first();

        if (! $voucher || $voucher->status !== 'active') {
            return false;
        }

        if ($this->voucherIsLimited($voucher)
            && $this->committedVoucherUsageCount($voucher) >= (int) $voucher->limit) {
            return false;
        }

        $discount = app(TicketPricingService::class)->calculateVoucherDiscount(
            $cart,
            (int) $cart->hargaCarts()->sum(DB::raw('quantity * harga_ticket'))
        );
        $committedUsage = $this->committedVoucherUsageCount($voucher);

        VoucherUsage::create([
            'voucher_id' => $voucher->id,
            'voucher_uid' => $voucher->uid,
            'cart_uid' => $cart->uid,
            'invoice' => $cart->invoice,
            'code' => $voucher->code,
            'discount_amount' => $discount,
            'used_at' => now(),
        ]);

        $voucher->digunakan = $committedUsage + 1;
        $voucher->save();

        return true;
    }
}
