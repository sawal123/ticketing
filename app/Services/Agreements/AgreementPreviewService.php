<?php

namespace App\Services\Agreements;

use App\Models\Agreement;
use App\Models\Event;
use App\Models\EventPaymentGateway;
use App\Models\PaymentGateway;
use App\Services\Tickets\TicketPricingService;
use Carbon\Carbon;

class AgreementPreviewService
{
    public function buildForEvent(Event $event): ?array
    {
        $event->loadMissing([
            'currentMouAgreement',
            'organizer',
            'bankAccount',
            'organizerLetter',
            'eventPaymentGateways.paymentGateway',
        ]);

        $agreement = $event->currentMouAgreement;

        if (! $agreement) {
            return null;
        }

        $organizer = $event->organizer;
        $bankAccount = $event->bankAccount;
        $organizerLetter = $event->organizerLetter;
        $paymentConfigs = $event->eventPaymentGateways
            ->filter(fn (EventPaymentGateway $config) => $config->paymentGateway !== null)
            ->sortBy(fn (EventPaymentGateway $config) => mb_strtolower((string) $config->paymentGateway->payment))
            ->values();
        $buyerFee = $this->resolveBuyerFee($event);

        $activePaymentMethods = $paymentConfigs
            ->map(fn (EventPaymentGateway $config) => $this->resolveGatewayFee($config))
            ->filter(fn (array $gateway) => $gateway['effective_is_active'])
            ->map(fn (array $gateway) => $gateway['payment'])
            ->values()
            ->all();

        return [
            'agreement' => [
                'uid' => $agreement->uid,
                'type' => $agreement->type,
                'version' => (int) $agreement->version,
                'status' => $agreement->status,
            ],
            'event' => [
                'name' => $event->event,
                'start_sale' => $this->formatDateTime($event->start_sale),
                'start' => $this->formatDateTime($event->tanggal),
                'end' => $this->formatDateTime($event->event_end),
                'venue_name' => $event->venue_name ?: '-',
                'venue_address' => $event->venue_address ?: ($event->alamat ?: '-'),
                'venue_city' => $event->venue_city ?: '-',
                'venue_province' => $event->venue_province ?: '-',
                'legacy_address' => $event->alamat ?: '-',
                'ticket_tax' => $buyerFee,
            ],
            'organizer' => [
                'organizer_name' => $organizer?->organizer_name ?: '-',
                'responsible_name' => $organizer?->responsible_name ?: '-',
                'responsible_position' => $organizer?->responsible_position ?: '-',
                'phone' => $organizer?->phone ?: '-',
                'email' => $organizer?->email ?: '-',
                'address' => $organizer?->address ?: '-',
            ],
            'bank_account' => [
                'bank_name' => $bankAccount?->bank_name ?: '-',
                'account_number' => $bankAccount?->account_number ?: '-',
                'account_holder_name' => $bankAccount?->account_holder_name ?: '-',
                'verification_status' => $bankAccount?->status ?: 'Belum dikonfigurasi',
            ],
            'organizer_letter' => [
                'document_number' => $organizerLetter?->document_number ?: '-',
                'document_date' => $this->formatDate($organizerLetter?->document_date),
                'original_name' => $organizerLetter?->original_name ?: '-',
                'verification_status' => $organizerLetter?->status ?: 'Belum dikonfigurasi',
            ],
            'commercial' => [
                'payment_otp_enabled' => (bool) $event->payment_otp_enabled,
                'active_payment_methods' => $activePaymentMethods,
                'payment_gateways' => $paymentConfigs->map(fn (EventPaymentGateway $config) => $this->resolveGatewayFee($config))->all(),
            ],
        ];
    }

    private function resolveBuyerFee(Event $event): array
    {
        [$taxPercent, $taxAmount] = app(TicketPricingService::class)->tax($event, 0);

        if ($taxPercent > 0) {
            return [
                'mode' => 'percent',
                'mode_label' => 'Persentase',
                'value' => $this->formatPercent($taxPercent).'%',
            ];
        }

        if ($taxAmount > 0) {
            return [
                'mode' => 'fixed',
                'mode_label' => 'Nominal Tetap',
                'value' => 'Rp '.$this->formatCurrencyForDisplay($taxAmount),
            ];
        }

        return [
            'mode' => 'none',
            'mode_label' => '-',
            'value' => 'Rp 0 / 0%',
        ];
    }

    private function resolveGatewayFee(EventPaymentGateway $config): array
    {
        $gateway = $config->paymentGateway;
        [$resolvedFixed, $resolvedPercent] = $this->resolveGatewayFeeParts($config, $gateway);
        $eventIsActive = (bool) $config->is_active;
        $globalIsActive = (bool) $gateway->is_active;

        return [
            'payment' => $gateway->payment,
            'event_is_active' => $eventIsActive,
            'global_is_active' => $globalIsActive,
            'effective_is_active' => $eventIsActive && $globalIsActive,
            'fee_mode' => $config->fee_mode ?: EventPaymentGateway::FEE_MODE_GLOBAL,
            'resolved_fee_fixed' => $this->formatCurrency($resolvedFixed),
            'resolved_fee_percent' => $this->formatPercent($resolvedPercent),
        ];
    }

    private function resolveGatewayFeeParts(EventPaymentGateway $config, PaymentGateway $gateway): array
    {
        if ($config->fee_mode === EventPaymentGateway::FEE_MODE_MANUAL) {
            return [
                $this->normalizeFeeValue($config->fee_fixed ?? 0),
                $this->normalizeFeeValue($config->fee_percent ?? 0),
            ];
        }

        $defaultFixed = $gateway->getAttribute('default_fee_fixed');
        $defaultPercent = $gateway->getAttribute('default_fee_percent');

        if ($defaultFixed === null || $defaultPercent === null) {
            if ($gateway->biaya_type === 'persen') {
                return [0.0, $this->normalizeFeeValue($gateway->biaya)];
            }

            return [$this->normalizeFeeValue($gateway->biaya), 0.0];
        }

        return [
            $this->normalizeFeeValue($defaultFixed ?? 0),
            $this->normalizeFeeValue($defaultPercent ?? 0),
        ];
    }

    private function formatDateTime($value): string
    {
        if (blank($value)) {
            return '-';
        }

        return Carbon::parse($value)->format('d-m-Y H:i');
    }

    private function formatDate($value): string
    {
        if (blank($value)) {
            return '-';
        }

        return Carbon::parse($value)->format('d-m-Y');
    }

    private function formatCurrency($value): string
    {
        if ($value === null || $value === '') {
            return '0.00';
        }

        return number_format((float) $value, 2, '.', '');
    }

    private function formatCurrencyForDisplay($value): string
    {
        return number_format((float) $value, 0, ',', '.');
    }

    private function formatPercent($value): string
    {
        if ($value === null || $value === '') {
            return '0';
        }

        return rtrim(rtrim(number_format((float) $value, 4, '.', ''), '0'), '.') ?: '0';
    }

    private function normalizeFeeValue($value): float
    {
        return max(0, (float) $value);
    }
}
