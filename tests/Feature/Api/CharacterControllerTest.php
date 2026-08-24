<?php

namespace Tests\Feature\Api;

use App\Models\Character;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CharacterControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_characters_paginated(): void
    {
        Character::factory()->count(20)->create();

        $response = $this->getJson('/api/characters');

        $response->assertStatus(200)
            ->assertJsonCount(15, 'data') // Verifica la paginación de 15
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'status', 'species'] // Ajusta según tu CharacterResource
                ],
                'meta',
                'links'
            ]);
    }

    public function test_can_filter_characters_by_name_status_and_species(): void
    {
        Character::factory()->create([
            'name' => 'Rick Sanchez',
            'status' => 'Alive',
            'species' => 'Human'
        ]);

        Character::factory()->create([
            'name' => 'Toxic Rick',
            'status' => 'Dead',
            'species' => 'Humanoid'
        ]);

        // Probar filtro de nombre (parcial)
        $this->getJson('/api/characters?name=Sanchez')
            ->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Rick Sanchez');

        // Probar filtro de estado exacto
        $this->getJson('/api/characters?status=Dead')
            ->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Toxic Rick');
            
        // Probar filtro de especie (parcial)
        $this->getJson('/api/characters?species=Humanoid')
            ->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    ## Tests de Detalle (Show)

    public function test_can_show_character_detail(): void
    {
        $character = Character::factory()->create();

        $response = $this->getJson("/api/characters/{$character->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $character->id);
    }

    public function test_returns_404_if_character_not_found(): void
    {
        $response = $this->getJson('/api/characters/9999');

        $response->assertStatus(404);
    }

    ## Tests de Favoritos (Toggle)

    public function test_authenticated_user_can_toggle_favorite_character(): void
    {
        $user = User::factory()->create();
        $character = Character::factory()->create();

        Sanctum::actingAs($user);

        // Añadir a favoritos
        $response = $this->postJson("/api/characters/{$character->id}/favorite");

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Personaje añadido a tus favoritos.',
                'is_favorite' => true,
            ]);

        $this->assertDatabaseHas('character_user', [ // Asume tabla pivot por defecto
            'user_id' => $user->id,
            'character_id' => $character->id,
        ]);

        // Quitar de favoritos (Toggle off)
        $response = $this->postJson("/api/characters/{$character->id}/favorite");

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Personaje eliminado de tus favoritos.',
                'is_favorite' => false,
            ]);

        $this->assertDatabaseMissing('character_user', [
            'user_id' => $user->id,
            'character_id' => $character->id,
        ]);
    }

    public function test_unauthenticated_user_cannot_toggle_favorite(): void
    {
        $character = Character::factory()->create();

        $response = $this->postJson("/api/characters/{$character->id}/favorite");

        $response->assertStatus(401);
    }

    ## Tests de Listado de Favoritos

    public function test_user_can_list_their_favorite_characters(): void
    {
        $user = User::factory()->create();
        $favorites = Character::factory()->count(3)->create();
        $otherCharacter = Character::factory()->create(); // No es favorito

        $user->favoriteCharacters()->attach($favorites->pluck('id'));

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/favorites');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
            
        // Asegurar que el personaje no favorito no está en la lista
        $responseIds = collect($response->json('data'))->pluck('id');
        $this->assertFalse($responseIds->contains($otherCharacter->id));
    }
}