<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiCrudPermissionsTest extends TestCase
{
    use RefreshDatabase;

    private function authHeaders(User $user): array
    {
        $token = $user->createToken('test')->plainTextToken;
        return ['Authorization' => 'Bearer '.$token];
    }

    public function test_users_index_requires_permission()
    {
        $this->seed();
        $user = User::where('email','warga@example.com')->first();
        $res = $this->withHeaders($this->authHeaders($user))->getJson('/api/v1/users');
        $res->assertStatus(403);
    }

    public function test_admin_can_access_users_index()
    {
        $this->seed();
        $admin = User::where('email','admin@example.com')->first();
        $res = $this->withHeaders($this->authHeaders($admin))->getJson('/api/v1/users');
        $res->assertOk();
    }
}
