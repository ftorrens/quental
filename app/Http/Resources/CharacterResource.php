<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CharacterResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'external_id' => $this->external_id,
            'name' => $this->name,
            'status' => $this->status,
            'species' => $this->species,
            'type' => $this->type,
            'gender' => $this->gender,
            'image' => $this->image,
            'origin' => $this->origin ? [
                'id' => $this->origin->id,
                'name' => $this->origin->name,
            ] : null,
            'location' => $this->location ? [
                'id' => $this->location->id,
                'name' => $this->location->name,
            ] : null,
            'episodes' => $this->whenLoaded('episodes', function () {
                return $this->episodes->map(fn ($ep) => [
                    'id' => $ep->id,
                    'name' => $ep->name,
                    'episode_code' => $ep->episode_code,
                ]);
            }),
        ];
    }
}