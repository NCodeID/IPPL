<?php

namespace App\Http\Controllers;

use OpenApi\Attributes as OA;

#[OA\Info(
    title: 'IMPAL API',
    version: '1.0.0',
    description: 'IMPAL backend API documentation',
)]
#[OA\Server(
    url: L5_SWAGGER_CONST_HOST,
    description: 'IMPAL API Server',
)]
#[OA\SecurityScheme(
    securityScheme: 'sanctum',
    type: 'http',
    scheme: 'bearer',
    bearerFormat: 'Sanctum personal access token',
    description: 'Enter the Sanctum token only; Swagger adds the Bearer prefix.',
)]
abstract class Controller
{
    //
}
