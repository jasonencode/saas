<?php

use App\Ai\Agents\OrderReport;
use App\Ai\Agents\OrderSearch;

return [
    // 按面板区分 Agent：键为面板 ID，'*' 兜底未列出的面板。
    // 数组键（order / report）用于区分各自的会话记录；两个面板目前共用同一组，
    // 需要差异化时直接增删各自分组下的条目即可。
    'agents' => [
        'backend' => [
            'order' => OrderSearch::class,
            'report' => OrderReport::class,
        ],
        'tenant' => [
            'order' => OrderSearch::class,
            'report' => OrderReport::class,
        ],
    ],

    'ui' => [
        'toggle_icon' => 'images/avatar.png',
    ],

    // ui 未配置的项沿用包默认值（FILAMENT_CHAT_ASSISTANT_WIDTH / HEIGHT / FULLSCREEN / NEW_CONVERSATION）。
];
