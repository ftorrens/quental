<?php

namespace App\Services\Sync;

use App\Models\Character;
use App\Models\Episode;
use App\Models\Location;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Exception;

class RickAndMortySyncService
{
    private const BASE_URL = 'https://rickandmortyapi.com/api';

    /**
     * @throws Exception
     */
    public function syncAll(?callable $progressCallback = null): void
    {
        // 1. Sincronizar Localizaciones
        $this->syncResource('location', function (array $item) {
            Location::updateOrCreate(
                ['external_id' => $item['id']],
                [
                    'name' => $item['name'],
                    'type' => $item['type'] ?? null,
                    'dimension' => $item['dimension'] ?? null,
                ]
            );
        }, $progressCallback);

        // 2. Sincronizar Episodios
        $this->syncResource('episode', function (array $item) {
            Episode::updateOrCreate(
                ['external_id' => $item['id']],
                [
                    'name' => $item['name'],
                    'air_date' => $item['air_date'],
                    'episode_code' => $item['episode'],
                ]
            );
        }, $progressCallback);

        // 3. Sincronizar Personajes
        $this->syncResource('character', function (array $item) {
            $character = Character::updateOrCreate(
                ['external_id' => $item['id']],
                [
                    'name' => $item['name'],
                    'status' => $item['status'],
                    'species' => $item['species'],
                    'type' => $item['type'] ?? null,
                    'gender' => $item['gender'],
                    'image' => $item['image'],
                    'origin_external_id' => $this->extractIdFromUrl($item['origin']['url'] ?? ''),
                    'location_external_id' => $this->extractIdFromUrl($item['location']['url'] ?? ''),
                ]
            );

            // Sincronizar la relación Muchos a Muchos con Episodios
            if (!empty($item['episode'])) {
                $episodeExternalIds = array_filter(array_map([$this, 'extractIdFromUrl'], $item['episode']));
                
                // Buscamos los IDs internos de la BD basados en los external_ids de la API
                $localEpisodeIds = Episode::whereIn('external_id', $episodeExternalIds)->pluck('id');
                $character->episodes()->sync($localEpisodeIds);
            }
        }, $progressCallback);
    }

    /**
     * Recorre todas las páginas de un recurso de la API y procesa cada elemento.
     */
    private function syncResource(string $resource, callable $processItem, ?callable $progressCallback): void
    {
        $page = 1;
        $totalPages = 1;

        do {
            $response = Http::timeout(5)
                ->retry(3, 100)
                ->get(self::BASE_URL . "/{$resource}", ['page' => $page]);

            if ($response->status() === 404) {
                break; // Fin de la paginación
            }

            $response->throw(); // Lanza excepción en caso de errores 500, etc.

            $data = $response->json();
            $totalPages = $data['info']['pages'] ?? 1;

            // Usamos una transacción por página para mayor velocidad e integridad
            DB::transaction(function () use ($data, $processItem) {
                foreach ($data['results'] as $item) {
                    $processItem($item);
                }
            });

            if ($progressCallback) {
                $progressCallback(ucfirst($resource), $page, $totalPages);
            }

            $page++;

            // Pausa de 300ms entre solicitudes para no sobrepasar el Rate Limit de Cloudflare
            usleep(300000);
            
        } while ($page <= $totalPages);
    }

    /**
     * Extrae el ID de las URLs que proporciona la API.
     */
    private function extractIdFromUrl(?string $url): ?int
    {
        if (empty($url)) {
            return null;
        }

        $parts = explode('/', rtrim($url, '/'));
        $id = end($parts);

        return is_numeric($id) ? (int) $id : null;
    }
}