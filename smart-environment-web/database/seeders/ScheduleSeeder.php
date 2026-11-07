<?php

namespace Database\Seeders;

use App\Models\Area;
use App\Models\Schedule;
use Illuminate\Database\Seeder;

class ScheduleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $kelurahans = Area::where('level', 'Kelurahan')->get();

        $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
        $times = ['08:00:00', '13:00:00'];

        foreach ($kelurahans as $kelurahan) {
            // Create 1-2 schedules per kelurahan
            $dayIndex = $kelurahan->id % count($days);
            $timeIndex = $kelurahan->id % count($times);

            Schedule::create([
                'area_id' => $kelurahan->id,
                'day_of_week' => $days[$dayIndex],
                'pickup_time' => $times[$timeIndex],
                'is_active' => true,
                'auto_generate_delivery' => false,
            ]);
        }

        $this->command->info('✓ Seeded ' . Schedule::count() . ' schedule(s)');
    }
}
