<?php

namespace App\Integration\RickAndMorty;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\RequestException;
use Exception;

class RickAndMortyClient
{
    private const BASE_URL = 'https://rickandmortyapi.com/api';

    /**
     * @throws RequestException|Exception
     */
    public function fetchPage(string $resource, int $page = 1): ?array
    {
        $response = Http::timeout(5)          // Falla rápido si la red se cuelga (5 segundos)
            ->retry(3, 100)                   // 3 reintentos con 100ms de espera entre ellos
            ->get(self::BASE_URL . "/{$resource}", ['page' => $page]);

        // Si la API devuelve 404, asumimos que hemos llegado al final de la paginación
        if ($response->status() === 404) {
            return null;
        }

        // Lanza una excepción (RequestException) si hay errores 500, 403, etc.
        $response->throw(); 

        return $response->json();
    }
}