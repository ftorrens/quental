<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuthControllerTest extends TestCase
{
    use RefreshDatabase; 

    ## Tests de Registro

    public function test_user_can_register_successfully(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => 'Rick Sanchez',
            'email' => 'rick@citadel.com',
            'password' => 'secret123',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'access_token',
                'token_type',
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'rick@citadel.com',
        ]);
    }

    public function test_registration_requires_valid_data(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => '', // Inválido
            'email' => 'not-an-email', // Inválido
            'password' => '123', // Demasiado corta
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'email', 'password']);
    }

    public function test_registration_fails_if_email_is_taken(): void
    {
        User::factory()->create(['email' => 'rick@citadel.com']);

        $response = $this->postJson('/api/register', [
            'name' => 'Morty Smith',
            'email' => 'rick@citadel.com', // Email duplicado
            'password' => 'secret123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    ## Tests de Login

    public function test_user_can_login_with_correct_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'user@example.com',
            'password' => 'secret123',
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'user@example.com',
            'password' => 'secret123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'access_token',
                'token_type',
            ]);
    }

    public function test_login_fails_with_incorrect_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'user@example.com',
            'password' => 'secret123',
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'user@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    ## Tests de Logout

    public function test_authenticated_user_can_logout(): void
    {
        $user = User::factory()->create();
        
        // Autentica al usuario usando Sanctum para este test
        Sanctum::actingAs($user, ['*']);

        $response = $this->postJson('/api/logout');

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Sesión cerrada correctamente.',
            ]);
    }

    public function test_unauthenticated_user_cannot_logout(): void
    {
        // Petición sin token
        $response = $this->postJson('/api/logout');

        $response->assertStatus(401);
    }
}