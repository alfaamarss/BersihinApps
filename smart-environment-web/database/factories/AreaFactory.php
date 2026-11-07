<?php

namespace Database\Factories;

use App\Models\Area;
use Illuminate\Database\Eloquent\Factories\Factory;

class AreaFactory extends Factory
{
    protected $model = Area::class;

    public function definition(): array
    {
        return [
            'parent_id' => null,
            'name' => $this->faker->unique()->city(),
            'level' => $this->faker->randomElement(['Kecamatan', 'Kelurahan']),
        ];
    }
}
