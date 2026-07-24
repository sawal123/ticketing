<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\View;
use Illuminate\Support\ViewErrorBag;
use Tests\TestCase;

class BarcodeLoginOptionsTest extends TestCase
{
    public function test_barcode_login_page_has_forgot_password_and_google_login_links(): void
    {
        $invoice = 'INV-260724-MDDIBA116';
        View::share('errors', new ViewErrorBag());

        $html = view('barcode-login', [
            'data' => $invoice,
            'logo' => [],
        ])->render();

        $this->assertStringContainsString(route('forgot'), $html);
        $this->assertStringContainsString('Lupa Password?', $html);
        $this->assertStringContainsString(
            route('auth.google', ['redirect' => route('barcode.generate', ['data' => $invoice], false)]),
            $html
        );
        $this->assertStringContainsString('Google', $html);
    }
}
