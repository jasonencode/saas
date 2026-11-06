<?php

use App\Ai\Agents\GeneralAssistant;

return [
    // 所有面板共用一个 Agent；数组键用于区分会话记录。
    'agents' => [
        'general' => GeneralAssistant::class,
    ],

    'ui' => [
        'toggle_icon' => 'images/avatar-white.png',
    ],

    // ui 未配置的项沿用包默认值（FILAMENT_CHAT_ASSISTANT_WIDTH / HEIGHT / FULLSCREEN / NEW_CONVERSATION）。
];
