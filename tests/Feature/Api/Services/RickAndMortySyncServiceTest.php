<?php

namespace Tests\Feature\Services;

use App\Integration\RickAndMorty\RickAndMortyClient;
use App\Models\Character;
use App\Models\Episode;
use App\Models\Location;
use App\Services\Sync\RickAndMortySyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class RickAndMortySyncServiceTest extends TestCase
{
    use RefreshDatabase;

    private RickAndMortySyncService $syncService;
    private MockInterface $clientMock;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Instanciamos un Mock del cliente HTTP para no hacer llamadas reales a la API
        $this->clientMock = Mockery::mock(RickAndMortyClient::class);
        $this->instance(RickAndMortyClient::class, $this->clientMock);

        // 2. Resolvemos el servicio desde el contenedor para que inyecte nuestro Mock y el Mapper real
        $this->syncService = app(RickAndMortySyncService::class);
    }

    public function test_can_sync_locations_successfully(): void
    {
        // Preparamos la respuesta falsa de la API para la página 1
        $this->clientMock->shouldReceive('fetchPage')
            ->with('location', 1)
            ->once()
            ->andReturn([
                'info' => ['pages' => 1, 'next' => null],
                'results' => [
                    [
                        'id' => 1,
                        'name' => 'Earth (C-137)',
                        'type' => 'Planet',
                        'dimension' => 'Dimension C-137',
                    ]
                ]
            ]);

        $this->syncService->syncLocations();

        // Verificamos que se insertó correctamente en la base de datos
        $this->assertDatabaseCount('locations', 1);
        $this->assertDatabaseHas('locations', [
            'external_id' => 1,
            'name' => 'Earth (C-137)',
            'type' => 'Planet',
        ]);
    }

    public function test_can_sync_episodes_successfully(): void
    {
        $this->clientMock->shouldReceive('fetchPage')
            ->with('episode', 1)
            ->once()
            ->andReturn([
                'info' => ['pages' => 1, 'next' => null],
                'results' => [
                    [
                        'id' => 10,
                        'name' => 'Pilot',
                        'air_date' => 'December 2, 2013',
                        'episode' => 'S01E01',
                    ]
                ]
            ]);

        $this->syncService->syncEpisodes();

        $this->assertDatabaseCount('episodes', 1);
        $this->assertDatabaseHas('episodes', [
            'external_id' => 10,
            'name' => 'Pilot',
            'episode_code' => 'S01E01',
        ]);
    }

    public function test_can_sync_characters_with_relationships_successfully(): void
    {
        // Creamos una localización y un episodio previos para probar el mapeo de relaciones (N:M y 1:N)
        $location = Location::factory()->create(['external_id' => 20]);
        $episode = Episode::factory()->create(['external_id' => 30]);

        $this->clientMock->shouldReceive('fetchPage')
            ->with('character', 1)
            ->once()
            ->andReturn([
                'info' => ['pages' => 1, 'next' => null],
                'results' => [
                    [
                        'id' => 1,
                        'name' => 'Rick Sanchez',
                        'status' => 'Alive',
                        'species' => 'Human',
                        'type' => '',
                        'gender' => 'Male',
                        'image' => 'https://rickandmortyapi.com/api/character/avatar/1.jpeg',
                        'origin' => ['name' => 'Earth', 'url' => 'https://rickandmortyapi.com/api/location/20'],
                        'location' => ['name' => 'Earth', 'url' => 'https://rickandmortyapi.com/api/location/20'],
                        'episode' => [
                            'https://rickandmortyapi.com/api/episode/30'
                        ]
                    ]
                ]
            ]);

        $this->syncService->syncCharacters();

        // Verificamos el personaje y sus llaves foráneas
        $this->assertDatabaseHas('characters', [
            'external_id' => 1,
            'name' => 'Rick Sanchez',
            'origin_id' => $location->id,
            'location_id' => $location->id,
        ]);

        // Verificamos que se sincronizó la tabla pivot (character_episode)
        $character = Character::where('external_id', 1)->first();
        $this->assertTrue($character->episodes->contains($episode->id));
    }

    public function test_continues_syncing_when_a_single_item_fails(): void
    {
        Log::shouldReceive('error')->once(); // Esperamos que se registre 1 error

        $this->clientMock->shouldReceive('fetchPage')
            ->with('location', 1)
            ->once()
            ->andReturn([
                'info' => ['pages' => 1, 'next' => null],
                'results' => [
                    ['id' => 1, 'name' => 'Valid Location', 'type' => 'Planet', 'dimension' => 'D1'],
                    ['id' => 2], // Item corrupto (faltan campos), disparará el catch individual
                    ['id' => 3, 'name' => 'Another Valid', 'type' => 'Planet', 'dimension' => 'D2'],
                ]
            ]);

        $this->syncService->syncLocations();

        // Deberían haberse guardado los 2 válidos a pesar del fallo en el del medio
        $this->assertDatabaseCount('locations', 2);
    }
}