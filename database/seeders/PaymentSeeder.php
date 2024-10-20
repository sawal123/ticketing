<?php

namespace Database\Seeders;

use App\Models\PaymentGateway;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PaymentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        PaymentGateway::insert([
            [

                'payment' => 'bri_va',
                'biaya' => 4000, // dalam rupiah
                'biaya_type' => 'rupiah',
                'icon' => 'bri.png',
                'is_active' => true
            ],

            [

                'payment' => 'mandiri-va',
                'biaya' => 4000, // dalam rupiah
                'biaya_type' => 'rupiah',
                'icon' => 'madniri.png',
                'is_active' => true
            ],
            [

                'payment' => 'bni-va',
                'biaya' => 4000, // dalam rupiah
                'biaya_type' => 'rupiah',
                'icon' => 'bni.png',
                'is_active' => true
            ],
            [

                'payment' => 'permata-va',
                'biaya' => 4000, // dalam rupiah
                'biaya_type' => 'rupiah',
                'icon' => 'permata.png',
                'is_active' => true
            ],
            [

                'payment' => 'cimb-va',
                'biaya' => 4000, // dalam rupiah
                'biaya_type' => 'rupiah',
                'icon' => 'cimb.png',
                'is_active' => true
            ],
            [

                'payment' => 'other-va',
                'biaya' => 4000, // dalam rupiah
                'biaya_type' => 'rupiah',
                'icon' => 'other.png',
                'is_active' => true
            ],
            [

                'payment' => 'gopay-qris',
                'biaya' => 2, // dalam rupiah
                'biaya_type' => 'persen',
                'icon' => 'qris.png',
                'is_active' => true
            ],
        ]);
    }
}

// bank-transfer/mandiri-va
// bank-transfer/bni-va
// bank-transfer/bri-va
// bank-transfer/permata-va
// bank-transfer/cimb-va-va
// bank-transfer/other-va
// /gopay-qris
// gopay
