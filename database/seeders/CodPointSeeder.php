<?php

namespace Database\Seeders;

use App\Models\CodPoint;
use Illuminate\Database\Seeder;

/**
 * Titik COD contoh di sekitar kampus Polines.
 * Sesuaikan koordinat dan nama dengan lokasi resmi dari kampus.
 */
class CodPointSeeder extends Seeder
{
    public function run(): void
    {
        $points = [
            [
                'name'      => 'Gedung Administrasi Polines',
                'address'   => 'Jl. Prof. H. Soedarto, S.H., Tembalang, Semarang',
                'lat'       => -7.0497,
                'lng'       => 110.4380,
                'is_active' => true,
            ],
            [
                'name'      => 'Kantin Polines',
                'address'   => 'Area Kantin, Politeknik Negeri Semarang',
                'lat'       => -7.0500,
                'lng'       => 110.4383,
                'is_active' => true,
            ],
            [
                'name'      => 'Perpustakaan Polines',
                'address'   => 'Gedung Perpustakaan, Politeknik Negeri Semarang',
                'lat'       => -7.0495,
                'lng'       => 110.4375,
                'is_active' => true,
            ],
            [
                'name'      => 'Parkiran Utama',
                'address'   => 'Area Parkir Utama Polines',
                'lat'       => -7.0502,
                'lng'       => 110.4370,
                'is_active' => true,
            ],
        ];

        foreach ($points as $point) {
            CodPoint::updateOrCreate(['name' => $point['name']], $point);
        }
    }
}
