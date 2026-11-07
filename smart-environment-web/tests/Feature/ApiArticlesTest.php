<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiArticlesTest extends TestCase
{
    use RefreshDatabase;

    private function authHeaders(User $user): array
    {
        $token = $user->createToken('test')->plainTextToken;
        return ['Authorization' => 'Bearer ' . $token];
    }

    public function test_admin_can_crud_articles(): void
    {
        $this->seed();

        $admin = User::where('email', 'admin@example.com')->firstOrFail();

        $createPayload = [
            'title' => 'Contoh Artikel',
            'content' => 'Isi artikel contoh yang cukup panjang.',
            'status' => 'published',
        ];

        $createResponse = $this
            ->withHeaders($this->authHeaders($admin))
            ->postJson('/api/v1/articles', $createPayload);

        $createResponse->assertOk();

        $created = $createResponse->json('data');

        $this->assertNotNull($created['id'] ?? null);
        $this->assertEquals('Contoh Artikel', $created['title']);
        $this->assertNotEmpty($created['slug']);
        $this->assertEquals($admin->id, $created['author_id']);
        $this->assertNotEmpty($created['published_at']);
        $articleId = $created['id'];

        $updatePayload = [
            'title' => 'Artikel Sesudah Diperbarui',
            'status' => 'draft',
        ];

        $updateResponse = $this
            ->withHeaders($this->authHeaders($admin))
            ->putJson("/api/v1/articles/{$articleId}", $updatePayload);

        $updateResponse->assertOk();
        $updated = $updateResponse->json('data');
        $this->assertEquals('Artikel Sesudah Diperbarui', $updated['title']);
        $this->assertEquals('draft', $updated['status']);
        $this->assertNotEmpty($updated['slug']);

        $deleteResponse = $this
            ->withHeaders($this->authHeaders($admin))
            ->deleteJson("/api/v1/articles/{$articleId}");

        $deleteResponse->assertOk()->assertJsonPath('data.deleted', true);
        $this->assertDatabaseMissing('articles', ['id' => $articleId]);
    }

    public function test_warga_cannot_create_articles(): void
    {
        $this->seed();

        $warga = User::where('email', 'warga@example.com')->firstOrFail();

        $payload = [
            'title' => 'Artikel Tidak Boleh',
            'content' => 'Isi artikel.',
        ];

        $response = $this
            ->withHeaders($this->authHeaders($warga))
            ->postJson('/api/v1/articles', $payload);

        $response->assertForbidden();
    }
}
