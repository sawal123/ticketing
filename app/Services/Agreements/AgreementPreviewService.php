<?php

namespace App\Services\Agreements;

use App\Models\Agreement;
use App\Models\Event;
use App\Models\EventPaymentGateway;
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

        $activePaymentMethods = $paymentConfigs
            ->where('is_active', true)
            ->map(fn (EventPaymentGateway $config) => $config->paymentGateway->payment)
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
                'ticket_tax_percent' => $this->formatPercent($event->fee),
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
                'payment_gateways' => $paymentConfigs->map(function (EventPaymentGateway $config) {
                    $gateway = $config->paymentGateway;
                    $feeMode = $config->fee_mode ?: EventPaymentGateway::FEE_MODE_GLOBAL;

                    $resolvedFixed = $feeMode === EventPaymentGateway::FEE_MODE_MANUAL
                        ? $config->fee_fixed
                        : $gateway->default_fee_fixed;
                    $resolvedPercent = $feeMode === EventPaymentGateway::FEE_MODE_MANUAL
                        ? $config->fee_percent
                        : $gateway->default_fee_percent;

                    return [
                        'payment' => $gateway->payment,
                        'is_active' => (bool) $config->is_active,
                        'fee_mode' => $feeMode,
                        'resolved_fee_fixed' => $this->formatCurrency($resolvedFixed),
                        'resolved_fee_percent' => $this->formatPercent($resolvedPercent),
                    ];
                })->all(),
            ],
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

    private function formatPercent($value): string
    {
        if ($value === null || $value === '') {
            return '0';
        }

        return rtrim(rtrim(number_format((float) $value, 4, '.', ''), '0'), '.') ?: '0';
    }
}
