<?php

namespace App\Integration\RickAndMorty;

use App\DTOs\CharacterDTO;
use App\DTOs\EpisodeDTO;
use App\DTOs\LocationDTO;
use Illuminate\Support\Facades\Validator;
use InvalidArgumentException;

class RickAndMortyMapper
{
    public function mapLocation(array $data): LocationDTO
    {
        $this->validate($data, [
            'id' => 'required|integer',
            'name' => 'required|string',
            'type' => 'nullable|string',
            'dimension' => 'nullable|string',
        ]);

        return new LocationDTO(
            $data['id'],
            $data['name'],
            $data['type'] ?? null,
            $data['dimension'] ?? null
        );
    }

    public function mapEpisode(array $data): EpisodeDTO
    {
        $this->validate($data, [
            'id' => 'required|integer',
            'name' => 'required|string',
            'air_date' => 'required|string',
            'episode' => 'required|string',
        ]);

        return new EpisodeDTO(
            $data['id'],
            $data['name'],
            $data['air_date'],
            $data['episode']
        );
    }

    public function mapCharacter(array $data): CharacterDTO
    {
        $this->validate($data, [
            'id' => 'required|integer',
            'name' => 'required|string',
            'status' => 'required|string',
            'species' => 'required|string',
            'type' => 'nullable|string',
            'gender' => 'required|string',
            'image' => 'required|url',
            'origin.url' => 'present',
            'location.url' => 'present',
            'episode' => 'required|array',
        ]);

        return new CharacterDTO(
            $data['id'],
            $data['name'],
            $data['status'],
            $data['species'],
            $data['type'] ?? null,
            $data['gender'],
            $data['image'],
            $this->extractIdFromUrl($data['origin']['url']),
            $this->extractIdFromUrl($data['location']['url']),
            array_map([$this, 'extractIdFromUrl'], $data['episode'])
        );
    }

    /**
     * Valida el payload de la API externa antes de procesarlo.
     */
    private function validate(array $data, array $rules): void
    {
        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            // En un caso real, podríamos registrar en Log y omitir el registro en lugar de parar todo
            throw new InvalidArgumentException(
                "Estructura inválida desde la API externa: " . json_encode($validator->errors()->all())
            );
        }
    }

    /**
     * Extrae el ID numérico del final de una URL de la API de Rick and Morty.
     * Si la URL está vacía (ej: origen "unknown"), devuelve null.
     */
    private function extractIdFromUrl(string $url): ?int
    {
        if (empty($url)) {
            return null;
        }

        $parts = explode('/', rtrim($url, '/'));
        $id = end($parts);

        return is_numeric($id) ? (int) $id : null;
    }
}