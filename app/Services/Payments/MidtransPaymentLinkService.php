<?php

namespace App\Services\Payments;

use Midtrans\Config as MidtransConfig;
use Midtrans\Transaction as MidtransTransaction;

class MidtransPaymentLinkService
{
    public function expire(string $orderId): void
    {
        MidtransConfig::$serverKey = (string) config('services.midtrans.serverKey');
        MidtransConfig::$isProduction = (bool) config('services.midtrans.isProduction');

        MidtransTransaction::expire($orderId);
    }
}
