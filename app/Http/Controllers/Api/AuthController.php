<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use OpenApi\Attributes as OA;

class AuthController extends Controller
{
    #[OA\Post(
        path: '/api/register',
        summary: 'Registra un nuevo usuario',
        tags: ['Autenticación']
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['name', 'email', 'password'],
            properties: [
                new OA\Property(property: 'name', type: 'string', example: 'Rick Sanchez'),
                new OA\Property(property: 'email', type: 'string', format: 'email', example: 'rick@citadel.com'),
                new OA\Property(property: 'password', type: 'string', format: 'password', minLength: 8, example: 'secret123')
            ]
        )
    )]
    #[OA\Response(
        response: 201,
        description: 'Usuario registrado exitosamente',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'message', type: 'string', example: 'Usuario registrado exitosamente.'),
                new OA\Property(property: 'access_token', type: 'string', example: '1|abc123xyz...'),
                new OA\Property(property: 'token_type', type: 'string', example: 'Bearer')
            ]
        )
    )]
    #[OA\Response(
        response: 422,
        description: 'Error de validación (email duplicado o campos inválidos)'
    )]   
    public function register(RegisterRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Usuario registrado exitosamente.',
            'access_token' => $token,
            'token_type' => 'Bearer',
        ], 201);
    }

    #[OA\Post(
        path: '/api/login',
        summary: 'Inicia sesión y genera un token Sanctum',
        tags: ['Autenticación']
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['email', 'password'],
            properties: [
                new OA\Property(property: 'email', type: 'string', format: 'email', example: 'user@example.com'),
                new OA\Property(property: 'password', type: 'string', format: 'password', example: 'secret123')
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: 'Login exitoso',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'access_token', type: 'string', example: '1|abc123xyz...'),
                new OA\Property(property: 'token_type', type: 'string', example: 'Bearer')
            ]
        )
    )]
    #[OA\Response(
        response: 422,
        description: 'Credenciales incorrectas'
    )]
    public function login(LoginRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $user = User::where('email', $validated['email'])->first();

        if (!$user || !Hash::check($validated['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Las credenciales proporcionadas son incorrectas.'],
            ]);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
        ]);
    }

    #[OA\Post(
        path: '/api/logout',
        summary: 'Cierra la sesión revocando el token actual',
        security: [['bearerAuth' => []]],
        tags: ['Autenticación']
    )]
    #[OA\Response(
        response: 200,
        description: 'Sesión cerrada correctamente',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'message', type: 'string', example: 'Sesión cerrada correctamente.')
            ]
        )
    )]
    #[OA\Response(
        response: 401,
        description: 'No autenticado'
    )]
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Sesión cerrada correctamente.']);
    }
}