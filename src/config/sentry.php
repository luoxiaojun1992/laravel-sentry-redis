<?php

return [
    'transport' => [\Lxj\Laravel\Sentry\Transports\RedisTransport::class, 'handle'],

    'redis_options' => [
        'queue_name' => env('SENTRY_REDIS_QUEUE_NAME', 'queue:sentry:transport'),
        'connection' => env('SENTRY_REDIS_CONNECTION', 'sentry'),
    ],
];
