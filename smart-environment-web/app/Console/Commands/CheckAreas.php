<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Area;

class CheckAreas extends Command
{
    protected $signature = 'check:areas';
    protected $description = 'Check areas structure in database';

    public function handle()
    {
        $this->info('=== AREAS & SETTINGS CHECK ===');

        // Check app settings
        $settings = \App\Models\AppSetting::first();
        if ($settings) {
            $this->info('Current Settings:');
            $this->info('  Province ID: ' . ($settings->province_id ?? 'null'));
            $this->info('  City ID: ' . ($settings->configured_city_id ?? 'null'));
            $this->info('  City Name: ' . ($settings->configured_city_name ?? 'null'));
        } else {
            $this->info('No app settings found');
        }

        $this->info('');
        $this->info('Areas Data:');
        $this->info('Total Areas: ' . Area::count());
        $this->info('Kecamatan: ' . Area::where('level', 'Kecamatan')->count());
        $this->info('Kelurahan: ' . Area::where('level', 'Kelurahan')->count());

        if (Area::count() > 0) {
            $this->info('');
            $this->info('Sample Kecamatan (parent_id = null):');
            Area::where('level', 'Kecamatan')->whereNull('parent_id')->take(3)->get()->each(function($area) {
                $this->info('  - ' . $area->name . ' (ID: ' . $area->id . ')');
            });

            $this->info('');
            $this->info('Sample Kelurahan with parent:');
            Area::where('level', 'Kelurahan')->whereNotNull('parent_id')->with('parent')->take(3)->get()->each(function($area) {
                $parentName = $area->parent ? $area->parent->name : 'No Parent';
                $this->info('  - ' . $area->name . ' (Parent: ' . $parentName . ')');
            });
        }
    }
}
