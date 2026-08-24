<?php

namespace App\Services\Sync;

use App\Integration\RickAndMorty\RickAndMortyClient;
use App\Integration\RickAndMorty\RickAndMortyMapper;
use App\Models\Character;
use App\Models\Episode;
use App\Models\Location;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class RickAndMortySyncService
{
    public function __construct(
        private readonly RickAndMortyClient $client,
        private readonly RickAndMortyMapper $mapper
    ) {}

    /**
     * Sincroniza todas las entidades de la API externa respetando el orden de dependencias.
     */
    public function syncAll(?callable $progressCallback = null): void
    {
        $this->syncLocations($progressCallback);
        $this->syncEpisodes($progressCallback);
        $this->syncCharacters($progressCallback);
    }

    public function syncLocations(?callable $callback = null): void
    {
        $this->paginateResource('location', function (array $item) {
            $dto = $this->mapper->mapLocation($item);

            Location::updateOrCreate(
                ['external_id' => $dto->externalId],
                [
                    'name' => $dto->name,
                    'type' => $dto->type,
                    'dimension' => $dto->dimension,
                ]
            );
        }, $callback, 'Localizaciones');
    }

    public function syncEpisodes(?callable $callback = null): void
    {
        $this->paginateResource('episode', function (array $item) {
            $dto = $this->mapper->mapEpisode($item);

            Episode::updateOrCreate(
                ['external_id' => $dto->externalId],
                [
                    'name' => $dto->name,
                    'air_date' => $dto->airDate,
                    'episode_code' => $dto->episodeCode,
                ]
            );
        }, $callback, 'Episodios');
    }

    public function syncCharacters(?callable $callback = null): void
    {
        // Pre-cargamos en memoria la relación external_id => id local de Locations y Episodes
        // Esto optimiza drásticamente el rendimiento evitando miles de consultas SQL en bucle
        $locationsMap = Location::whereNotNull('external_id')->pluck('id', 'external_id');
        $episodesMap = Episode::pluck('id', 'external_id');

        $this->paginateResource('character', function (array $item) use ($locationsMap, $episodesMap) {
            $dto = $this->mapper->mapCharacter($item);

            DB::transaction(function () use ($dto, $locationsMap, $episodesMap) {
                // Mapear los IDs externos a los IDs locales creados previamente
                $originId = $dto->originExternalId ? ($locationsMap[$dto->originExternalId] ?? null) : null;
                $locationId = $dto->locationExternalId ? ($locationsMap[$dto->locationExternalId] ?? null) : null;

                $character = Character::updateOrCreate(
                    ['external_id' => $dto->externalId],
                    [
                        'name' => $dto->name,
                        'status' => $dto->status,
                        'species' => $dto->species,
                        'type' => $dto->type,
                        'gender' => $dto->gender,
                        'image' => $dto->image,
                        'origin_id' => $originId,
                        'location_id' => $locationId,
                    ]
                );

                // Sincronizar la relación N:M con episodios (sync evita duplicados)
                $episodeLocalIds = collect($dto->episodeExternalIds)
                    ->map(fn ($extId) => $episodesMap[$extId] ?? null)
                    ->filter()
                    ->toArray();

                $character->episodes()->sync($episodeLocalIds);
            });
        }, $callback, 'Personajes');
    }

    /**
     * Método genérico para recorrer la paginación con tolerancia a fallos en elementos individuales.
     */
    private function paginateResource(string $endpoint, callable $processor, ?callable $callback, string $resourceName): void
    {
        $page = 1;

        do {
            try {
                $response = $this->client->fetchPage($endpoint, $page);

                if (!$response || empty($response['results'])) {
                    break;
                }

                foreach ($response['results'] as $item) {
                    try {
                        $processor($item);
                    } catch (Throwable $e) {
                        // Tolerancia a fallos parciales: si un ítem tiene formato corrupto, lo registramos y continuamos
                        Log::error("Error procesando {$endpoint} ID {$item['id']}: " . $e->getMessage());
                    }
                }

                if ($callback) {
                    $callback($resourceName, $page, $response['info']['pages'] ?? $page);
                }

                $page = isset($response['info']['next']) ? $page + 1 : null;

                // Pausa de 300ms entre solicitudes para no sobrepasar el Rate Limit de Cloudflare
                usleep(300000);

            } catch (Throwable $e) {
                Log::critical("Fallo crítico al consultar la página {$page} de {$endpoint}: " . $e->getMessage());
                break; // Rompemos el bucle ante una caída completa del servicio
            }

        } while ($page !== null);
    }
}