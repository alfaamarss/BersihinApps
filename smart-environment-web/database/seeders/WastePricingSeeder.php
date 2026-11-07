<?php

namespace Database\Seeders;

use App\Models\Waste;
use App\Models\PocketSize;
use App\Models\WastePricing;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class WastePricingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $wastes = Waste::all();
        $sizes = PocketSize::all();

        $pricingData = [
            // Organic waste pricing
            'ORG' => [
                'Besar' => 5000,
                'Sedang' => 3500,
                'Kecil' => 2000,
            ],
            // Inorganic waste pricing
            'INORG' => [
                'Besar' => 7500,
                'Sedang' => 5000,
                'Kecil' => 3000,
            ],
        ];

        $today = Carbon::today();

        foreach ($wastes as $waste) {
            if (!isset($pricingData[$waste->code])) {
                continue;
            }

            foreach ($sizes as $size) {
                if (!isset($pricingData[$waste->code][$size->name])) {
                    continue;
                }

                WastePricing::updateOrCreate(
                    [
                        'waste_id' => $waste->id,
                        'pocket_size_id' => $size->id,
                        'effective_from' => $today,
                    ],
                    [
                        'price' => $pricingData[$waste->code][$size->name],
                        'effective_to' => null,
                    ]
                );
            }
        }

        $this->command->info('✓ Seeded waste pricing data for all waste types and pocket sizes');
    }
}
