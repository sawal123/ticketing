<?php

namespace App\Services\Tickets;

use App\Models\Cart;
use App\Models\CartVoucher;
use App\Models\Event;
use App\Models\HargaCart;
use App\Models\PaymentGateway;
use App\Models\Voucher;

class TicketPricingService
{
    public function calculateCart(Cart $cart, ?PaymentGateway $paymentGateway = null): array
    {
        $cart->loadMissing(['hargaCarts.masterHarga', 'event']);

        $items = $cart->hargaCarts;
        $ticketTotal = (int) $items->sum(function (HargaCart $item) {
            $price = $item->masterHarga ? (int) $item->masterHarga->harga : (int) $item->harga_ticket;

            return (int) $item->quantity * $price;
        });

        $discount = $this->calculateVoucherDiscount($cart, $ticketTotal);
        $subtotal = max(0, $ticketTotal - $discount);
        [$taxPercent, $taxAmount] = $this->tax($cart->event, $subtotal);
        $internetFee = $paymentGateway ? $this->internetFee($paymentGateway, $subtotal) : (int) ($cart->internet_fee ?? 0);
        $grossAmount = max(0, $subtotal + $taxAmount + $internetFee);

        return [
            'ticket_total' => $ticketTotal,
            'discount' => $discount,
            'subtotal' => $subtotal,
            'tax_percent' => $taxPercent,
            'tax_amount' => $taxAmount,
            'internet_fee' => $internetFee,
            'gross_amount' => $grossAmount,
        ];
    }

    public function calculateVoucherDiscount(Cart $cart, int $ticketTotal): int
    {
        $cartVoucher = CartVoucher::where('uid', $cart->uid)
            ->whereNotNull('code')
            ->where('code', '!=', '')
            ->first();

        if (! $cartVoucher) {
            return 0;
        }

        $voucher = Voucher::where('code', $cartVoucher->code)
            ->where('event_uid', $cart->event_uid)
            ->where('status', 'active')
            ->first();

        if (! $voucher || $ticketTotal < (int) $voucher->min_beli) {
            return 0;
        }

        if ($voucher->unit === 'persen') {
            $discount = (int) round(((int) $voucher->nominal / 100) * $ticketTotal);
            $maxDiscount = (int) ($voucher->max_disc ?? 0);

            return $maxDiscount > 0 ? min($discount, $maxDiscount) : $discount;
        }

        return min((int) $voucher->nominal, $ticketTotal);
    }

    public function taxPercent(?Event $event): int
    {
        return $this->tax($event, 0)[0];
    }

    public function tax(?Event $event, int $subtotal): array
    {
        if (! $event) {
            return [0, 0];
        }

        $fee = (int) ($event->fee ?? 0);

        if ($fee > 100) {
            return [0, $fee];
        }

        return [$fee, (int) round(($fee / 100) * $subtotal)];
    }

    public function internetFee(PaymentGateway $paymentGateway, int $subtotal): int
    {
        if ($paymentGateway->biaya_type === 'rupiah') {
            return (int) $paymentGateway->biaya;
        }

        return (int) round(((int) $paymentGateway->biaya / 100) * $subtotal);
    }
}
