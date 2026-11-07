<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Area;
use App\Models\ServiceAccount;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class TestSyncAreas extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:sync-areas {cityId}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test sync areas functionality';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $cityId = $this->argument('cityId');
        $this->info("Testing sync areas for city ID: {$cityId}");

        try {
            // Disable foreign key checks temporarily
            $this->info('Disabling foreign key checks...');
            DB::statement('SET FOREIGN_KEY_CHECKS=0');

            // Hapus semua data terkait
            $this->info('Truncating related tables...');

            // Hapus all related tables (order doesn't matter with FK checks disabled)
            $tablesToTruncate = [
                'waste_delivery_items',
                'waste_deliveries',
                'invoices',
                'complaints',
                'schedules',
                'service_accounts',
                'areas'
            ];

            foreach ($tablesToTruncate as $table) {
                if (Schema::hasTable($table)) {
                    DB::table($table)->truncate();
                    $this->info("✓ Truncated {$table}");
                }
            }

            // Re-enable foreign key checks
            $this->info('Re-enabling foreign key checks...');
            DB::statement('SET FOREIGN_KEY_CHECKS=1');

            // Ambil data kecamatan dari API
            $this->info('Fetching districts from API...');
            $districts = Http::get("https://www.emsifa.com/api-wilayah-indonesia/api/districts/{$cityId}.json")->json();

            if (!$districts || !is_array($districts)) {
                $this->error('Failed to fetch districts data from API');
                return;
            }

            $this->info('Found ' . count($districts) . ' districts');
            $villageCount = 0;

            foreach ($districts as $district) {
                // Buat data kecamatan terlebih dahulu
                $this->info("Creating district: {$district['name']}");
                $districtArea = Area::create([
                    'name' => $district['name'],
                    'level' => 'Kecamatan',
                    'parent_id' => null,
                ]);

                // Ambil data kelurahan untuk setiap kecamatan
                $this->info("  Fetching villages for district: {$district['name']}");
                $villages = Http::get("https://www.emsifa.com/api-wilayah-indonesia/api/villages/{$district['id']}.json")->json();

                if ($villages && is_array($villages)) {
                    foreach ($villages as $village) {
                        // Buat data kelurahan dengan parent_id menuju kecamatan
                        Area::create([
                            'name' => $village['name'],
                            'level' => 'Kelurahan',
                            'parent_id' => $districtArea->id,
                        ]);
                        $villageCount++;
                    }
                    $this->info("  ✓ Created " . count($villages) . " villages for {$district['name']}");
                } else {
                    $this->warn("  No villages found for {$district['name']}");
                }
            }

            $this->info("✅ Successfully synced areas:");
            $this->info("   Districts (Kecamatan): " . count($districts));
            $this->info("   Villages (Kelurahan): {$villageCount}");
            $this->info("   Total: " . Area::count());

        } catch (\Exception $e) {
            $this->error('Error syncing areas: ' . $e->getMessage());
            $this->error($e->getTraceAsString());
        }
    }
}
