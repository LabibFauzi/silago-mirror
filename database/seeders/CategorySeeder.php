<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Mengisi kategori barang awal sesuai design.md.
 */
class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Elektronik',        'icon' => 'laptop'],
            ['name' => 'Fashion',           'icon' => 'shirt'],
            ['name' => 'Kendaraan',         'icon' => 'bike'],
            ['name' => 'Buku & Alat Tulis', 'icon' => 'book'],
            ['name' => 'Rumah Tangga',      'icon' => 'home'],
            ['name' => 'Olahraga',          'icon' => 'dumbbell'],
            ['name' => 'Lainnya',           'icon' => 'package'],
        ];

        foreach ($categories as $cat) {
            Category::updateOrCreate(
                ['slug' => Str::slug($cat['name'])],
                ['name' => $cat['name'], 'icon' => $cat['icon']]
            );
        }
    }
}
