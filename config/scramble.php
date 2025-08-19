<?php

use Dedoc\Scramble\Http\Middleware\RestrictedDocsAccess;

return [
    // Habilita a UI (deixe true no local)
    'enabled' => env('SCRAMBLE_ENABLED', true),

    // Caminho da UI (ex.: /docs)
    'path' => 'docs',

    'openapi' => [
        'info' => [
            'title' => 'API - Autenticação JWT',
            'version' => '1.0.0',
            'description' => 'Rotas de cadastro, login, me, logout, refresh.',
        ],

        // Adiciona Bearer JWT na UI
        'securitySchemes' => [
            'bearerAuth' => [
                'type' => 'http',
                'scheme' => 'bearer',
                'bearerFormat' => 'JWT',
            ],
        ],

        // Define Bearer como segurança padrão (opcional)
        'security' => [
            ['bearerAuth' => []],
        ],
    ],
];
