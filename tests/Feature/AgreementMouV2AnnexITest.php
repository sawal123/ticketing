<?php

namespace Tests\Feature;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\View;
use Tests\TestCase;

class AgreementMouV2AnnexITest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        View::share('logo', [(object) ['logo' => '', 'icon' => '']]);
    }

    public function test_pdf_and_preview_render_annex_i_from_shared_partial(): void
    {
        $payload = $this->fixturePayload();

        $pdfHtml = view('agreements.mou-v2-pdf', ['payload' => $payload])->render();
        $previewHtml = view('agreements.mou-v2-preview', ['payload' => $payload])->render();

        foreach ([$pdfHtml, $previewHtml] as $html) {
            $this->assertStringContainsString('mou-v2-contract-shared-body', $html);
            $this->assertStringContainsString('mou-v2-annex-i-shared-body', $html);
            $this->assertStringContainsString('LAMPIRAN I', $html);
            $this->assertStringContainsString('DATA EVENT DAN INFORMASI PENCAIRAN', $html);
            $this->assertStringNotContainsString('DATA EVENT DAN KETENTUAN KOMERSIAL', $html);
            $this->assertStringContainsString('Festival Nada Nusantara', $html);
            $this->assertStringContainsString('PT Ruang Pertunjukan Nusantara', $html);
            $this->assertStringContainsString('10-09-2026 19:00', $html);
            $this->assertStringContainsString('10-09-2026 22:00', $html);
            $this->assertStringContainsString('Balai Pertunjukan Senandika', $html);
            $this->assertStringContainsString('Jl. Melodi Raya No. 88', $html);
            $this->assertStringContainsString('Jakarta Selatan', $html);
            $this->assertStringContainsString('DKI Jakarta', $html);
            $this->assertStringContainsString('01-09-2026 10:00', $html);
            $this->assertStringContainsString('Rekening Pencairan', $html);
            $this->assertStringContainsString('Nama Bank', $html);
            $this->assertStringContainsString('1234567890', $html);
            $this->assertStringContainsString('PT Ruang Pertunjukan Nusantara', $html);
            $this->assertStringContainsString('verified', $html);
            $this->assertStringContainsString('Agreement UID', $html);
            $this->assertStringContainsString('Template Version', $html);
            $this->assertStringNotContainsString('Biaya Pembeli / Event Fee', $html);
            $this->assertStringNotContainsString('Kanal Pembayaran', $html);
            $this->assertStringNotContainsString('Rp 2.000', $html);
            $this->assertStringNotContainsString('Rp 2.000 + 3%', $html);
            $this->assertStringNotContainsString('Rp 0 + 3%', $html);
            $this->assertStringNotContainsString('Rp 4.000 + 0%', $html);
            $this->assertStringNotContainsString('BCA Virtual Account Super Long Gateway Name For Snapshot Rendering', $html);
            $this->assertStringNotContainsString('QRIS Promo Snapshot', $html);
            $this->assertStringNotContainsString('Transfer Manual Snapshot', $html);
            $this->assertStringNotContainsString('Injected Ticket Category Marker', $html);
            $this->assertStringNotContainsString('Injected Ticket Name Marker', $html);
            $this->assertStringNotContainsString('Injected Ticket Benefit Marker', $html);
            $this->assertStringNotContainsString('777111', $html);
            $this->assertStringNotContainsString('888222', $html);
            $this->assertStringNotContainsString('private/events/secret-bank-book.pdf', $html);
            $this->assertStringNotContainsString('internal-verifier-uid', $html);
            $this->assertStringNotContainsString('private/live/gateway/config', $html);
            $this->assertStringNotContainsString('Live Gateway Should Not Render', $html);
            $this->assertStringNotContainsString('payment_otp_enabled', $html);
        }

        $pdfBinary = Pdf::loadView('agreements.mou-v2-pdf', ['payload' => $payload])
            ->setPaper('a4', 'portrait')
            ->output();

        $this->assertNotEmpty($pdfBinary);
        $this->assertStringStartsWith('%PDF', $pdfBinary);
    }

    public function test_annex_does_not_render_commercial_snapshot_fields_even_when_payload_contains_them(): void
    {
        $payload = $this->fixturePayload();
        $payload['commercial']['buyer_fee'] = ['mode' => 'fixed', 'value' => 987654];
        $payload['commercial']['payment_gateways'] = [[
            'payment' => 'Gateway Hidden Marker',
            'effective_is_active' => true,
            'resolved_fee_fixed' => '54321.00',
            'resolved_fee_percent' => '1.75',
        ]];

        $pdfHtml = view('agreements.mou-v2-pdf', ['payload' => $payload])->render();
        $previewHtml = view('agreements.mou-v2-preview', ['payload' => $payload])->render();

        $this->assertSame(987654, $payload['commercial']['buyer_fee']['value']);
        $this->assertCount(1, $payload['commercial']['payment_gateways']);
        $this->assertStringContainsString('Rekening Pencairan', $pdfHtml);
        $this->assertStringContainsString('Rekening Pencairan', $previewHtml);
        $this->assertStringNotContainsString('Rp 987.654', $pdfHtml);
        $this->assertStringNotContainsString('Rp 987.654', $previewHtml);
        $this->assertStringNotContainsString('Gateway Hidden Marker', $pdfHtml);
        $this->assertStringNotContainsString('Gateway Hidden Marker', $previewHtml);
        $this->assertStringNotContainsString('Rp 54.321 + 1.75%', $pdfHtml);
        $this->assertStringNotContainsString('Rp 54.321 + 1.75%', $previewHtml);
    }

    public function test_annex_renders_null_fields_safely_without_rendering_commercial_data(): void
    {
        $payload = $this->fixturePayload([
            'event' => [
                'event_name' => null,
                'start' => null,
                'end' => null,
                'venue_name' => null,
                'venue_address' => null,
                'venue_city' => null,
                'venue_province' => null,
                'start_sale' => null,
            ],
            'organizer' => [
                'organizer_name' => null,
            ],
            'bank_account' => [
                'bank_name' => null,
                'account_number' => null,
                'account_holder_name' => null,
                'verification_status' => null,
            ],
        ]);
        $payload['commercial']['buyer_fee'] = ['mode' => 'percent', 'value' => 12.5];
        $payload['commercial']['payment_gateways'] = [[
            'payment' => 'Gateway Null Hidden Marker',
            'effective_is_active' => false,
            'resolved_fee_fixed' => '76543.00',
            'resolved_fee_percent' => '9.5',
        ]];

        $pdfHtml = view('agreements.mou-v2-pdf', ['payload' => $payload])->render();
        $previewHtml = view('agreements.mou-v2-preview', ['payload' => $payload])->render();

        $this->assertStringContainsString('DATA EVENT DAN INFORMASI PENCAIRAN', $pdfHtml);
        $this->assertStringContainsString('DATA EVENT DAN INFORMASI PENCAIRAN', $previewHtml);
        $this->assertStringContainsString('<td>-</td>', $pdfHtml);
        $this->assertStringContainsString('<td>-</td>', $previewHtml);
        $this->assertStringNotContainsString('12.5%', $pdfHtml);
        $this->assertStringNotContainsString('12.5%', $previewHtml);
        $this->assertStringNotContainsString('Gateway Null Hidden Marker', $pdfHtml);
        $this->assertStringNotContainsString('Gateway Null Hidden Marker', $previewHtml);
    }

    private function fixturePayload(array $overrides = []): array
    {
        $payload = [
            'agreement' => [
                'uid' => 'AGR-MOU-V2-ANNEX-001',
                'type' => 'mou',
                'version' => 1,
                'status' => 'READY',
                'template_version' => 'mou-v2',
                'document_number' => 'MOU/GOTIK/VIII/2026/001',
            ],
            'event' => [
                'event_name' => 'Festival Nada Nusantara',
                'start' => '10-09-2026 19:00',
                'end' => '10-09-2026 22:00',
                'venue_name' => 'Balai Pertunjukan Senandika',
                'venue_address' => 'Jl. Melodi Raya No. 88',
                'venue_city' => 'Jakarta Selatan',
                'venue_province' => 'DKI Jakarta',
                'start_sale' => '01-09-2026 10:00',
                'tickets' => [
                    ['category' => 'Injected Ticket Category Marker', 'ticket_name' => 'Injected Ticket Name Marker'],
                ],
                'category' => 'Injected Ticket Category Marker',
                'ticket_name' => 'Injected Ticket Name Marker',
                'price' => 777111,
                'quota' => 888222,
                'benefit' => 'Injected Ticket Benefit Marker',
            ],
            'organizer' => [
                'organizer_name' => 'PT Ruang Pertunjukan Nusantara',
                'responsible_name' => 'Dimas Prabowo',
                'responsible_position' => 'Head of Event Operations',
            ],
            'bank_account' => [
                'bank_name' => 'BCA',
                'account_number' => '1234567890',
                'account_holder_name' => 'PT Ruang Pertunjukan Nusantara',
                'verification_status' => 'verified',
                'bank_book_path' => 'private/events/secret-bank-book.pdf',
                'verified_by' => 'internal-verifier-uid',
                'private_path' => 'private/internal/path',
            ],
            'commercial' => [
                'buyer_fee' => ['mode' => 'fixed', 'value' => 2000],
                'payment_otp_enabled' => true,
                'current_payment_gateways' => [
                    ['payment' => 'Live Gateway Should Not Render', 'private_path' => 'private/live/gateway/config'],
                ],
                'payment_gateways' => [
                    [
                        'payment' => 'BCA Virtual Account Super Long Gateway Name For Snapshot Rendering',
                        'effective_is_active' => true,
                        'resolved_fee_fixed' => '2000.00',
                        'resolved_fee_percent' => '3',
                    ],
                    [
                        'payment' => 'QRIS Promo Snapshot',
                        'effective_is_active' => false,
                        'resolved_fee_fixed' => '0.00',
                        'resolved_fee_percent' => '3',
                    ],
                    [
                        'payment' => 'Transfer Manual Snapshot',
                        'effective_is_active' => true,
                        'resolved_fee_fixed' => '4000.00',
                        'resolved_fee_percent' => '0',
                    ],
                ],
            ],
        ];

        return array_replace_recursive($payload, $overrides);
    }
}
