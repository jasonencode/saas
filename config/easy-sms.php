<?php

return [
    'debug' => env('EASY_SMS_DEBUG', true),

    'length' => env('EASY_SMS_CODE_LENGTH', 4),

    'default' => [
        // 网关调用策略，默认：顺序调用
        'strategy' => \Overtrue\EasySms\Strategies\OrderStrategy::class,

        // 默认可用的发送网关
        'gateways' => [
            'debug',
            'aliyun',
        ],
    ],

    'gateways' => [
        'debug' => [
            'code' => '0000',
        ],

        'aliyun' => [
            'access_key_id' => env('EASY_SMS_ALIYUN_ACCESS_KEY_ID'),
            'access_key_secret' => env('EASY_SMS_ALIYUN_ACCESS_KEY_SECRET'),
        ],
    ],
];
