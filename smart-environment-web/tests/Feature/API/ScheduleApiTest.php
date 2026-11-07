<?php

namespace Tests\Feature\API;

use App\Models\Area;
use App\Models\Schedule;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ScheduleApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);

        $this->user = User::factory()->create();
        $adminRole = Role::findByName('admin', 'api');
        $this->user->assignRole($adminRole);

        Sanctum::actingAs($this->user, ['*'], 'api');
    }

    public function test_can_list_schedules_filtered_by_area(): void
    {
        $areaA = Area::factory()->create(['name' => 'Area Alpha']);
        $areaB = Area::factory()->create(['name' => 'Area Beta']);

        Schedule::factory()->create([
            'area_id' => $areaA->id,
            'day_of_week' => 'Monday',
            'pickup_time' => '07:30:00',
        ]);
        Schedule::factory()->create([
            'area_id' => $areaB->id,
            'day_of_week' => 'Tuesday',
            'pickup_time' => '08:45:00',
        ]);

        $response = $this->getJson('/api/v1/schedules?filter[area_id]=' . $areaA->id);

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.area.id', $areaA->id);
        $response->assertJsonPath('data.0.area.name', 'Area Alpha');
    }

    public function test_can_create_schedule(): void
    {
        $area = Area::factory()->create();

        $payload = [
            'area_id' => $area->id,
            'day_of_week' => 'Wednesday',
            'pickup_time' => '09:15:00',
        ];

        $response = $this->postJson('/api/v1/schedules', $payload);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('data.area.id', $area->id);
        $response->assertJsonPath('data.day_of_week', 'Wednesday');
        $response->assertJsonPath('data.pickup_time', '09:15:00');

        $this->assertDatabaseHas('schedules', [
            'area_id' => $area->id,
            'day_of_week' => 'Wednesday',
            'pickup_time' => '09:15:00',
        ]);
    }

    public function test_validation_error_when_invalid_day(): void
    {
        $area = Area::factory()->create();

        $payload = [
            'area_id' => $area->id,
            'day_of_week' => 'Funday',
            'pickup_time' => '10:00:00',
        ];

        $response = $this->postJson('/api/v1/schedules', $payload);

        $response->assertStatus(422);
        $response->assertJsonPath('errors.details.day_of_week.0', 'The selected day of week is invalid.');
    }
}
