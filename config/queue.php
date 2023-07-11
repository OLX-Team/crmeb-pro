<?php

use think\facade\Env;

return [
    'default' => 'redis',
    'connections' => [
        'sync' => [
            'driver' => 'sync',
        ],
        'database' => [
            'driver' => 'database',
            'queue' => 'default',
            'table' => 'jobs',
        ],
        'redis' => [
            'driver' => 'redis',
            'queue' => Env::get('queue.listen_name', 'CRMEB_PRO'),
            'batch_queue' => Env::get('queue.batch_listen_name', 'CRMEB_PRO_BATCH'),
            'host' => Env::get('redis.redis_hostname', '127.0.0.1'),
            'port' => Env::get('redis.port', 6379),
            'password' => Env::get('redis.redis_password', ''),
            'select' => Env::get('redis.select', 0),
            'timeout' => 0,
            'persistent' => true,
        ],
    ],
    'failed' => [
        'type' => 'none',
        'table' => 'failed_jobs',
    ],
];
