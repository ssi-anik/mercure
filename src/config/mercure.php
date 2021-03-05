<?php

return [
    'default' => env('MERCURE_HUB_CONNECTION', 'hub'),

    'enable_broadcasting' => env('ENABLE_MERCURE_BROADCASTING', false),
    'enable_notification' => env('ENABLE_MERCURE_NOTIFICATION', false),

    'connections' => [
        'hub' => [
            'url' => env('MERCURE_PUBLISH_HUB_URL', 'http://127.0.0.1:9000/.well-known/mercure'),
            'jwt' => env('MERCURE_PUBLISH_JWT'),
        ],
    ],
];
