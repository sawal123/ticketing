<?php

namespace Tests\Feature;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\View;
use Tests\TestCase;

class AgreementMouV2TemplateTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        View::share('logo', [(object) ['logo' => '', 'icon' => '']]);
    }

    public function test_mou_v2_pdf_and_preview_render_comprehensive_body_from_shared_partial(): void
    {
        $payload = $this->fixturePayload();

        $pdfHtml = view('agreements.mou-v2-pdf', ['payload' => $payload])->render();
        $previewHtml = view('agreements.mou-v2-preview', ['payload' => $payload])->render();

        $this->assertStringContainsString('mou-v2-contract-shared-body', $pdfHtml);
        $this->assertStringContainsString('mou-v2-contract-shared-body', $previewHtml);

        foreach ([
            'PERJANJIAN KERJA SAMA',
            'MELALUI PLATFORM GOTIK',
            'PIHAK PERTAMA',
            'PIHAK KEDUA',
            'Festival Nada Nusantara',
            'PT Gotik Indonesia',
            'PT Ruang Pertunjukan Nusantara',
            'Rani Setiawati',
            'Direktur Utama',
            'Dimas Prabowo',
            'Head of Event Operations',
            'Agreement UID',
            'Template Version',
        ] as $text) {
            $this->assertStringContainsString($text, $pdfHtml);
            $this->assertStringContainsString($text, $previewHtml);
        }

        foreach ([
            'Platform adalah',
            'Event adalah',
            'Penyelenggara adalah',
            'Pembeli adalah',
            'Tiket adalah',
            'Payment Gateway adalah',
            'Rekonsiliasi adalah',
            'Refund adalah',
            'Gate System adalah',
            'Biaya tambahan kepada Pembeli, apabila berlaku, wajib diinformasikan secara transparan',
            'Biaya yang timbul dari penggunaan kanal pembayaran tertentu, apabila berlaku, diinformasikan kepada Pembeli sebelum transaksi',
            'sesuai mekanisme penandatanganan yang berlaku dan disepakati PARA PIHAK.',
        ] as $text) {
            $this->assertStringContainsString($text, $pdfHtml);
            $this->assertStringContainsString($text, $previewHtml);
        }

        for ($article = 1; $article <= 21; $article++) {
            $this->assertStringContainsString('PASAL '.$article, $pdfHtml);
            $this->assertStringContainsString('PASAL '.$article, $previewHtml);
        }

        $this->assertStringContainsString('Halaman Tanda Tangan', $pdfHtml);
        $this->assertStringContainsString('Halaman Tanda Tangan', $previewHtml);

        $this->assertStringNotContainsString('Injected VIP Ticket', $pdfHtml);
        $this->assertStringNotContainsString('Injected VIP Ticket', $previewHtml);
        $this->assertStringNotContainsString('999000', $pdfHtml);
        $this->assertStringNotContainsString('999000', $previewHtml);
        $this->assertStringNotContainsString('777', $pdfHtml);
        $this->assertStringNotContainsString('777', $previewHtml);
        $this->assertStringNotContainsString('mekanisme yang ditetapkan kemudian', $pdfHtml);
        $this->assertStringNotContainsString('mekanisme yang ditetapkan kemudian', $previewHtml);
        $this->assertStringNotContainsString('Privy', $pdfHtml);
        $this->assertStringNotContainsString('Privy', $previewHtml);
        $this->assertStringNotContainsString('OAuth', $pdfHtml);
        $this->assertStringNotContainsString('OAuth', $previewHtml);
        $this->assertStringNotContainsString('webhook', $pdfHtml);
        $this->assertStringNotContainsString('webhook', $previewHtml);
        $this->assertStringNotContainsString('callback', $pdfHtml);
        $this->assertStringNotContainsString('callback', $previewHtml);
        $this->assertStringNotContainsString('API', $pdfHtml);
        $this->assertStringNotContainsString('API', $previewHtml);
        $this->assertStringNotContainsString('Biaya Pembeli / Event Fee adalah', $pdfHtml);
        $this->assertStringNotContainsString('Biaya Pembeli / Event Fee adalah', $previewHtml);
        $this->assertStringNotContainsString('Biaya Kanal Pembayaran adalah', $pdfHtml);
        $this->assertStringNotContainsString('Biaya Kanal Pembayaran adalah', $previewHtml);

        $this->assertGreaterThan(
            strpos($pdfHtml, 'PASAL 21'),
            strpos($pdfHtml, 'Agreement UID')
        );

        $pdfBinary = Pdf::loadView('agreements.mou-v2-pdf', ['payload' => $payload])
            ->setPaper('a4', 'portrait')
            ->output();

        $this->assertNotEmpty($pdfBinary);
        $this->assertStringStartsWith('%PDF', $pdfBinary);
    }

    public function test_mou_v2_handles_optional_null_fields_without_render_error(): void
    {
        $payload = $this->fixturePayload([
            'agreement' => [
                'document_number' => null,
            ],
            'event' => [
                'start' => null,
                'end' => null,
                'venue_name' => null,
                'venue_address' => null,
                'venue_city' => null,
                'venue_province' => null,
            ],
            'platform_party' => [
                'legal_id' => null,
                'address' => null,
                'email' => null,
                'phone' => null,
                'website' => null,
            ],
            'organizer' => [
                'phone' => null,
                'email' => null,
                'address' => null,
            ],
        ]);

        $pdfHtml = view('agreements.mou-v2-pdf', ['payload' => $payload])->render();
        $previewHtml = view('agreements.mou-v2-preview', ['payload' => $payload])->render();

        $this->assertStringContainsString('Festival Nada Nusantara', $pdfHtml);
        $this->assertStringContainsString('Festival Nada Nusantara', $previewHtml);
        $this->assertStringContainsString('<td>-</td>', $pdfHtml);
        $this->assertStringContainsString('<td>-</td>', $previewHtml);
    }

    public function test_legal_review_checklist_avoids_privy_api_integration_wording(): void
    {
        $checklist = file_get_contents(base_path('docs/mou-v2-legal-review-checklist.md'));

        $this->assertIsString($checklist);
        $this->assertStringContainsString('validitas tanda tangan elektronik', $checklist);
        $this->assertStringContainsString('workflow download unsigned', $checklist);
        $this->assertStringContainsString('penandatanganan independen', $checklist);
        $this->assertStringContainsString('upload signed PDF', $checklist);
        $this->assertStringContainsString('verifikasi admin', $checklist);
        $this->assertStringNotContainsString('Privy', $checklist);
        $this->assertStringNotContainsString('API', $checklist);
        $this->assertStringNotContainsString('OAuth', $checklist);
        $this->assertStringNotContainsString('webhook', $checklist);
        $this->assertStringNotContainsString('callback', $checklist);
        $this->assertStringNotContainsString('integrasi penandatanganan digital', $checklist);
    }

    private function fixturePayload(array $overrides = []): array
    {
        $payload = [
            'agreement' => [
                'uid' => 'AGR-MOU-V2-001',
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
                'tickets' => [
                    ['category' => 'Injected VIP Ticket', 'price' => 999000, 'quota' => 777],
                ],
                'category' => 'Injected VIP Ticket',
                'price' => 999000,
                'quota' => 777,
            ],
            'platform_party' => [
                'company_name' => 'PT Gotik Indonesia',
                'legal_id' => 'NIB-1234567890123',
                'address' => 'Jl. Harmoni Digital No. 15, Jakarta Pusat',
                'representative_name' => 'Rani Setiawati',
                'representative_position' => 'Direktur Utama',
                'email' => 'legal@gotik.test',
                'phone' => '081200000001',
                'website' => 'https://gotik.test',
            ],
            'organizer' => [
                'organizer_name' => 'PT Ruang Pertunjukan Nusantara',
                'responsible_name' => 'Dimas Prabowo',
                'responsible_position' => 'Head of Event Operations',
                'phone' => '081300000002',
                'email' => 'event@rpn.test',
                'address' => 'Jl. Panggung Kreasi No. 21, Bandung',
            ],
        ];

        return array_replace_recursive($payload, $overrides);
    }
}
