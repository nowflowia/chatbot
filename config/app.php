<?php

return [
    'name'     => env('APP_NAME', 'ChatBot System'),
    'env'      => env('APP_ENV', 'production'),
    'debug'    => (bool) env('APP_DEBUG', false),
    'url'      => env('APP_URL', 'http://localhost'),
    'key'      => env('APP_KEY', ''),
    'timezone' => env('TIMEZONE', 'America/Sao_Paulo'),
    'locale'   => env('LOCALE', 'pt_BR'),

    'session' => [
        'lifetime' => (int) env('SESSION_LIFETIME', 120),
        'secure'   => (bool) env('SESSION_SECURE', false),
    ],

    'mail' => [
        'driver'     => env('MAIL_DRIVER', 'smtp'),
        'host'       => env('MAIL_HOST', 'smtp.mailtrap.io'),
        'port'       => (int) env('MAIL_PORT', 2525),
        'username'   => env('MAIL_USERNAME'),
        'password'   => env('MAIL_PASSWORD'),
        'encryption' => env('MAIL_ENCRYPTION', 'tls'),
        'from'       => [
            'address' => env('MAIL_FROM_ADDRESS', 'noreply@chatbot.local'),
            'name'    => env('MAIL_FROM_NAME', 'ChatBot System'),
        ],
    ],
];
