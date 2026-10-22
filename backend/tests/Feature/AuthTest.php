<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_returns_a_token_for_valid_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'admin@example.com',
            'password' => 'secret-password',
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'admin@example.com',
            'password' => 'secret-password',
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'token',
                    'user' => ['id', 'name', 'email'],
                ],
                'message',
            ])
            ->assertJsonPath('data.user.id', $user->id);

        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $user->id,
        ]);
    }

    public function test_login_rejects_an_incorrect_password(): void
    {
        User::factory()->create([
            'email' => 'admin@example.com',
            'password' => 'secret-password',
        ]);

        $this->postJson('/api/login', [
            'email' => 'admin@example.com',
            'password' => 'incorrect-password',
        ])->assertUnauthorized();
    }

    public function test_login_requires_an_email(): void
    {
        $this->postJson('/api/login', [
            'password' => 'secret-password',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('email');
    }

    public function test_logout_requires_a_token(): void
    {
        $this->post('/api/logout')->assertUnauthorized()
            ->assertJsonStructure(['message']);
    }

    public function test_logout_deletes_the_current_token(): void
    {
        $user = User::factory()->create();
        $issuedToken = $user->createToken('test-token');
        $tokenId = $issuedToken->accessToken->getKey();

        $this->withToken($issuedToken->plainTextToken)
            ->postJson('/api/logout')
            ->assertOk()
            ->assertJsonStructure(['message']);

        $this->assertDatabaseMissing('personal_access_tokens', [
            'id' => $tokenId,
        ]);
    }
}
