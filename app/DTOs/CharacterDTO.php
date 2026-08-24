<?php

namespace App\DTOs;

readonly class CharacterDTO
{
    public function __construct(
        public int $externalId,
        public string $name,
        public string $status,
        public string $species,
        public ?string $type,
        public string $gender,
        public string $image,
        public ?int $originExternalId,
        public ?int $locationExternalId,
        public array $episodeExternalIds
    ) {}
}