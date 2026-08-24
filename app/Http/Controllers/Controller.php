<?php

namespace App\Http\Controllers;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: "1.0.0",
    title: "Rick and Morty API",
    description: "API REST de gestión de personajes y favoritos para la prueba técnica."
)]
#[OA\Server(
    url: L5_SWAGGER_CONST_HOST,
    description: "Servidor Local"
)]
#[OA\SecurityScheme(
    securityScheme: "bearerAuth",
    type: "http",
    scheme: "bearer",
    bearerFormat: "JWT"
)]

abstract class Controller
{
    //
}
