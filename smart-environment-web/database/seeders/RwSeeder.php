<?php

namespace Database\Seeders;

use App\Models\Area;
use App\Models\Rw;
use Illuminate\Database\Seeder;

class RwSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * 
     * Note: RWs are now created on-demand when users register service accounts.
     * This seeder only creates a few examples for testing/demo purposes.
     */
    public function run(): void
    {
        // Only create sample RWs for first kelurahan in each kecamatan for demo
        $kecamatans = Area::where('level', 'Kecamatan')->with('children')->get();

        foreach ($kecamatans as $kecamatan) {
            $firstKelurahan = $kecamatan->children->first();
            
            if ($firstKelurahan) {
                // Create just 3 sample RWs for demo/testing
                for ($i = 1; $i <= 3; $i++) {
                    Rw::create([
                        'kelurahan_id' => $firstKelurahan->id,
                        'name' => sprintf('RW %02d', $i),
                    ]);
                }
            }
        }

        $this->command->info('✓ Seeded ' . Rw::count() . ' sample RW(s) for demo');
        $this->command->info('  (RWs will be created automatically when users register)');
    }
}
