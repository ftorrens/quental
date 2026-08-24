<?php

namespace Tests\Unit\Services;

use App\DTOs\CharacterDTO;
use App\DTOs\EpisodeDTO;
use App\DTOs\LocationDTO;
use App\Integration\RickAndMorty\RickAndMortyMapper;
use InvalidArgumentException;
use Tests\TestCase;

class RickAndMortyMapperTest extends TestCase
{
    private RickAndMortyMapper $mapper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mapper = new RickAndMortyMapper();
    }

    public function test_can_map_valid_location_data(): void
    {
        $rawLocation = [
            'id' => 3,
            'name' => 'Citadel of Ricks',
            'type' => 'Space station',
            'dimension' => 'unknown',
        ];

        $dto = $this->mapper->mapLocation($rawLocation);

        $this->assertInstanceOf(LocationDTO::class, $dto);
        $this->assertEquals(3, $dto->externalId);
        $this->assertEquals('Citadel of Ricks', $dto->name);
        $this->assertEquals('Space station', $dto->type);
        $this->assertEquals('unknown', $dto->dimension);
    }

    public function test_location_mapping_fails_on_invalid_data(): void
    {
        $this->expectException(InvalidArgumentException::class);

        // Falta el 'id' requerido
        $this->mapper->mapLocation([
            'name' => 'Invalid Location',
        ]);
    }

    // --- TESTS DE EPISODIOS ---

    public function test_can_map_valid_episode_data(): void
    {
        $rawEpisode = [
            'id' => 28,
            'name' => 'The Ricklantis Mixup',
            'air_date' => 'September 10, 2017',
            'episode' => 'S03E07',
        ];

        $dto = $this->mapper->mapEpisode($rawEpisode);

        $this->assertInstanceOf(EpisodeDTO::class, $dto);
        $this->assertEquals(28, $dto->externalId);
        $this->assertEquals('The Ricklantis Mixup', $dto->name);
        $this->assertEquals('September 10, 2017', $dto->airDate);
        $this->assertEquals('S03E07', $dto->episodeCode);
    }

    public function test_episode_mapping_fails_on_missing_fields(): void
    {
        $this->expectException(InvalidArgumentException::class);

        // Falta 'episode'
        $this->mapper->mapEpisode([
            'id' => 1,
            'name' => 'Pilot',
            'air_date' => 'December 2, 2013',
        ]);
    }

    // --- TESTS DE PERSONAJES Y EXTRACCIÓN DE IDs ---

    public function test_can_map_character_and_extract_ids_from_urls(): void
    {
        $rawCharacter = [
            'id' => 1,
            'name' => 'Rick Sanchez',
            'status' => 'Alive',
            'species' => 'Human',
            'type' => '',
            'gender' => 'Male',
            'image' => 'https://rickandmortyapi.com/api/character/avatar/1.jpeg',
            'origin' => [
                'name' => 'Earth (C-137)',
                'url' => 'https://rickandmortyapi.com/api/location/1',
            ],
            'location' => [
                'name' => 'Citadel of Ricks',
                'url' => 'https://rickandmortyapi.com/api/location/3',
            ],
            'episode' => [
                'https://rickandmortyapi.com/api/episode/1',
                'https://rickandmortyapi.com/api/episode/2',
            ],
        ];

        $dto = $this->mapper->mapCharacter($rawCharacter);

        $this->assertInstanceOf(CharacterDTO::class, $dto);
        $this->assertEquals(1, $dto->externalId);
        $this->assertEquals('Rick Sanchez', $dto->name);
        // Comprobamos la correcta extracción de IDs numéricos desde las URLs
        $this->assertEquals(1, $dto->originExternalId);
        $this->assertEquals(3, $dto->locationExternalId);
        $this->assertEquals([1, 2], $dto->episodeExternalIds);
    }

    public function test_character_mapping_handles_unknown_origin_and_empty_urls(): void
    {
        $rawCharacter = [
            'id' => 2,
            'name' => 'Morty Smith',
            'status' => 'Alive',
            'species' => 'Human',
            'type' => null,
            'gender' => 'Male',
            'image' => 'https://rickandmortyapi.com/api/character/avatar/2.jpeg',
            'origin' => [
                'name' => 'unknown',
                'url' => '', // URL vacía para orígenes desconocidos
            ],
            'location' => [
                'name' => 'Earth (Replacement Dimension)',
                'url' => 'https://rickandmortyapi.com/api/location/20',
            ],
            'episode' => [
                'https://rickandmortyapi.com/api/episode/1',
            ],
        ];

        $dto = $this->mapper->mapCharacter($rawCharacter);

        // El origen vacío debe traducirse a null de forma segura
        $this->assertNull($dto->originExternalId);
        $this->assertEquals(20, $dto->locationExternalId);
    }

    public function test_character_mapping_fails_if_image_is_not_a_valid_url(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $rawCharacter = [
            'id' => 1,
            'name' => 'Rick',
            'status' => 'Alive',
            'species' => 'Human',
            'gender' => 'Male',
            'image' => 'not-a-valid-url', // Fallará la regla de validación 'url'
            'origin' => ['url' => 'https://rickandmortyapi.com/api/location/1'],
            'location' => ['url' => 'https://rickandmortyapi.com/api/location/1'],
            'episode' => ['https://rickandmortyapi.com/api/episode/1'],
        ];

        $this->mapper->mapCharacter($rawCharacter);
    }
}