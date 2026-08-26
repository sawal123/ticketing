<?php

namespace App\Services\Agreements;

use App\Models\Agreement;
use App\Models\Event;
use App\Models\EventPaymentGateway;
use Illuminate\Support\Facades\Storage;

class AgreementReviewService
{
    private const ACTIVATION_REVIEW_KEYS = [
        'bank_account_available',
        'physical_bank_book_available',
        'bank_account_verified',
        'organizer_letter_available',
        'physical_organizer_letter_available',
        'organizer_letter_verified',
        'payment_configuration_valid',
        'effective_active_gateway',
    ];

    public function buildForEvent(Event $event): array
    {
        $event->loadMissing([
            'currentMouAgreement',
            'organizer',
            'bankAccount',
            'organizerLetter',
            'eventPaymentGateways.paymentGateway',
        ]);

        $commercial = app(AgreementPreviewService::class)->buildCommercialSummaryForEvent($event);
        $organizer = $event->organizer;
        $bankAccount = $event->bankAccount;
        $organizerLetter = $event->organizerLetter;
        $disk = Storage::disk('local');

        $organizerComplete = collect([
            $organizer?->organizer_name,
            $organizer?->responsible_name,
            $organizer?->responsible_position,
            $organizer?->phone,
            $organizer?->email,
            $organizer?->address,
        ])->every(fn ($value) => filled($value));

        $bankRecordAvailable = $bankAccount !== null
            && filled($bankAccount->bank_name)
            && filled($bankAccount->account_number)
            && filled($bankAccount->account_holder_name);
        $bankBookAvailable = filled($bankAccount?->bank_book_path);
        $bankBookPhysicalAvailable = $bankBookAvailable && $disk->exists($bankAccount->bank_book_path);
        $bankVerified = strtolower((string) $bankAccount?->status) === 'verified';

        $organizerLetterAvailable = $organizerLetter !== null
            && filled($organizerLetter->document_number)
            && filled($organizerLetter->document_date)
            && filled($organizerLetter->original_name)
            && filled($organizerLetter->file_path);
        $organizerLetterPhysicalAvailable = filled($organizerLetter?->file_path)
            && $disk->exists($organizerLetter->file_path);
        $organizerLetterVerified = strtolower((string) $organizerLetter?->status) === 'verified';

        $paymentGateways = collect($commercial['payment_gateways'] ?? []);
        $paymentConfigValid = $this->paymentConfigIsValid($commercial);
        $hasEffectiveActiveGateway = $paymentGateways
            ->contains(fn (array $gateway) => (bool) ($gateway['effective_is_active'] ?? false));

        $items = [
            $this->checkItem(
                'agreement_available',
                'Agreement MOU tersedia',
                $event->currentMouAgreement !== null,
                'Agreement MOU draft belum tersedia.'
            ),
            $this->checkItem(
                'organizer_complete',
                'Organizer lengkap',
                $organizerComplete,
                'Data organizer belum lengkap.'
            ),
            $this->checkItem(
                'bank_account_available',
                'Rekening Event tersedia',
                $bankRecordAvailable,
                'Rekening event belum lengkap.'
            ),
            $this->checkItem(
                'physical_bank_book_available',
                'Physical bank book tersedia',
                $bankBookPhysicalAvailable,
                $bankBookAvailable
                    ? 'File buku rekening fisik tidak ditemukan.'
                    : 'File buku rekening belum tersedia.'
            ),
            $this->checkItem(
                'bank_account_verified',
                'Rekening VERIFIED',
                $bankVerified,
                'Rekening event belum diverifikasi.'
            ),
            $this->checkItem(
                'organizer_letter_available',
                'Organizer letter tersedia',
                $organizerLetterAvailable,
                'Surat penyelenggara belum lengkap.'
            ),
            $this->checkItem(
                'physical_organizer_letter_available',
                'Physical organizer letter tersedia',
                $organizerLetterPhysicalAvailable,
                filled($organizerLetter?->file_path)
                    ? 'File surat penyelenggara fisik tidak ditemukan.'
                    : 'File surat penyelenggara belum tersedia.'
            ),
            $this->checkItem(
                'organizer_letter_verified',
                'Organizer letter VERIFIED',
                $organizerLetterVerified,
                'Surat penyelenggara belum diverifikasi.'
            ),
            $this->checkItem(
                'payment_configuration_valid',
                'Konfigurasi payment valid',
                $paymentConfigValid,
                'Konfigurasi payment event belum valid.'
            ),
            $this->checkItem(
                'effective_active_gateway',
                'Minimal 1 payment gateway EFFECTIVE ACTIVE',
                $hasEffectiveActiveGateway,
                'Belum ada payment gateway event yang efektif aktif.'
            ),
        ];

        $blockingReasons = collect($items)
            ->filter(fn (array $item) => ! $item['passed'])
            ->pluck('reason')
            ->filter()
            ->values()
            ->all();

        $isReady = $blockingReasons === [];

        return [
            'is_ready' => $isReady,
            'status_label' => $isReady ? 'SIAP FINALISASI' : 'BELUM SIAP FINALISASI',
            'items' => $items,
            'blocking_reasons' => $blockingReasons,
        ];
    }

    public function activationPrerequisiteItemsForEvent(Event $event): array
    {
        return collect($this->buildForEvent($event)['items'] ?? [])
            ->filter(fn (array $item) => in_array($item['key'] ?? null, self::ACTIVATION_REVIEW_KEYS, true))
            ->values()
            ->all();
    }

    private function paymentConfigIsValid(array $commercial): bool
    {
        $ticketTax = $commercial['ticket_tax'] ?? [];
        $paymentGateways = collect($commercial['payment_gateways'] ?? []);

        if (! in_array($ticketTax['mode'] ?? null, ['none', 'fixed', 'percent'], true)) {
            return false;
        }

        if ($paymentGateways->isEmpty()) {
            return false;
        }

        return $paymentGateways->every(function (array $gateway): bool {
            if (! filled($gateway['payment'] ?? null)) {
                return false;
            }

            if (! in_array($gateway['fee_mode'] ?? null, [
                EventPaymentGateway::FEE_MODE_GLOBAL,
                EventPaymentGateway::FEE_MODE_MANUAL,
            ], true)) {
                return false;
            }

            return array_key_exists('resolved_fee_fixed', $gateway)
                && array_key_exists('resolved_fee_percent', $gateway);
        });
    }

    private function checkItem(string $key, string $label, bool $passed, string $reason): array
    {
        return [
            'key' => $key,
            'label' => $label,
            'passed' => $passed,
            'reason' => $passed ? null : $reason,
        ];
    }
}
