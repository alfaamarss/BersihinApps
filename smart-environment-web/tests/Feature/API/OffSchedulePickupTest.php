<?php

namespace Tests\Feature\API;

use App\Models\AppSetting;
use App\Models\Area;
use App\Models\Invoice;
use App\Models\PocketSize;
use App\Models\ServiceAccount;
use App\Models\User;
use App\Models\Waste;
use App\Models\WastePricing;
use App\Models\WasteDelivery;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class OffSchedulePickupTest extends TestCase
{
    use RefreshDatabase;

    protected User $resident;
    protected User $collector;
    protected User $admin;
    protected ServiceAccount $serviceAccount;
    protected Area $area;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);

        // Create area
        $this->area = Area::factory()->create([
            'name' => 'Test Area',
            'level' => 'Kelurahan',
        ]);

        // Create resident user
        $this->resident = User::factory()->create(['email' => 'resident@test.com']);
        $residentRole = Role::findByName('resident', 'api');
        $this->resident->assignRole($residentRole);

        // Create collector user
        $this->collector = User::factory()->create(['email' => 'collector@test.com']);
        $collectorRole = Role::findByName('collector', 'api');
        $this->collector->assignRole($collectorRole);

        // Create admin user
        $this->admin = User::factory()->create(['email' => 'admin@test.com']);
        $adminRole = Role::findByName('admin', 'api');
        $this->admin->assignRole($adminRole);

        // Create service account for resident
        $this->serviceAccount = ServiceAccount::factory()->create([
            'user_id' => $this->resident->id,
            'area_id' => $this->area->id,
            'name' => 'John Doe',
            'address' => 'Jl. Test No. 123',
        ]);

        // Create waste pricing
        $waste = Waste::factory()->create(['category' => 'inorganic']);
        $pocketSize = PocketSize::factory()->create(['name' => 'Besar']);
        WastePricing::factory()->create([
            'waste_id' => $waste->id,
            'pocket_size_id' => $pocketSize->id,
            'price' => 5000,
            'effective_from' => now()->subDays(30),
        ]);

        // Create app settings with surcharge
        AppSetting::create([
            'app_name' => 'Test App',
            'off_schedule_surcharge' => 3000,
        ]);
    }

    public function test_resident_can_create_off_schedule_pickup_request(): void
    {
        Sanctum::actingAs($this->resident, ['*'], 'api');

        $payload = [
            'service_account_id' => $this->serviceAccount->id,
            'requested_pickup_date' => now()->addDays(2)->toDateString(),
            'requested_pickup_time' => '09:00:00',
            'bag_count' => 3,
            'notes' => 'Mohon pickup di pagi hari',
        ];

        $response = $this->postJson('/api/v1/mobile/resident/off-schedule-pickups', $payload);

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('data.request_status', 'pending');
        $response->assertJsonPath('data.bag_count', 3);
        $response->assertJsonPath('data.pickup_type', 'off_schedule');

        // Check base amount calculation (bag_count * pricing + surcharge)
        // 3 bags * 5000 + 3000 = 18000
        $response->assertJsonPath('data.base_amount', 18000);
        $response->assertJsonPath('data.extra_fee', 0);
        $response->assertJsonPath('data.total_amount', 18000);

        $this->assertDatabaseHas('waste_deliveries', [
            'service_account_id' => $this->serviceAccount->id,
            'pickup_type' => 'off_schedule',
            'request_status' => 'pending',
            'bag_count' => 3,
        ]);
    }

    public function test_resident_cannot_create_request_with_unpaid_invoice(): void
    {
        Sanctum::actingAs($this->resident, ['*'], 'api');

        // Create an existing request with unpaid invoice
        $existingDelivery = WasteDelivery::factory()->create([
            'service_account_id' => $this->serviceAccount->id,
            'pickup_type' => 'off_schedule',
            'request_status' => 'completed',
            'bag_count' => 2,
            'base_amount' => 13000,
            'total_amount' => 13000,
        ]);

        Invoice::factory()->create([
            'service_account_id' => $this->serviceAccount->id,
            'waste_delivery_id' => $existingDelivery->id,
            'total_amount' => 13000,
            'status' => 'pending',
        ]);

        $payload = [
            'service_account_id' => $this->serviceAccount->id,
            'requested_pickup_date' => now()->addDays(2)->toDateString(),
            'requested_pickup_time' => '09:00:00',
            'bag_count' => 3,
        ];

        $response = $this->postJson('/api/v1/mobile/resident/off-schedule-pickups', $payload);

        $response->assertStatus(422);
        $response->assertJsonPath('success', false);
        $response->assertJsonPath('message', 'Anda memiliki tagihan pickup luar jadwal yang belum dibayar. Mohon selesaikan pembayaran terlebih dahulu.');
    }

    public function test_resident_can_list_own_off_schedule_requests(): void
    {
        Sanctum::actingAs($this->resident, ['*'], 'api');

        // Create some requests
        WasteDelivery::factory()->count(3)->create([
            'service_account_id' => $this->serviceAccount->id,
            'pickup_type' => 'off_schedule',
            'request_status' => 'pending',
        ]);

        $response = $this->getJson('/api/v1/mobile/resident/off-schedule-pickups');

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $response->assertJsonCount(3, 'data');
    }

    public function test_admin_can_assign_collector_to_request(): void
    {
        Sanctum::actingAs($this->admin, ['*'], 'api');

        $delivery = WasteDelivery::factory()->create([
            'service_account_id' => $this->serviceAccount->id,
            'pickup_type' => 'off_schedule',
            'request_status' => 'pending',
            'bag_count' => 2,
            'base_amount' => 13000,
            'total_amount' => 13000,
        ]);

        $response = $this->postJson("/api/v1/admin/off-schedule-pickups/{$delivery->id}/assign", [
            'collector_id' => $this->collector->id,
        ]);

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('data.request_status', 'processing');
        $response->assertJsonPath('data.assigned_collector_id', $this->collector->id);

        $this->assertDatabaseHas('waste_deliveries', [
            'id' => $delivery->id,
            'assigned_collector_id' => $this->collector->id,
            'request_status' => 'processing',
        ]);
    }

    public function test_collector_can_complete_assigned_pickup(): void
    {
        Sanctum::actingAs($this->collector, ['*'], 'api');

        $delivery = WasteDelivery::factory()->create([
            'service_account_id' => $this->serviceAccount->id,
            'pickup_type' => 'off_schedule',
            'request_status' => 'processing',
            'assigned_collector_id' => $this->collector->id,
            'bag_count' => 2,
            'base_amount' => 13000,
            'total_amount' => 13000,
        ]);

        $response = $this->putJson("/api/v1/mobile/collector/off-schedule-pickups/{$delivery->id}/complete", [
            'photo_url' => 'https://example.com/photo.jpg',
        ]);

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('data.request_status', 'completed');
        $response->assertJsonPath('data.photo_url', 'https://example.com/photo.jpg');

        $this->assertDatabaseHas('waste_deliveries', [
            'id' => $delivery->id,
            'request_status' => 'completed',
        ]);

        // Check invoice was created
        $this->assertDatabaseHas('invoices', [
            'waste_delivery_id' => $delivery->id,
            'status' => 'pending',
            'total_amount' => 13000,
        ]);
    }

    public function test_resident_can_confirm_completed_pickup(): void
    {
        Sanctum::actingAs($this->resident, ['*'], 'api');

        $delivery = WasteDelivery::factory()->create([
            'service_account_id' => $this->serviceAccount->id,
            'pickup_type' => 'off_schedule',
            'request_status' => 'completed',
            'assigned_collector_id' => $this->collector->id,
            'bag_count' => 2,
            'base_amount' => 13000,
            'total_amount' => 13000,
            'photo_url' => 'https://example.com/photo.jpg',
        ]);

        $invoice = Invoice::factory()->create([
            'service_account_id' => $this->serviceAccount->id,
            'waste_delivery_id' => $delivery->id,
            'total_amount' => 13000,
            'status' => 'pending',
        ]);

        $response = $this->putJson("/api/v1/mobile/resident/off-schedule-pickups/{$delivery->id}/confirm");

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('data.invoice_id', $invoice->id);
    }

    public function test_payment_updates_request_status_to_paid(): void
    {
        Sanctum::actingAs($this->resident, ['*'], 'api');

        $delivery = WasteDelivery::factory()->create([
            'service_account_id' => $this->serviceAccount->id,
            'pickup_type' => 'off_schedule',
            'request_status' => 'completed',
            'bag_count' => 2,
            'base_amount' => 13000,
            'total_amount' => 13000,
        ]);

        $invoice = Invoice::factory()->create([
            'service_account_id' => $this->serviceAccount->id,
            'waste_delivery_id' => $delivery->id,
            'total_amount' => 13000,
            'status' => 'pending',
        ]);

        // Simulate payment success
        $invoice->update(['status' => 'paid']);
        
        // Manually trigger the service method (simulating what payment controller does)
        $service = app(\App\Services\OffSchedulePickupService::class);
        $service->markRequestIfNeeded($delivery->id);

        $delivery->refresh();
        $this->assertEquals('paid', $delivery->request_status);
    }

    public function test_admin_can_update_cost_before_assignment(): void
    {
        Sanctum::actingAs($this->admin, ['*'], 'api');

        $delivery = WasteDelivery::factory()->create([
            'service_account_id' => $this->serviceAccount->id,
            'pickup_type' => 'off_schedule',
            'request_status' => 'pending',
            'bag_count' => 2,
            'base_amount' => 13000,
            'extra_fee' => 0,
            'total_amount' => 13000,
        ]);

        $response = $this->postJson("/api/v1/admin/off-schedule-pickups/{$delivery->id}/update-cost", [
            'bag_count' => 3,
            'extra_fee' => 2000,
        ]);

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('data.bag_count', 3);
        $response->assertJsonPath('data.extra_fee', 2000);
        // New calculation: 3 * 5000 + 3000 (surcharge) + 2000 (extra) = 20000
        $response->assertJsonPath('data.total_amount', 20000);
    }

    public function test_collector_can_skip_assigned_pickup(): void
    {
        Sanctum::actingAs($this->collector, ['*'], 'api');

        $delivery = WasteDelivery::factory()->create([
            'service_account_id' => $this->serviceAccount->id,
            'pickup_type' => 'off_schedule',
            'request_status' => 'processing',
            'assigned_collector_id' => $this->collector->id,
            'bag_count' => 2,
        ]);

        $response = $this->putJson("/api/v1/mobile/collector/off-schedule-pickups/{$delivery->id}/skip", [
            'reason' => 'Alamat tidak ditemukan',
        ]);

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('data.request_status', 'pending');
        $this->assertNull($delivery->fresh()->assigned_collector_id);
    }

    public function test_admin_can_reject_request(): void
    {
        Sanctum::actingAs($this->admin, ['*'], 'api');

        $delivery = WasteDelivery::factory()->create([
            'service_account_id' => $this->serviceAccount->id,
            'pickup_type' => 'off_schedule',
            'request_status' => 'pending',
        ]);

        $response = $this->postJson("/api/v1/admin/off-schedule-pickups/{$delivery->id}/reject", [
            'notes' => 'Area tidak terjangkau',
        ]);

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('data.request_status', 'rejected');
    }
}
