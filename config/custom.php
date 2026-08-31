<?php

return [
    /*
    |--------------------------------------------------------------------------
    | 强制开启HTTPS
    |--------------------------------------------------------------------------
    */
    'force_https' => env('FORCE_HTTPS', false),

    /*
    |--------------------------------------------------------------------------
    | 多节点时，服务器 ID 标记
    |--------------------------------------------------------------------------
    */
    'server_id' => env('SERVER_ID'),

    /*
    |--------------------------------------------------------------------------
    | 域名配置
    |--------------------------------------------------------------------------
    | 默认域名 default_domain
    | api域名 api_domain
    | 后台域名 backend_domain
    | 租户域名 tenant_domain
    */
    'domains' => [
        'default_domain' => env('DEFAULT_DOMAIN', ''),
        'api_domain' => env('API_DOMAIN', ''),
        'backend_domain' => env('BACKEND_DOMAIN', ''),
        'tenant_domain' => env('TENANT_DOMAIN', ''),
    ],

    /*
    |--------------------------------------------------------------------------
    | 商城配置
    |--------------------------------------------------------------------------
    */
    'mall' => [
        'order_expired_minutes' => env('MALL_ORDER_EXPIRED_MINUTES', 30),

        // 推荐商品（GET /api/mall/products/recommends）
        'recommend' => [
            // 候选上限：按手动排序预取的最大条数（随机抽样的来源范围）
            'candidate_cap' => 500,
            // 候选池大小：每次请求从 candidate_cap 范围内随机抽取的评分条数
            'candidate_pool' => 200,
            // 评分权重：热度 + 新鲜度 + 个性化
            'weight' => [
                'popularity' => 0.5,
                'freshness' => 0.3,
                'personal' => 0.2,
            ],
            // 新鲜度衰减周期（天）：上架 N 天后新鲜度分衰减至 ~37%
            'freshness_days' => 7,
            // 结果中每个品牌最多出现次数
            'brand_max' => 2,
            // 个性化偏好回溯窗口（天）
            'personal_days' => 90,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | 区块链模块配置
    |--------------------------------------------------------------------------
    */
    'block_chain' => [
        'public_key' => env('BLOCK_CHAIN_PUBLIC_KEY'),
    ],

    /*
    |--------------------------------------------------------------------------
    | 分页配置
    |--------------------------------------------------------------------------
    | default_per_page: 默认每页数量
    | max_per_page: 最大每页数量
    */
    'pagination' => [
        'default_per_page' => env('PAGINATION_DEFAULT_PER_PAGE', 15),
        'max_per_page' => env('PAGINATION_MAX_PER_PAGE', 50),
    ],

    /*
    |--------------------------------------------------------------------------
    | 频率限制配置
    |--------------------------------------------------------------------------
    */
    'rate_limits' => [
        'api' => env('RATE_LIMIT_API', 60),
        'upload' => env('RATE_LIMIT_UPLOAD', 10),
        'login' => env('RATE_LIMIT_LOGIN', 5),
        'sms' => env('RATE_LIMIT_SMS', 2),
        'register' => env('RATE_LIMIT_REGISTER', 3),
        'password_reset' => env('RATE_LIMIT_PASSWORD_RESET', 3),
        'default' => env('RATE_LIMIT_DEFAULT', 30),
    ],
];
