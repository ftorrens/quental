<?php

namespace App\DTOs;

readonly class EpisodeDTO
{
    public function __construct(
        public int $externalId,
        public string $name,
        public string $airDate,
        public string $episodeCode
    ) {}
}