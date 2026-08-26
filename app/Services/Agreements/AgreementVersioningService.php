<?php

namespace App\Services\Agreements;

use App\Models\Agreement;
use App\Models\Event;
use App\Models\EventDocument;
use App\Models\EventPaymentGateway;
use App\Services\Tickets\TicketPricingService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AgreementVersioningService
{
    public const TEMPLATE_VERSION = 'addendum-v1';

    /**
     * Check if contractual changes occurred on an event that has a completed agreement.
     * If so, create or return an existing DRAFT Addendum.
     */
    public function checkForContractualChanges(Event $event, ?string $actorUid = null): ?Agreement
    {
        if (! \Illuminate\Support\Facades\Schema::hasTable('agreements')) {
            return null;
        }

        return DB::transaction(function () use ($event, $actorUid) {
            $latestCompleted = $this->getLatestCompletedAgreement($event);

            if (! $latestCompleted) {
                return null;
            }

            $freshEvent = Event::query()
                ->with([
                    'organizer',
                    'bankAccount',
                    'organizerLetter',
                    'eventPaymentGateways.paymentGateway',
                ])
                ->where('uid', $event->uid)
                ->first();

            if (! $freshEvent) {
                return null;
            }

            $liveSnapshots = $this->buildLiveSnapshots($freshEvent);

            if (! $this->hasContractualDiff($liveSnapshots, $latestCompleted)) {
                return null;
            }

            // If an open (non-completed) Addendum exists, reuse it without duplicating
            $existingDraft = Agreement::query()
                ->where('event_uid', $freshEvent->uid)
                ->where('type', Agreement::TYPE_ADDENDUM)
                ->whereNotIn('status', [Agreement::STATUS_COMPLETED, Agreement::STATUS_CANCELLED])
                ->latest('version')
                ->first();

            if ($existingDraft) {
                return $existingDraft;
            }

            $lastAddendumVersion = Agreement::query()
                ->where('event_uid', $freshEvent->uid)
                ->where('type', Agreement::TYPE_ADDENDUM)
                ->max('version');

            $nextVersion = ($lastAddendumVersion ?? 0) + 1;

            $addendum = new Agreement();
            $addendum->forceFill([
                'uid' => (string) Str::uuid(),
                'event_uid' => $freshEvent->uid,
                'tenant_user_uid' => $freshEvent->user_uid,
                'type' => Agreement::TYPE_ADDENDUM,
                'parent_agreement_uid' => $latestCompleted->uid,
                'version' => $nextVersion,
                'status' => Agreement::STATUS_DRAFT,
                'created_by' => $actorUid ?? $freshEvent->user_uid,
            ]);
            $addendum->save();

            return $addendum->fresh();
        });
    }

    public function getLatestCompletedAgreement(Event $event): ?Agreement
    {
        if (! \Illuminate\Support\Facades\Schema::hasTable('agreements')) {
            return null;
        }

        return Agreement::query()
            ->where('event_uid', $event->uid)
            ->where('status', Agreement::STATUS_COMPLETED)
            ->orderByRaw("CASE WHEN type = 'addendum' THEN 2 ELSE 1 END DESC")
            ->orderByDesc('version')
            ->first();
    }

    public function buildLiveSnapshots(Event $event): array
    {
        return [
            'event_snapshot' => $this->buildEventSnapshot($event),
            'party_snapshot' => $this->buildPartySnapshot($event),
            'bank_snapshot' => $this->buildBankSnapshot($event),
            'document_snapshot' => $this->buildDocumentSnapshot($event),
            'commercial_snapshot' => $this->buildCommercialSnapshot($event),
        ];
    }

    public function hasContractualDiff(array $liveSnapshots, Agreement $parentAgreement): bool
    {
        $diffs = $this->computeDiffs($liveSnapshots, $parentAgreement);

        return count($diffs) > 0;
    }

    public function computeDiffs(array $afterSnapshots, Agreement $parentAgreement): array
    {
        $diffs = [];

        // 1. Event main
        $beforeEvent = $parentAgreement->event_snapshot ?? [];
        $afterEvent = $afterSnapshots['event_snapshot'] ?? [];

        $eventFields = [
            'event_name' => 'Nama Event',
            'start' => 'Waktu Mulai',
            'end' => 'Waktu Selesai',
            'venue_name' => 'Nama Venue',
            'venue_address' => 'Alamat Venue',
            'venue_city' => 'Kota',
            'venue_province' => 'Provinsi',
            'start_sale' => 'Mulai Penjualan',
        ];

        foreach ($eventFields as $field => $label) {
            $beforeVal = (string) ($beforeEvent[$field] ?? '');
            $afterVal = (string) ($afterEvent[$field] ?? '');

            if ($beforeVal !== $afterVal) {
                $diffs[] = [
                    'section' => 'Event',
                    'field' => $field,
                    'label' => $label,
                    'before' => $beforeVal ?: '-',
                    'after' => $afterVal ?: '-',
                ];
            }
        }

        // Buyer fee in event
        $beforeFeeMode = (string) ($beforeEvent['buyer_fee']['mode'] ?? 'none');
        $afterFeeMode = (string) ($afterEvent['buyer_fee']['mode'] ?? 'none');
        $beforeFeeVal = (float) ($beforeEvent['buyer_fee']['value'] ?? 0);
        $afterFeeVal = (float) ($afterEvent['buyer_fee']['value'] ?? 0);

        if ($beforeFeeMode !== $afterFeeMode || abs($beforeFeeVal - $afterFeeVal) > 0.0001) {
            $diffs[] = [
                'section' => 'Event',
                'field' => 'buyer_fee',
                'label' => 'Biaya Pembeli (Platform Fee)',
                'before' => $this->formatFeeDisplay($beforeFeeMode, $beforeFeeVal),
                'after' => $this->formatFeeDisplay($afterFeeMode, $afterFeeVal),
            ];
        }

        // 2. Organizer
        $beforeParty = $parentAgreement->party_snapshot ?? [];
        $afterParty = $afterSnapshots['party_snapshot'] ?? [];

        $partyFields = [
            'organizer_name' => 'Nama Penyelenggara',
            'responsible_name' => 'Nama Penanggung Jawab',
            'responsible_position' => 'Jabatan Penanggung Jawab',
            'phone' => 'No. Telepon',
            'email' => 'Email Penyelenggara',
            'address' => 'Alamat Penyelenggara',
        ];

        foreach ($partyFields as $field => $label) {
            $beforeVal = (string) ($beforeParty[$field] ?? '');
            $afterVal = (string) ($afterParty[$field] ?? '');

            if ($beforeVal !== $afterVal) {
                $diffs[] = [
                    'section' => 'Penyelenggara',
                    'field' => $field,
                    'label' => $label,
                    'before' => $beforeVal ?: '-',
                    'after' => $afterVal ?: '-',
                ];
            }
        }

        // 3. Bank Account
        $beforeBank = $parentAgreement->bank_snapshot ?? [];
        $afterBank = $afterSnapshots['bank_snapshot'] ?? [];

        $bankFields = [
            'bank_name' => 'Nama Bank',
            'account_number' => 'Nomor Rekening',
            'account_holder_name' => 'Nama Pemilik Rekening',
            'verification_status' => 'Status Verifikasi Rekening',
        ];

        foreach ($bankFields as $field => $label) {
            $beforeVal = (string) ($beforeBank[$field] ?? '');
            $afterVal = (string) ($afterBank[$field] ?? '');

            if ($beforeVal !== $afterVal) {
                $diffs[] = [
                    'section' => 'Rekening Bank',
                    'field' => $field,
                    'label' => $label,
                    'before' => $beforeVal ?: '-',
                    'after' => $afterVal ?: '-',
                ];
            }
        }

        // 4. Organizer Letter
        $beforeDoc = $parentAgreement->document_snapshot ?? [];
        $afterDoc = $afterSnapshots['document_snapshot'] ?? [];

        $docFields = [
            'document_type' => 'Jenis Surat',
            'document_number' => 'Nomor Surat',
            'document_date' => 'Tanggal Surat',
            'original_name' => 'Nama File Surat',
            'verification_status' => 'Status Verifikasi Surat',
        ];

        foreach ($docFields as $field => $label) {
            $beforeVal = (string) ($beforeDoc[$field] ?? '');
            $afterVal = (string) ($afterDoc[$field] ?? '');

            if ($beforeVal !== $afterVal) {
                $diffs[] = [
                    'section' => 'Surat Penyelenggara',
                    'field' => $field,
                    'label' => $label,
                    'before' => $beforeVal ?: '-',
                    'after' => $afterVal ?: '-',
                ];
            }
        }

        // 5. Commercial / Payment Gateways
        $beforeComm = $parentAgreement->commercial_snapshot ?? [];
        $afterComm = $afterSnapshots['commercial_snapshot'] ?? [];

        $beforeOtp = (bool) ($beforeComm['payment_otp_enabled'] ?? false);
        $afterOtp = (bool) ($afterComm['payment_otp_enabled'] ?? false);

        if ($beforeOtp !== $afterOtp) {
            $diffs[] = [
                'section' => 'Pembayaran',
                'field' => 'payment_otp_enabled',
                'label' => 'Payment OTP',
                'before' => $beforeOtp ? 'Aktif' : 'Nonaktif',
                'after' => $afterOtp ? 'Aktif' : 'Nonaktif',
            ];
        }

        $beforeGateways = collect($beforeComm['payment_gateways'] ?? [])
            ->keyBy(fn ($g) => (string) ($g['payment_gateway_id'] ?? $g['gateway_name'] ?? ''));
        $afterGateways = collect($afterComm['payment_gateways'] ?? [])
            ->keyBy(fn ($g) => (string) ($g['payment_gateway_id'] ?? $g['gateway_name'] ?? ''));

        $allGatewayKeys = $beforeGateways->keys()->merge($afterGateways->keys())->unique();

        foreach ($allGatewayKeys as $key) {
            $bg = $beforeGateways->get($key);
            $ag = $afterGateways->get($key);

            $gName = $ag['gateway_name'] ?? $bg['gateway_name'] ?? $key;

            $bActive = (bool) ($bg['is_active'] ?? false);
            $aActive = (bool) ($ag['is_active'] ?? false);
            $bMode = (string) ($bg['fee_mode'] ?? 'global');
            $aMode = (string) ($ag['fee_mode'] ?? 'global');
            $bFixed = (string) ($bg['resolved_fee_fixed'] ?? '0.00');
            $aFixed = (string) ($ag['resolved_fee_fixed'] ?? '0.00');
            $bPercent = (string) ($bg['resolved_fee_percent'] ?? '0');
            $aPercent = (string) ($ag['resolved_fee_percent'] ?? '0');

            if ($bActive !== $aActive || $bMode !== $aMode || $bFixed !== $aFixed || $bPercent !== $aPercent) {
                $bDesc = $bActive ? "Aktif [{$bMode}] Fixed: Rp {$bFixed}, {$bPercent}%" : 'Nonaktif';
                $aDesc = $aActive ? "Aktif [{$aMode}] Fixed: Rp {$aFixed}, {$aPercent}%" : 'Nonaktif';

                $diffs[] = [
                    'section' => 'Payment Gateway',
                    'field' => 'gateway_'.$key,
                    'label' => 'Gateway: '.$gName,
                    'before' => $bDesc,
                    'after' => $aDesc,
                ];
            }
        }

        return $diffs;
    }

    public function buildAddendumPreview(Event $event, Agreement $addendum): array
    {
        $parent = $addendum->parentAgreement
            ?? Agreement::where('uid', $addendum->parent_agreement_uid)->first()
            ?? $this->getLatestCompletedAgreement($event);

        $afterSnapshots = ($addendum->isCompleted() || $addendum->isReady())
            ? [
                'event_snapshot' => $addendum->event_snapshot ?? [],
                'party_snapshot' => $addendum->party_snapshot ?? [],
                'bank_snapshot' => $addendum->bank_snapshot ?? [],
                'document_snapshot' => $addendum->document_snapshot ?? [],
                'commercial_snapshot' => $addendum->commercial_snapshot ?? [],
            ]
            : $this->buildLiveSnapshots($event);

        $diffs = $parent ? $this->computeDiffs($afterSnapshots, $parent) : [];

        return [
            'agreement' => [
                'uid' => $addendum->uid,
                'type' => $addendum->type,
                'version' => (int) $addendum->version,
                'status' => $addendum->status,
                'document_number' => $addendum->document_number,
                'parent_agreement_uid' => $parent?->uid,
                'parent_type' => $parent?->type,
                'parent_version' => $parent?->version,
                'completed_at' => $addendum->completed_at?->format('d-m-Y H:i'),
            ],
            'parent_agreement' => $parent ? [
                'uid' => $parent->uid,
                'type' => $parent->type,
                'version' => (int) $parent->version,
                'status' => $parent->status,
                'document_number' => $parent->document_number,
                'completed_at' => $parent->completed_at?->format('d-m-Y H:i'),
            ] : null,
            'diffs' => $diffs,
            'after' => $afterSnapshots,
        ];
    }

    private function buildEventSnapshot(Event $event): array
    {
        [$buyerFeeMode, $buyerFeeValue] = $this->resolveBuyerFeeModeAndValue($event);

        return [
            'event_uid' => $event->uid,
            'event_name' => $event->event,
            'start' => $this->formatDateTime($event->tanggal),
            'end' => $this->formatDateTime($event->event_end),
            'venue_name' => $event->venue_name,
            'venue_address' => $event->venue_address ?: $event->alamat,
            'venue_city' => $event->venue_city,
            'venue_province' => $event->venue_province,
            'start_sale' => $this->formatDateTime($event->start_sale),
            'buyer_fee' => [
                'mode' => $buyerFeeMode,
                'value' => $buyerFeeValue,
            ],
        ];
    }

    private function buildPartySnapshot(Event $event): array
    {
        $organizer = $event->organizer;

        return [
            'organizer_name' => $organizer?->organizer_name,
            'responsible_name' => $organizer?->responsible_name,
            'responsible_position' => $organizer?->responsible_position,
            'phone' => $organizer?->phone,
            'email' => $organizer?->email,
            'address' => $organizer?->address,
        ];
    }

    private function buildBankSnapshot(Event $event): array
    {
        $bankAccount = $event->bankAccount;

        return [
            'bank_name' => $bankAccount?->bank_name,
            'account_number' => $bankAccount?->account_number,
            'account_holder_name' => $bankAccount?->account_holder_name,
            'verification_status' => $bankAccount?->status,
            'verified_by' => $bankAccount?->verified_by,
            'verified_at' => $this->formatDateTime($bankAccount?->verified_at),
        ];
    }

    private function buildDocumentSnapshot(Event $event): array
    {
        $document = $event->organizerLetter;

        return [
            'document_type' => $document?->document_type ?? EventDocument::TYPE_ORGANIZER_LETTER,
            'document_number' => $document?->document_number,
            'document_date' => $this->formatDate($document?->document_date),
            'original_name' => $document?->original_name,
            'verification_status' => $document?->status,
            'verified_by' => $document?->verified_by,
            'verified_at' => $this->formatDateTime($document?->verified_at),
        ];
    }

    private function buildCommercialSnapshot(Event $event): array
    {
        $previewService = app(AgreementPreviewService::class);

        $gateways = $event->eventPaymentGateways
            ->filter(fn (EventPaymentGateway $config) => $config->paymentGateway !== null)
            ->sortBy(fn (EventPaymentGateway $config) => mb_strtolower((string) $config->paymentGateway->payment))
            ->map(fn (EventPaymentGateway $config) => $previewService->resolveGatewayFeeSnapshot($config))
            ->values()
            ->all();

        [$buyerFeeMode, $buyerFeeValue] = $this->resolveBuyerFeeModeAndValue($event);

        return [
            'buyer_fee' => [
                'mode' => $buyerFeeMode,
                'value' => $buyerFeeValue,
            ],
            'payment_otp_enabled' => (bool) $event->payment_otp_enabled,
            'payment_gateways' => $gateways,
        ];
    }

    private function resolveBuyerFeeModeAndValue(Event $event): array
    {
        [$taxPercent, $taxAmount] = app(TicketPricingService::class)->tax($event, 0);

        if ((float) $taxPercent > 0) {
            return ['percent', (float) $taxPercent];
        }

        if ((float) $taxAmount > 0) {
            return ['fixed', (float) $taxAmount];
        }

        return ['none', 0.0];
    }

    private function formatFeeDisplay(string $mode, float $value): string
    {
        return match ($mode) {
            'percent' => $value.'%',
            'fixed' => 'Rp '.number_format($value, 0, ',', '.'),
            default => 'None / Rp 0',
        };
    }

    private function formatDateTime($value): ?string
    {
        if (blank($value)) {
            return null;
        }

        return Carbon::parse($value)->format('d-m-Y H:i');
    }

    private function formatDate($value): ?string
    {
        if (blank($value)) {
            return null;
        }

        return Carbon::parse($value)->format('d-m-Y');
    }
}
