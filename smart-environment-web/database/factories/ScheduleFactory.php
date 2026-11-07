<?php

namespace Database\Factories;

use App\Models\Area;
use App\Models\Schedule;
use Illuminate\Database\Eloquent\Factories\Factory;

class ScheduleFactory extends Factory
{
    protected $model = Schedule::class;

    public function definition(): array
    {
        return [
            'area_id' => Area::factory(),
            'day_of_week' => $this->faker->randomElement(['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday']),
            'pickup_time' => $this->faker->time('H:i:s'),
        ];
    }
}
