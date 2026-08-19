<?php

namespace App\Services\Tickets;

use App\Models\Cart;
use App\Models\CartVoucher;
use App\Models\Event;
use App\Models\EventPaymentGateway;
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
        $currentTax = $this->tax($cart->event, $subtotal);
        $hasCompleteFinancialSnapshot = $this->hasCompleteFinancialSnapshot($cart);
        $storedFinancialSnapshot = ($hasCompleteFinancialSnapshot || ! $paymentGateway)
            ? $this->storedFinancialSnapshot($cart, $subtotal, $currentTax)
            : null;
        $usesHistoricalStoredInternetFee = ! $paymentGateway
            && in_array($cart->status, [Cart::STATUS_PENDING, Cart::STATUS_SUCCESS], true);

        if ($storedFinancialSnapshot && $hasCompleteFinancialSnapshot) {
            $taxPercent = $storedFinancialSnapshot['tax_percent'];
            $taxAmount = $storedFinancialSnapshot['tax_amount'];
            $paymentFee = $storedFinancialSnapshot;
            $internetFee = $storedFinancialSnapshot['internet_fee'];
            $grossAmount = $storedFinancialSnapshot['gross_amount'];
        } else {
            [$taxPercent, $taxAmount] = $storedFinancialSnapshot
                ? [$storedFinancialSnapshot['tax_percent'], $storedFinancialSnapshot['tax_amount']]
                : $currentTax;
            $paymentFee = $paymentGateway
                ? $this->resolvePaymentFeeSnapshot($cart, $paymentGateway, $subtotal)
                : ($storedFinancialSnapshot ?? $this->storedPaymentFeeSnapshot($cart));
            $internetFee = $paymentGateway
                ? $paymentFee['internet_fee']
                : ($usesHistoricalStoredInternetFee
                    ? (int) ($cart->internet_fee ?? 0)
                    : ($storedFinancialSnapshot['internet_fee'] ?? (int) ($cart->internet_fee ?? 0)));
            $grossAmount = $storedFinancialSnapshot && $cart->gross_amount !== null
                ? $storedFinancialSnapshot['gross_amount']
                : max(0, $subtotal + $taxAmount + $internetFee);
        }

        return [
            'ticket_total' => $ticketTotal,
            'discount' => $discount,
            'subtotal' => $subtotal,
            'tax_percent' => $taxPercent,
            'tax_amount' => $taxAmount,
            'payment_gateway_available' => $paymentGateway ? $paymentFee['is_available'] : true,
            'payment_fee_mode' => $paymentFee['payment_fee_mode'],
            'payment_fee_fixed' => $paymentFee['payment_fee_fixed'],
            'payment_fee_percent' => $paymentFee['payment_fee_percent'],
            'internet_fee' => $internetFee,
            'gross_amount' => $grossAmount,
        ];
    }

    public function calculateVoucherDiscount(Cart $cart, int $ticketTotal): int
    {
        $cartVoucher = CartVoucher::where('uid', $cart->uid)
            ->where('event_uid', $cart->event_uid)
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
        return $this->resolvePaymentFeeSnapshot(
            new Cart(['event_uid' => null]),
            $paymentGateway,
            $subtotal,
            false
        )['internet_fee'];
    }

    private function resolvePaymentFeeSnapshot(
        Cart $cart,
        PaymentGateway $paymentGateway,
        int $subtotal,
        bool $requiresActiveEventGateway = true
    ): array {
        if (! $paymentGateway->is_active) {
            return $this->emptyResolvedPaymentFee();
        }

        $eventGateway = null;

        if ($requiresActiveEventGateway) {
            $event = $cart->event;

            if (! $event) {
                return $this->emptyResolvedPaymentFee();
            }

            $eventGateway = $paymentGateway->eventPaymentGateways()
                ->where('event_id', $event->id)
                ->where('is_active', true)
                ->first();

            if (! $eventGateway) {
                return $this->emptyResolvedPaymentFee();
            }
        }

        [$mode, $fixed, $percent] = $eventGateway && $eventGateway->fee_mode === EventPaymentGateway::FEE_MODE_MANUAL
            ? [
                EventPaymentGateway::FEE_MODE_MANUAL,
                $this->normalizeFeeValue($eventGateway->fee_fixed ?? 0),
                $this->normalizeFeeValue($eventGateway->fee_percent ?? 0),
            ]
            : array_merge([EventPaymentGateway::FEE_MODE_GLOBAL], $this->defaultGatewayFeeParts($paymentGateway));

        return [
            'is_available' => true,
            'payment_fee_mode' => $mode,
            'payment_fee_fixed' => $this->formatDecimal($fixed, 2),
            'payment_fee_percent' => $this->formatDecimal($percent, 4),
            'internet_fee' => max(0, (int) round($fixed + (($subtotal * $percent) / 100))),
        ];
    }

    private function storedPaymentFeeSnapshot(Cart $cart): array
    {
        return [
            'is_available' => true,
            'payment_fee_mode' => $cart->payment_fee_mode,
            'payment_fee_fixed' => $this->storedDecimal($cart->payment_fee_fixed, 2),
            'payment_fee_percent' => $this->storedDecimal($cart->payment_fee_percent, 4),
            'internet_fee' => (int) ($cart->internet_fee ?? 0),
        ];
    }

    private function storedFinancialSnapshot(Cart $cart, int $subtotal, array $currentTax): array
    {
        $paymentFeeSnapshot = $this->storedPaymentFeeSnapshot($cart);
        $hasStoredTaxSnapshot = $this->hasCompleteFinancialSnapshot($cart)
            || (int) ($cart->pajak ?? 0) > 0
            || (int) ($cart->pajak_persen ?? 0) > 0;
        $taxPercent = $hasStoredTaxSnapshot ? (int) ($cart->pajak_persen ?? 0) : (int) $currentTax[0];
        $taxAmount = $hasStoredTaxSnapshot ? (int) ($cart->pajak ?? 0) : (int) $currentTax[1];
        $internetFee = $paymentFeeSnapshot['internet_fee'];

        return array_merge($paymentFeeSnapshot, [
            'tax_percent' => $taxPercent,
            'tax_amount' => $taxAmount,
            'gross_amount' => $cart->gross_amount !== null
                ? (int) $cart->gross_amount
                : max(0, $subtotal + $taxAmount + $internetFee),
        ]);
    }

    private function defaultGatewayFeeParts(PaymentGateway $paymentGateway): array
    {
        $defaultFixed = $paymentGateway->getAttribute('default_fee_fixed');
        $defaultPercent = $paymentGateway->getAttribute('default_fee_percent');

        if ($defaultFixed === null || $defaultPercent === null) {
            return $this->legacyGatewayFeeParts($paymentGateway);
        }

        return [
            $this->normalizeFeeValue($defaultFixed ?? 0),
            $this->normalizeFeeValue($defaultPercent ?? 0),
        ];
    }

    private function legacyGatewayFeeParts(PaymentGateway $paymentGateway): array
    {
        if ($paymentGateway->biaya_type === 'persen') {
            return [0.0, $this->normalizeFeeValue($paymentGateway->biaya)];
        }

        return [$this->normalizeFeeValue($paymentGateway->biaya), 0.0];
    }

    private function emptyResolvedPaymentFee(): array
    {
        return [
            'is_available' => false,
            'payment_fee_mode' => null,
            'payment_fee_fixed' => null,
            'payment_fee_percent' => null,
            'internet_fee' => 0,
        ];
    }

    private function formatDecimal(float $value, int $scale): string
    {
        return number_format($value, $scale, '.', '');
    }

    private function normalizeFeeValue($value): float
    {
        return max(0, (float) $value);
    }

    private function hasCompleteFinancialSnapshot(Cart $cart): bool
    {
        return $cart->gross_amount !== null;
    }

    private function storedDecimal($value, int $scale): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return $this->formatDecimal((float) $value, $scale);
    }
}
