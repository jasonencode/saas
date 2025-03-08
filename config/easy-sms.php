<?php

use Overtrue\EasySms\Strategies\OrderStrategy;

return [
    'timeout' => 5.0,
    'debug' => true,
    'length' => 6,
    'default' => [
        'strategy' => OrderStrategy::class,
        'gateways' => [
            'debug', 'aliyun',
        ],
    ],
    'gateways' => [
        'debug' => [
            'code' => '0000',
        ],
        'aliyun' => [
            'access_key_id' => '',
            'access_key_secret' => '',
            'sign_name' => '',
        ],
    ],
];
