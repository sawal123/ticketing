<?php

namespace Database\Seeders;

use App\Models\Landing;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SeoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Landing::create([
            'description' => 'Gotik Description',
            'keyword'=> 'Gotik Keyword',
            'logo'=> 'logo.png',
            'icon'=> 'icon.png'
        ]);
    }
}
