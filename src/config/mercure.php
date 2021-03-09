<?php

return [
    /**
     * Default hub connection to select when resolving the instance/driver.
     */
    'default' => env('MERCURE_HUB_CONNECTION', 'hub'),

    /**
     * By default, the package doesn't register the broadcasting driver.
     * If you want to register the broadcasting driver set the environment
     * variable to `true`.
     */
    'enable_broadcasting' => env('ENABLE_MERCURE_BROADCASTING', false),

    /**
     * By default, the package doesn't register the notification channel.
     * If you want to register the notification channel set the environment
     * variable to `true`.
     */
    'enable_notification' => env('ENABLE_MERCURE_NOTIFICATION', false),

    'connections' => [
        'hub' => [
            /**
             * Full URL for the mercure hub's publish endpoint.
             */
            'url' => env('MERCURE_PUBLISH_HUB_URL', 'http://127.0.0.1:3000/.well-known/mercure'),
            /**
             * The signed JWT which will be used to publish messages
             */
            'jwt' => env('MERCURE_PUBLISH_JWT'),
        ],
    ],
];
