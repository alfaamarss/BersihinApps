<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_creates_user_and_returns_token()
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'New User',
            'email' => 'new@example.com',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
            'device_name' => 'test',
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => ['token','token_type','user' => ['id','name','email']],
                'meta',
                'errors'
            ])
            ->assertJsonPath('data.user.email', 'new@example.com');

        $token = $response->json('data.token');
        $me = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/auth/me');
        $me->assertOk()->assertJsonPath('data.email', 'new@example.com');
    }

    public function test_login_returns_token_and_user()
    {
        $this->seed();

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'admin@example.com',
            'password' => 'password',
            'device_name' => 'test',
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => ['token','token_type','user' => ['id','name','email']],
                'meta',
                'errors'
            ]);

        $token = $response->json('data.token');

        $me = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/auth/me');

        $me->assertOk()->assertJsonPath('data.email', 'admin@example.com');
    }
}
