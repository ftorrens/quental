<?php

namespace App\DTOs;

readonly class LocationDTO
{
    public function __construct(
        public ?int $externalId, // Nullable porque existe el origen "unknown" sin ID
        public string $name,
        public ?string $type,
        public ?string $dimension
    ) {}
}