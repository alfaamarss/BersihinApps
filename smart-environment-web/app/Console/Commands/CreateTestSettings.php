<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\AppSetting;

class CreateTestSettings extends Command
{
    protected $signature = 'create:test-settings';
    protected $description = 'Create test app settings';

    public function handle()
    {
        $this->info('Creating test app settings...');

        $settings = AppSetting::firstOrNew();
        $settings->fill([
            'app_name' => 'Smart Environment Test',
            'province_id' => '33', // Jawa Tengah
            'configured_city_id' => '3374', // Kota Semarang
            'configured_city_name' => 'KOTA SEMARANG'
        ]);
        $settings->save();

        $this->info('✅ Test app settings created successfully!');
        $this->info('Province: JAWA TENGAH (ID: 33)');
        $this->info('City: KOTA SEMARANG (ID: 3374)');
    }
}
