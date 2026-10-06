<?php

declare(strict_types=1);

use Marko\Config\Env;

return [
    'enabled' => Env::bool('DEBUGBAR_ENABLED', Env::bool('APP_DEBUG', false)),
    'allow_production' => Env::bool('DEBUGBAR_ALLOW_PRODUCTION', false),
    'inject' => Env::bool('DEBUGBAR_INJECT', true),
    'capture_cli' => Env::bool('DEBUGBAR_CAPTURE_CLI', false),
    'theme' => Env::string('DEBUGBAR_THEME', 'auto'),
    'route' => [
        'open' => Env::bool('DEBUGBAR_ROUTE_OPEN', false),
        'allowed_ips' => ['127.0.0.1', '::1'],
        'trusted_proxies' => [],
    ],
    'storage' => [
        'enabled' => Env::bool('DEBUGBAR_STORAGE_ENABLED', true),
        'path' => Env::string('DEBUGBAR_STORAGE_PATH', 'storage/debugbar'),
        'max_files' => Env::int('DEBUGBAR_STORAGE_MAX_FILES', 100, min: 0),
    ],
    'collectors' => [
        'messages' => Env::bool('DEBUGBAR_COLLECTORS_MESSAGES', true),
        'time' => Env::bool('DEBUGBAR_COLLECTORS_TIME', true),
        'memory' => Env::bool('DEBUGBAR_COLLECTORS_MEMORY', true),
        'request' => Env::bool('DEBUGBAR_COLLECTORS_REQUEST', true),
        'response' => Env::bool('DEBUGBAR_COLLECTORS_RESPONSE', true),
        'inertia' => Env::bool('DEBUGBAR_COLLECTORS_INERTIA', true),
        'views' => Env::bool('DEBUGBAR_COLLECTORS_VIEWS', true),
        'database' => Env::bool('DEBUGBAR_COLLECTORS_DATABASE', true),
        'logs' => Env::bool('DEBUGBAR_COLLECTORS_LOGS', true),
        'config' => Env::bool('DEBUGBAR_COLLECTORS_CONFIG', false),
    ],
    'options' => [
        'messages' => [
            'trace' => Env::bool('DEBUGBAR_OPTIONS_MESSAGES_TRACE', false),
        ],
        'config' => [
            'masked' => [
                'key',
                '*.key',
                'password',
                '*.password',
                'secret',
                '*.secret',
                'token',
                '*.token',
                'dsn',
                '*.dsn',
                '*.api_key',
                '*.private_key',
            ],
        ],
        'database' => [
            'with_bindings' => Env::bool('DEBUGBAR_OPTIONS_DATABASE_WITH_BINDINGS', true),
            'slow_threshold_ms' => Env::int('DEBUGBAR_OPTIONS_DATABASE_SLOW_THRESHOLD_MS', 100, min: 0),
        ],
    ],
];
