<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;
use App\Models\Fasilitas;
use Illuminate\Support\Str;

class MasterDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Categories
        $categories = [
            ['name' => 'Konser Musik'],
            ['name' => 'Seminar'],
            ['name' => 'Workshop'],
            ['name' => 'Festival'],
            ['name' => 'Olahraga'],
            ['name' => 'Pameran'],
            ['name' => 'Meet & Greet'],
            ['name' => 'Teater & Budaya'],
        ];

        foreach ($categories as $category) {
            Category::updateOrCreate(
                ['name' => $category['name']], 
                [
                    'name' => $category['name'],
                    'slug' => Str::slug($category['name'])
                ]
            );
        }

        // Fasilitas
        $fasilitas = [
            ['name' => 'WiFi'],
            ['name' => 'Parking Area'],
            ['name' => 'Smoking Area'],
            ['name' => 'Toilets'],
            ['name' => 'Food & Beverage'],
            ['name' => 'First Aid'],
            ['name' => 'Prayer Room'],
            ['name' => 'Security'],
        ];

        foreach ($fasilitas as $item) {
            Fasilitas::updateOrCreate(['name' => $item['name']], $item);
        }
    }
}
