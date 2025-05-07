<?php

return [
    'force_https' => env('FORCE_HTTPS', false),

    'server_id' => env('SERVER_ID'),

    'api_rate_limit' => env('API_RATE_LIMIT', 120),

    'domain' => [
        'default_domain' => env('DEFAULT_DOMAIN', ''),
        'api_domain' => env('API_DOMAIN', ''),
        'backend_domain' => env('BACKEND_DOMAIN', ''),
        'tenant_domain' => env('TENANT_DOMAIN', ''),
    ],

    'tenant_user_default_password' => env('TENANT_USER_DEFAULT_PASSWORD'),
];
