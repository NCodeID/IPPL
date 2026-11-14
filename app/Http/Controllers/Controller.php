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
    type: 'apiKey',
    in: 'cookie',
    name: 'laravel_session',
    description: 'Session cookie. Run POST /api/login in this page first; the browser then sends the cookie automatically.',
)]
abstract class Controller
{
    //
}
