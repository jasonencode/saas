<?php

return [
    'force_https' => env('FORCE_HTTPS', false),

    'server_id' => env('SERVER_ID'),

    'api_rate_limit' => env('API_RATE_LIMIT', 120),

    'domains' => [
        'default_domain' => env('DEFAULT_DOMAIN', ''),
        'api_domain' => env('API_DOMAIN', ''),
        'backend_domain' => env('BACKEND_DOMAIN', ''),
        'tenant_domain' => env('TENANT_DOMAIN', ''),
    ],

    'tenant_user_default_password' => env('TENANT_USER_DEFAULT_PASSWORD', 'a123456'),

    'table_use_foreign' => env('TABLE_USE_FOREIGN', true),
];
