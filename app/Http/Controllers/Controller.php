<?php

namespace App\Http\Controllers;

use OpenApi\Attributes as OA;

/**
 * Base controller providing shared OpenAPI metadata.
 */
#[OA\Info(
    version: '1.0.0',
    title: 'Task Notification System API',
    description: 'RESTful API for task management with real-time notifications via Pusher.',
    contact: new OA\Contact(email: 'admin@example.com'),
)]
#[OA\Server(url: '/api', description: 'API endpoint prefix')]
#[OA\SecurityScheme(
    securityScheme: 'sessionAuth',
    type: 'apiKey',
    in: 'cookie',
    name: 'laravel_session',
    description: 'Session-based authentication (Sanctum stateful). Obtain a session by logging in via the web form.',
)]
#[OA\Tag(name: 'Tasks', description: 'Task CRUD and status transitions')]
#[OA\Tag(name: 'Notifications', description: 'Notification listing and read-state management')]
abstract class Controller
{
    //
}
