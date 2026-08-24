<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Location extends Model
{
    use HasFactory;

    protected $fillable = [
        'external_id',
        'name',
        'type',
        'dimension',
    ];

    /**
     * Personajes que tienen esta localización como origen.
     */
    public function nativeCharacters(): HasMany
    {
        return $this->hasMany(Character::class, 'origin_id');
    }

    /**
     * Personajes que actualmente residen en esta localización.
     */
    public function residents(): HasMany
    {
        return $this->hasMany(Character::class, 'location_id');
    }
}
