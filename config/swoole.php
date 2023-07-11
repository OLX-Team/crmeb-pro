<?php

use app\webscoket\Manager;
use Swoole\Table;


return strtoupper(substr(php_uname('s'), 0, 3)) === 'WIN' ? [] : [
    'http' => [
        'enable' => true,
        'host' => '0.0.0.0',
        'port' => 20199,
        'worker_num' => swoole_cpu_num() * 4,
        'options' => [
            'package_max_length' => 50 * 1024 * 1024,
        ],
    ],
    'websocket' => [
        'enable' => true,
        'handler' => Manager::class,
        'ping_interval' => 25000,
        'ping_timeout' => 60000,
        'room' => [
            'type' => 'table',
            'table' => [
                'room_rows' => 4096,
                'room_size' => 2048,
                'client_rows' => 8192,
                'client_size' => 2048,
            ],
            'redis' => [
                'host' => '127.0.0.1',
                'port' => 6379,
                'max_active' => 3,
                'max_wait_time' => 5,
            ],
        ],
        'listen' => [],
        'subscribe' => [],
    ],
    'rpc' => [
        'server' => [
            'enable' => false,
            'host' => '0.0.0.0',
            'port' => 9000,
            'worker_num' => swoole_cpu_num(),
            'services' => [
            ],
        ],
        'client' => [
        ],
    ],
    //队列
    'queue' => [
        'enable' => true,
        'workers' => [
            'CRMEB_PRO' => [],
            'CRMEB_PRO_BATCH' => [],
            'CRMEB_PRO_TASK' => [],
            'CRMEB_PRO_ERP' => [],
        ],
    ],
    //热更新
    'hot_update' => [
        'enable' => env('APP_DEBUG', false),
        'name' => ['*.php'],
        'include' => [app_path(), root_path('crmeb')],
        'exclude' => [],
    ],
    //连接池
    'pool' => [
        'db' => [
            'enable' => true,
            'max_active' => 100,
            'max_wait_time' => 5,
        ],
        'cache' => [
            'enable' => true,
            'max_active' => 3,
            'max_wait_time' => 5,
        ],
        //自定义连接池
    ],
    'tables' => [//高性能内存数据库
        'user' => [
            'size' => 20480,
            'columns' => [
                ['name' => 'fd', 'type' => Table::TYPE_STRING, 'size' => 50],
                ['name' => 'type', 'type' => Table::TYPE_INT],
                ['name' => 'uid', 'type' => Table::TYPE_INT],
                ['name' => 'to_uid', 'type' => Table::TYPE_STRING, 'size' => 50],
                ['name' => 'tourist', 'type' => Table::TYPE_INT]
            ]
        ]
    ],
    //每个worker里需要预加载以共用的实例
    'concretes' => [],
    //重置器
    'resetters' => [],
    //每次请求前需要清空的实例
    'instances' => [],
    //每次请求前需要重新执行的服务
    'services' => [],
];
