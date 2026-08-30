<?php

namespace Tests\Feature;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\View;
use Tests\TestCase;

class AgreementMouV2AnnexIITest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        View::share('logo', [(object) ['logo' => '', 'icon' => '']]);
    }

    public function test_pdf_and_preview_render_annex_ii_in_the_correct_order(): void
    {
        $payload = $this->fixturePayload();

        $pdfHtml = view('agreements.mou-v2-pdf', ['payload' => $payload])->render();
        $previewHtml = view('agreements.mou-v2-preview', ['payload' => $payload])->render();

        foreach ([$pdfHtml, $previewHtml] as $html) {
            $this->assertStringContainsString('mou-v2-contract-shared-body', $html);
            $this->assertStringContainsString('mou-v2-annex-i-shared-body', $html);
            $this->assertStringContainsString('mou-v2-annex-ii-shared-body', $html);
            $this->assertStringContainsString('LAMPIRAN II', $html);
            $this->assertStringContainsString('DOKUMEN, KESIAPAN EVENT DAN KONTAK RESMI', $html);
            $this->assertStringContainsString('LENGKAP', $html);
            $this->assertStringContainsString('TERSEDIA', $html);
            $this->assertStringContainsString('TERVERIFIKASI', $html);
            $this->assertStringContainsString('BELUM TERSEDIA', $html);
            $this->assertStringContainsString('Status keberadaan file buku rekening tidak tersedia pada frozen snapshot.', $html);
            $this->assertStringContainsString('ORGANIZER_LETTER', $html);
            $this->assertStringContainsString('Identitas Penanggung Jawab', $html);
            $this->assertStringContainsString('SURAT/ORG/2026/001', $html);
            $this->assertStringContainsString('15-08-2026', $html);
            $this->assertStringContainsString('surat-organizer.pdf', $html);
            $this->assertStringContainsString('PT Gotik Indonesia', $html);
            $this->assertStringContainsString('Rani Setiawati', $html);
            $this->assertStringContainsString('Direktur Utama', $html);
            $this->assertStringContainsString('legal@gotik.test', $html);
            $this->assertStringContainsString('081200000001', $html);
            $this->assertStringContainsString('https://gotik.test', $html);
            $this->assertStringContainsString('PT Ruang Pertunjukan Nusantara', $html);
            $this->assertStringContainsString('Dimas Prabowo', $html);
            $this->assertStringContainsString('Head of Event Operations', $html);
            $this->assertStringContainsString('event@rpn.test', $html);
            $this->assertStringContainsString('081300000002', $html);
            $this->assertStringNotContainsString('PRIVATE-BANK-BOOK-MARKER', $html);
            $this->assertStringNotContainsString('PRIVATE-FILE-PATH-MARKER', $html);
            $this->assertStringNotContainsString('PRIVATE-PATH-MARKER', $html);
            $this->assertStringNotContainsString('PRIVATE-VERIFIED-BY-MARKER', $html);
            $this->assertStringNotContainsString('PRIVATE-UNSIGNED-PDF-MARKER', $html);
            $this->assertStringNotContainsString('PRIVATE-SIGNED-PDF-MARKER', $html);
            $this->assertStringNotContainsString('Injected Ticket Category Marker', $html);
            $this->assertStringNotContainsString('Injected Ticket Name Marker', $html);
            $this->assertStringNotContainsString('Injected Ticket Benefit Marker', $html);
            $this->assertStringNotContainsString('777111', $html);
            $this->assertStringNotContainsString('888222', $html);
            $this->assertStringNotContainsString('payment_otp_enabled', $html);
            $this->assertStringNotContainsString('ktp-responsible.pdf', $html);

            $this->assertTrue(
                strpos($html, 'mou-v2-contract-shared-body') < strpos($html, 'mou-v2-annex-i-shared-body')
            );
            $this->assertTrue(
                strpos($html, 'mou-v2-annex-i-shared-body') < strpos($html, 'mou-v2-annex-ii-shared-body')
            );
        }

        $pdfBinary = Pdf::loadView('agreements.mou-v2-pdf', ['payload' => $payload])
            ->setPaper('a4', 'portrait')
            ->output();

        $this->assertNotEmpty($pdfBinary);
        $this->assertStringStartsWith('%PDF', $pdfBinary);
    }

    public function test_annex_ii_renders_incomplete_and_rejected_states_safely(): void
    {
        $payload = $this->fixturePayload([
            'platform_party' => [
                'company_name' => null,
                'representative_name' => null,
                'representative_position' => null,
                'email' => null,
                'phone' => null,
                'website' => null,
            ],
            'organizer' => [
                'responsible_name' => null,
                'email' => null,
            ],
            'bank_account' => [
                'account_number' => null,
                'verification_status' => 'rejected',
            ],
            'organizer_letter' => [
                'document_date' => null,
                'verification_status' => 'rejected',
            ],
            'responsible_identity' => [
                'verification_status' => 'rejected',
                'original_name' => 'ktp-rejected.pdf',
            ],
        ]);
        $payload['commercial']['payment_gateways'] = [
            ['payment' => 'Gateway Snapshot A', 'effective_is_active' => false],
            ['payment' => 'Gateway Snapshot B', 'effective_is_active' => false],
        ];

        $pdfHtml = view('agreements.mou-v2-pdf', ['payload' => $payload])->render();
        $previewHtml = view('agreements.mou-v2-preview', ['payload' => $payload])->render();

        foreach ([$pdfHtml, $previewHtml] as $html) {
            $this->assertStringContainsString('BELUM LENGKAP', $html);
            $this->assertStringContainsString('DITOLAK', $html);
            $this->assertStringContainsString('<td>-</td>', $html);
            $this->assertStringNotContainsString('Kanal Pembayaran Efektif', $html);
            $this->assertStringNotContainsString('Gateway Snapshot A', $html);
            $this->assertStringNotContainsString('Gateway Snapshot B', $html);
        }
    }

    public function test_annex_ii_handles_pending_unknown_and_empty_gateway_states(): void
    {
        $payload = $this->fixturePayload([
            'bank_account' => [
                'verification_status' => 'pending',
            ],
            'organizer_letter' => [
                'verification_status' => 'arsip-lama',
            ],
            'responsible_identity' => [
                'verification_status' => 'pending',
            ],
        ]);
        $payload['commercial']['payment_gateways'] = [];

        $pdfHtml = view('agreements.mou-v2-pdf', ['payload' => $payload])->render();
        $previewHtml = view('agreements.mou-v2-preview', ['payload' => $payload])->render();

        foreach ([$pdfHtml, $previewHtml] as $html) {
            $this->assertStringContainsString('MENUNGGU VERIFIKASI', $html);
            $this->assertStringContainsString('arsip-lama', $html);
            $this->assertStringContainsString('BELUM TERSEDIA', $html);
            $this->assertStringNotContainsString('Kanal Pembayaran Efektif', $html);
        }
    }

    public function test_annex_ii_handles_historical_payload_without_responsible_identity_safely(): void
    {
        $payload = $this->fixturePayload();
        unset($payload['responsible_identity']);

        $pdfHtml = view('agreements.mou-v2-pdf', ['payload' => $payload])->render();
        $previewHtml = view('agreements.mou-v2-preview', ['payload' => $payload])->render();

        foreach ([$pdfHtml, $previewHtml] as $html) {
            $this->assertStringContainsString('Identitas Penanggung Jawab', $html);
            $this->assertStringContainsString('BELUM TERSEDIA', $html);
            $this->assertStringNotContainsString('ktp-responsible.pdf', $html);
        }
    }

    private function fixturePayload(array $overrides = []): array
    {
        $payload = [
            'agreement' => [
                'uid' => 'AGR-MOU-V2-ANNEX-II-001',
                'type' => 'mou',
                'version' => 1,
                'status' => 'READY',
                'template_version' => 'mou-v2',
                'document_number' => 'MOU/GOTIK/VIII/2026/001',
                'unsigned_pdf_path' => 'PRIVATE-UNSIGNED-PDF-MARKER',
                'signed_pdf_path' => 'PRIVATE-SIGNED-PDF-MARKER',
            ],
            'event' => [
                'event_name' => 'Festival Nada Nusantara',
                'category' => 'Injected Ticket Category Marker',
                'ticket_name' => 'Injected Ticket Name Marker',
                'price' => 777111,
                'quota' => 888222,
                'benefit' => 'Injected Ticket Benefit Marker',
            ],
            'platform_party' => [
                'company_name' => 'PT Gotik Indonesia',
                'representative_name' => 'Rani Setiawati',
                'representative_position' => 'Direktur Utama',
                'email' => 'legal@gotik.test',
                'phone' => '081200000001',
                'website' => 'https://gotik.test',
                'private_path' => 'PRIVATE-PATH-MARKER',
                'verified_by' => 'PRIVATE-VERIFIED-BY-MARKER',
            ],
            'organizer' => [
                'organizer_name' => 'PT Ruang Pertunjukan Nusantara',
                'responsible_name' => 'Dimas Prabowo',
                'responsible_position' => 'Head of Event Operations',
                'phone' => '081300000002',
                'email' => 'event@rpn.test',
                'address' => 'Jl. Panggung Kreasi No. 21, Bandung',
                'private_path' => 'PRIVATE-PATH-MARKER',
            ],
            'bank_account' => [
                'bank_name' => 'BCA',
                'account_number' => '1234567890',
                'account_holder_name' => 'PT Ruang Pertunjukan Nusantara',
                'verification_status' => 'verified',
                'bank_book_path' => 'PRIVATE-BANK-BOOK-MARKER',
                'verified_by' => 'PRIVATE-VERIFIED-BY-MARKER',
                'private_path' => 'PRIVATE-PATH-MARKER',
            ],
            'organizer_letter' => [
                'document_type' => 'ORGANIZER_LETTER',
                'document_number' => 'SURAT/ORG/2026/001',
                'document_date' => '15-08-2026',
                'original_name' => 'surat-organizer.pdf',
                'verification_status' => 'verified',
                'file_path' => 'PRIVATE-FILE-PATH-MARKER',
                'verified_by' => 'PRIVATE-VERIFIED-BY-MARKER',
                'private_path' => 'PRIVATE-PATH-MARKER',
            ],
            'responsible_identity' => [
                'document_type' => 'RESPONSIBLE_IDENTITY',
                'original_name' => 'ktp-responsible.pdf',
                'verification_status' => 'verified',
                'verified_at' => '16-08-2026 10:30',
                'file_path' => 'PRIVATE-FILE-PATH-MARKER',
                'verified_by' => 'PRIVATE-VERIFIED-BY-MARKER',
                'private_path' => 'PRIVATE-PATH-MARKER',
            ],
            'commercial' => [
                'payment_otp_enabled' => true,
                'payment_gateways' => [
                    [
                        'payment' => 'BCA Virtual Account',
                        'effective_is_active' => true,
                        'private_path' => 'PRIVATE-PATH-MARKER',
                    ],
                    [
                        'payment' => 'QRIS Snapshot',
                        'effective_is_active' => false,
                        'private_path' => 'PRIVATE-PATH-MARKER',
                    ],
                ],
            ],
        ];

        return array_replace_recursive($payload, $overrides);
    }
}
