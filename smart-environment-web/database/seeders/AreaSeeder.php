<?php

namespace Database\Seeders;

use App\Models\Area;
use Illuminate\Database\Seeder;

class AreaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Sample Kecamatan
        $kecamatans = [
            'Coblong',
            'Sukasari',
            'Cidadap',
        ];

        foreach ($kecamatans as $kecamatanName) {
            $kecamatan = Area::create([
                'name' => $kecamatanName,
                'level' => 'Kecamatan',
                'parent_id' => null,
            ]);

            // Sample Kelurahan for each Kecamatan
            $kelurahans = [
                'Dago',
                'Lebak Gede',
                'Lebak Siliwangi',
                'Sadang Serang',
            ];

            foreach ($kelurahans as $kelurahanName) {
                Area::create([
                    'name' => $kelurahanName,
                    'level' => 'Kelurahan',
                    'parent_id' => $kecamatan->id,
                ]);
            }
        }

        $this->command->info('✓ Seeded ' . Area::where('level', 'Kecamatan')->count() . ' kecamatan(s)');
        $this->command->info('✓ Seeded ' . Area::where('level', 'Kelurahan')->count() . ' kelurahan(s)');
    }
}
