<?php

/*
 * Cross-Origin Resource Sharing (CORS)
 *
 * Necessário para que o site estático (pascomjerico.com.br) possa
 * registrar visualizações na API do CMS (admin.pascomjerico.com.br).
 */

return [

    'paths' => ['api/*'],

    'allowed_methods' => ['POST'],

    'allowed_origins' => array_filter(explode(',', env('CORS_ALLOWED_ORIGINS', ''))),

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['Content-Type', 'Accept', 'X-Requested-With'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false,
];
