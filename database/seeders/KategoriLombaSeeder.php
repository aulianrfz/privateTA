<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\KategoriLomba;

class KategoriLombaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $kategoriLomba = [
            ['name' => 'Creative Dance', 'duration' => 60],
            ['name' => 'Tourism Ideathon', 'duration' => 90],
            ['name' => 'Bidding Event Proposal', 'duration' => 45],
            ['name' => 'Making Bed', 'duration' => 30],
            ['name' => 'Tourism Speech Competition', 'duration' => 60],
            ['name' => 'Manual Brew', 'duration' => 40],
            ['name' => 'Black Box Cooking Battle', 'duration' => 120],
        ];

        foreach ($kategoriLomba as $data) {
            KategoriLomba::create($data);
        }
    }
}
