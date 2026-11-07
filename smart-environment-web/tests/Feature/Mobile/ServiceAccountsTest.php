<?php

namespace Tests\Feature\Mobile;

use App\Models\Area;
use App\Models\ServiceAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ServiceAccountsTest extends TestCase
{
    use RefreshDatabase;

    protected function authenticate(User $user): void
    {
        Sanctum::actingAs($user, [], 'api');
    }

    protected function createAreaHierarchy(): array
    {
        $district = Area::create([
            'name' => 'Kecamatan Test',
            'level' => 'kecamatan',
        ]);

        $subDistrict = Area::create([
            'name' => 'Kelurahan Test',
            'level' => 'kelurahan',
            'parent_id' => $district->id,
        ]);

        return [$district, $subDistrict];
    }

    public function test_user_can_create_list_and_delete_service_account(): void
    {
        [$district, $area] = $this->createAreaHierarchy();
        $user = User::factory()->create();
        $this->authenticate($user);

        $payload = [
            'name' => 'Rumah Utama',
            'contact_phone' => '08123456789',
            'area_id' => $area->id,
            'address' => 'Jl. Mawar No. 1',
            'latitude' => -6.2,
            'longitude' => 106.8,
            'note' => 'Catatan penting',
        ];

        $create = $this->postJson('/api/v1/mobile/service-accounts', $payload);

        $create->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Rumah Utama')
            ->assertJsonPath('data.contact_phone', '08123456789')
            ->assertJsonPath('data.area.name', $area->name)
            ->assertJsonPath('data.area.parent.name', $district->name);

        $this->assertDatabaseHas('service_accounts', [
            'user_id' => $user->id,
            'area_id' => $area->id,
            'name' => 'Rumah Utama',
            'contact_phone' => '08123456789',
            'status' => 'active',
        ]);

        $index = $this->getJson('/api/v1/mobile/service-accounts');

        $index->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data.items')
            ->assertJsonPath('data.items.0.name', 'Rumah Utama')
            ->assertJsonPath('data.items.0.kelurahan', $area->name);

        $serviceAccountId = $create->json('data.id');

        $delete = $this->deleteJson("/api/v1/mobile/service-accounts/{$serviceAccountId}");
        $delete->assertOk()->assertJsonPath('data.deleted', true);

        $this->assertSoftDeleted('service_accounts', ['id' => $serviceAccountId]);
    }

    public function test_user_cannot_view_service_account_owned_by_someone_else(): void
    {
        [$district, $area] = $this->createAreaHierarchy();
        $otherUser = User::factory()->create();
        $serviceAccount = ServiceAccount::create([
            'user_id' => $otherUser->id,
            'area_id' => $area->id,
            'name' => 'Rumah Orang Lain',
            'contact_phone' => '089999999',
            'address' => 'Alamat Rahasia',
            'latitude' => -6.1,
            'longitude' => 107.0,
            'note' => null,
            'status' => 'active',
        ]);

        $user = User::factory()->create();
        $this->authenticate($user);

        $this->getJson("/api/v1/mobile/service-accounts/{$serviceAccount->id}")
            ->assertStatus(404);
    }
}
