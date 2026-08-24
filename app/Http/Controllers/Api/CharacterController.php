<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CharacterResource;
use App\Models\Character;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use OpenApi\Attributes as OA;

class CharacterController extends Controller
{
    #[OA\Get(
        path: '/api/characters',
        summary: 'Listado de personajes paginado y filtrado',
        tags: ['Personajes']
    )]
    #[OA\Parameter(
        name: 'name',
        description: 'Filtrar por nombre',
        in: 'query',
        required: false,
        schema: new OA\Schema(type: 'string')
    )]
    #[OA\Parameter(
        name: 'status',
        description: 'Filtrar por estado (Alive, Dead, unknown)',
        in: 'query',
        required: false,
        schema: new OA\Schema(type: 'string')
    )]
    #[OA\Parameter(
        name: 'species',
        description: 'Filtrar por especie',
        in: 'query',
        required: false,
        schema: new OA\Schema(type: 'string')
    )]
    #[OA\Response(
        response: 200,
        description: 'Lista de personajes'
    )]
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Character::with(['origin', 'location']);

        // Filtro por Nombre (búsqueda parcial)
        if ($request->filled('name')) {
            $query->where('name', 'like', '%' . $request->query('name') . '%');
        }

        // Filtro por Estado (Alive, Dead, unknown)
        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        // Filtro por Especie
        if ($request->filled('species')) {
            $query->where('species', 'like', '%' . $request->query('species') . '%');
        }

        return CharacterResource::collection($query->paginate(15));
    }

    #[OA\Get(
        path: '/api/characters/{character}',
        summary: 'Obtiene el detalle de un personaje específico',
        tags: ['Personajes']
    )]
    #[OA\Parameter(
        name: 'character',
        description: 'ID interno del personaje en la base de datos',
        in: 'path',
        required: true,
        schema: new OA\Schema(type: 'integer', example: 1)
    )]
    #[OA\Response(
        response: 200,
        description: 'Detalle del personaje, incluyendo episodios relacionados'
    )]
    #[OA\Response(
        response: 404,
        description: 'Personaje no encontrado'
    )]
    public function show(Character $character): CharacterResource
    {
        $character->load(['origin', 'location', 'episodes']);

        return new CharacterResource($character);
    }

    #[OA\Post(
        path: '/api/characters/{character}/favorite',
        summary: 'Añade o elimina un personaje de favoritos',
        security: [['bearerAuth' => []]],
        tags: ['Favoritos']
    )]
    #[OA\Parameter(
        name: 'character',
        description: 'ID del personaje',
        in: 'path',
        required: true,
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\Response(
        response: 200,
        description: 'Estado del favorito actualizado'
    )]
    #[OA\Response(
        response: 401,
        description: 'No autenticado'
    )]
    #[OA\Response(
        response: 404,
        description: 'Personaje no encontrado'
    )]
    public function toggleFavorite(Request $request, Character $character): JsonResponse
    {
        $user = $request->user();

        $changes = $user->favoriteCharacters()->toggle($character->id);

        $isFavorite = count($changes['attached']) > 0;

        return response()->json([
            'message' => $isFavorite 
                ? 'Personaje añadido a tus favoritos.' 
                : 'Personaje eliminado de tus favoritos.',
            'is_favorite' => $isFavorite,
        ]);
    }

    #[OA\Get(
        path: '/api/favorites',
        summary: 'Listado de personajes favoritos del usuario autenticado',
        security: [['bearerAuth' => []]],
        tags: ['Favoritos']
    )]
    #[OA\Response(
        response: 200,
        description: 'Lista paginada de los personajes marcados como favoritos'
    )]
    #[OA\Response(
        response: 401,
        description: 'No autenticado'
    )]
    public function favorites(Request $request): AnonymousResourceCollection
    {
        $favorites = $request->user()
            ->favoriteCharacters()
            ->with(['origin', 'location'])
            ->paginate(15);

        return CharacterResource::collection($favorites);
    }
}