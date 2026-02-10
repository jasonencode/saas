<?php

/**
 * @disregard P1009
 */
return [
    'user_model' => [
        // @phpstan-ignore class.notFound
        'class' => \App\Models\User::class, // Default user model
        'fields' => [
            'id' => 'id', // Default user model ID field
            'avatar_url' => 'avatar', // Default user model avatar field
            'heading' => 'name', // Default user model name field
            'description' => 'username', // Default user model email field
        ],
    ],
    'active_state' => [
        'show' => false, // Show active state by default
        'field' => 'is_active', // Default field for active state
    ],
];
