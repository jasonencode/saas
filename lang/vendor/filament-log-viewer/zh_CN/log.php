<?php

declare(strict_types=1);

return [
    'dashboard' => [
        'title' => '日志分析',
    ],
    'show' => [
        'title' => '日志 :log',
    ],
    'navigation' => [
        'group' => '日志',
        'label' => '系统日志',
        'sort' => 100,
    ],
    'table' => [
        'columns' => [
            'date' => [
                'label' => '日期',
            ],
            'level' => [
                'label' => '级别',
            ],
            'message' => [
                'label' => '信息',
            ],
        ],
        'actions' => [
            'view' => [
                'label' => '详情',
            ],
            'download' => [
                'label' => '下载日志 :log',
                'bulk' => [
                    'label' => '批量下载',
                    'error' => '下载出错',
                ],
            ],
            'delete' => [
                'label' => '删除日志 :log',
                'success' => '日志删除成功',
                'error' => '日志删除失败',
                'bulk' => [
                    'label' => '删除选中日志',
                ],
            ],
            'close' => [
                'label' => '返回',
            ],
        ],
        'detail' => [
            'title' => '详情',
            'file_path' => '文件路径',
            'log_entries' => '日志条目',
            'size' => '大小',
            'created_at' => '创建时间',
            'updated_at' => '更新时间',
        ],
    ],
    'levels' => [
        'all' => '全部',
        'emergency' => '突发事件',
        'alert' => '警报',
        'critical' => '严重',
        'error' => '错误',
        'warning' => '警告',
        'notice' => '注意',
        'info' => '信息',
        'debug' => '调试',
    ],
];
