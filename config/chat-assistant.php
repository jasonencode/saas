<?php

use App\Ai\Agents\OrderReport;
use App\Ai\Agents\OrderSearch;

return [
    // 可配置多个 Agent，对话框中可切换；数组键用于区分各自的会话记录。
    'agents' => [
        'order' => OrderSearch::class,
        'report' => OrderReport::class,
    ],

    // 界面配置（宽度、高度、全屏、新会话按钮、名称）默认沿用包配置：
    // FILAMENT_CHAT_ASSISTANT_WIDTH / HEIGHT / FULLSCREEN / NEW_CONVERSATION / TITLE。
    // 需要在本项目覆盖时，再显式添加 ui 配置。

    'panels' => [],
];
