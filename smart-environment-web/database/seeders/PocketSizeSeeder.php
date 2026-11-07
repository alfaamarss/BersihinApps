<?php

namespace Database\Seeders;

use App\Models\PocketSize;
use Illuminate\Database\Seeder;

class PocketSizeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $pocketSizes = [
            [
                'name' => 'Besar',
                'description' => 'Kantong sampah ukuran besar untuk volume sampah yang banyak',
            ],
            [
                'name' => 'Sedang',
                'description' => 'Kantong sampah ukuran sedang untuk volume sampah standar',
            ],
            [
                'name' => 'Kecil',
                'description' => 'Kantong sampah ukuran kecil untuk volume sampah yang sedikit',
            ],
        ];

        foreach ($pocketSizes as $size) {
            PocketSize::updateOrCreate(
                ['name' => $size['name']],
                $size
            );
        }

        $this->command->info('✓ Seeded ' . count($pocketSizes) . ' pocket sizes');
    }
}
