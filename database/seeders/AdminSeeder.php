<?php

namespace Database\Seeders;

use App\Models\Bank;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $uid = Str::uuid();
        $admin = User::create([
            'uid' => $uid,
            'name' => 'Gotik',
            'email' => 'gotikevent@gmail.com',
            'nomor' => '082387348459',
            'birthday' => '2008-06-09',
            'alamat' => 'Medan',
            'kota' => 'Sumatera Utara',
            'gender' => 'pria',
            'gambar' => 'null',
            'role' => 'admin',
            'password' => '$2y$10$auGmPWvxktS055z1fD3uAeyWCmrRKRwnuMmrIouP1u8ryaSH056L6'
        ]);

        $bank = Bank::create([
            'uid' => $uid,
            'uid_user' => $uid,
            'nama' => 'GOTIK',
            'bank' => 'BCA',
            'norek' => '453774953'
        ]);
    }
}
