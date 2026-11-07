<?php

namespace Database\Seeders;

use App\Models\Waste;
use Illuminate\Database\Seeder;

class WasteSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $wastes = [
            [
                'code' => 'ORG',
                'name' => 'Sampah Organik',
                'category' => 'organic',
                'description' => 'Sampah organik seperti sisa makanan, daun, dan bahan alami lainnya',
            ],
            [
                'code' => 'INORG',
                'name' => 'Sampah Anorganik',
                'category' => 'inorganic',
                'description' => 'Sampah anorganik seperti plastik, kertas, logam, dan kaca',
            ],
        ];

        foreach ($wastes as $waste) {
            Waste::updateOrCreate(
                ['code' => $waste['code']],
                $waste
            );
        }

        $this->command->info('✓ Seeded ' . count($wastes) . ' waste types');
    }
}
