<?php

namespace App\Services\Agreements;

use App\Models\Agreement;
use App\Models\Event;
use App\Models\EventDocument;
use App\Models\EventPaymentGateway;
use App\Services\Tickets\TicketPricingService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class AgreementFinalizationService
{
    /**
     * Template version recorded on the finalized agreement / PDF.
     *
     * Kept as a simple explicit constant; no template builder is introduced.
     */
    public const TEMPLATE_VERSION = 'mou-v1';

    /**
     * Finalize the MOU for an event:
     *
     *  1. Re-validates M7 readiness server-side.
     *  2. Freezes live contractual data into Agreement snapshots.
     *  3. Generates the unsigned PDF strictly from those snapshots.
     *  4. Persists the PDF to private storage.
     *  5. Transitions DRAFT -> READY only when everything succeeded.
     *
     * The Agreement row is locked and resolved server-side from the Event;
     * an arbitrary client-provided agreement UID is never trusted.
     *
     * @return array{ok: bool, agreement?: Agreement, unsigned_pdf_path?: string, reason?: string, message?: string, blocking_reasons?: array<int, string>}
     *
     * @throws Throwable When rendering or persisting the PDF fails. In that
     *                   case the DB transaction is rolled back (snapshots and
     *                   status stay untouched) and any newly written PDF file
     *                   is cleaned up.
     */
    public function finalizeForEvent(Event $event, string $actorUid): array
    {
        $disk = Storage::disk('local');
        $writtenPath = null;

        try {
            return DB::transaction(function () use ($event, $actorUid, $disk, &$writtenPath) {
                $agreement = Agreement::query()
                    ->where('event_uid', $event->uid)
                    ->where('type', Agreement::TYPE_MOU)
                    ->where('version', 1)
                    ->lockForUpdate()
                    ->first();

                if (! $agreement) {
                    return $this->failure('not_found', 'Agreement MOU tidak ditemukan untuk event ini.');
                }

                if (! $agreement->isDraft()) {
                    return $this->failure(
                        'not_draft',
                        'Agreement sudah difinalisasi dan tidak dapat difinalisasi ulang.'
                    );
                }

                $freshEvent = Event::query()
                    ->with([
                        'organizer',
                        'bankAccount',
                        'organizerLetter',
                        'eventPaymentGateways.paymentGateway',
                    ])
                    ->where('uid', $event->uid)
                    ->lockForUpdate()
                    ->firstOrFail();

                $review = app(AgreementReviewService::class)->buildForEvent($freshEvent);

                if (! ($review['is_ready'] ?? false)) {
                    return $this->failure(
                        'not_ready',
                        'Event belum memenuhi syarat finalisasi MOU.',
                        $review['blocking_reasons'] ?? []
                    );
                }

                $snapshots = [
                    'event_snapshot' => $this->buildEventSnapshot($freshEvent),
                    'party_snapshot' => $this->buildPartySnapshot($freshEvent),
                    'bank_snapshot' => $this->buildBankSnapshot($freshEvent),
                    'document_snapshot' => $this->buildDocumentSnapshot($freshEvent),
                    'commercial_snapshot' => $this->buildCommercialSnapshot($freshEvent),
                ];

                $payload = $this->buildPdfPayload($agreement, $snapshots);
                $pdfContent = $this->renderPdf($payload);

                $path = 'private/agreements/'.$agreement->uid.'/unsigned.pdf';

                // The local disk uses 'throw' => false, so put() may return
                // false without throwing. Only treat the write as successful
                // when the file is actually present; otherwise abort before
                // the DB record is touched.
                $stored = $disk->put($path, $pdfContent);

                if ($stored !== true || ! $disk->exists($path)) {
                    throw new RuntimeException('Unsigned MOU PDF gagal disimpan.');
                }

                $writtenPath = $path;

                $agreement->fill(array_merge($snapshots, [
                    'template_version' => self::TEMPLATE_VERSION,
                    'unsigned_pdf_path' => $path,
                    'status' => Agreement::STATUS_READY,
                ]))->save();

                $agreement->refresh();

                return [
                    'ok' => true,
                    'agreement' => $agreement,
                    'unsigned_pdf_path' => $path,
                ];
            });
        } catch (Throwable $e) {
            // Any failure after the PDF was successfully written (e.g. during
            // the DB save, refresh, or transaction commit) leaves a stray file
            // behind while the DB rolls back. Remove it so no orphan
            // "authoritative" PDF survives for a DRAFT agreement.
            if ($writtenPath !== null) {
                $this->deleteIfExists($disk, $writtenPath);
            }

            throw $e;
        }
    }

    /**
     * Build the normalized payload used by the PDF view directly from the
     * frozen Agreement snapshots. The blade never reads live event data.
     */
    public function pdfPayloadForAgreement(Agreement $agreement): array
    {
        return $this->buildPdfPayload($agreement, [
            'event_snapshot' => $agreement->event_snapshot ?? [],
            'party_snapshot' => $agreement->party_snapshot ?? [],
            'bank_snapshot' => $agreement->bank_snapshot ?? [],
            'document_snapshot' => $agreement->document_snapshot ?? [],
            'commercial_snapshot' => $agreement->commercial_snapshot ?? [],
        ]);
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

    /**
     * Commercial snapshot uses the same resolved semantics as M6 preview and
     * checkout. Gateway resolved fees are stored in the exact decimal-string
     * format produced by AgreementPreviewService / TicketPricingService so the
     * snapshot never needs the global PaymentGateway to be reconstructed.
     */
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

    /**
     * Mirrors the M6 buyer/event fee resolution used by preview and checkout:
     * event.fee <= 100 is a percentage, event.fee > 100 is a fixed nominal,
     * and 0 means no buyer fee.
     *
     * @return array{0: 'none'|'fixed'|'percent', 1: float}
     */
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

    private function buildPdfPayload(Agreement $agreement, array $snapshots): array
    {
        return [
            'agreement' => [
                'uid' => $agreement->uid,
                'type' => $agreement->type,
                'version' => (int) $agreement->version,
                'status' => Agreement::STATUS_READY,
                'template_version' => $agreement->template_version ?: self::TEMPLATE_VERSION,
                'document_number' => $agreement->document_number,
            ],
            'event' => $snapshots['event_snapshot'],
            'organizer' => $snapshots['party_snapshot'],
            'bank_account' => $snapshots['bank_snapshot'],
            'organizer_letter' => $snapshots['document_snapshot'],
            'commercial' => $snapshots['commercial_snapshot'],
        ];
    }

    private function renderPdf(array $payload): string
    {
        return Pdf::loadView('agreements.mou-pdf', ['payload' => $payload])
            ->setPaper('a4', 'portrait')
            ->output();
    }

    private function deleteIfExists(Filesystem $disk, string $path): void
    {
        try {
            if ($disk->exists($path)) {
                $disk->delete($path);
            }
        } catch (Throwable) {
            // Best effort cleanup only; the original exception takes priority.
        }
    }

    private function failure(string $reason, string $message, array $blockingReasons = []): array
    {
        return [
            'ok' => false,
            'reason' => $reason,
            'message' => $message,
            'blocking_reasons' => $blockingReasons,
        ];
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
