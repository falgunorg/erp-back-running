<?php

return [
    'default' => env('MAIL_MAILER', 'smtp'),
    'mailers' => [
        'smtp' => [
            'transport' => 'smtp',
            'host' => env('MAIL_HOST', 'mail.fashion-product.com.bd'),
            'port' => env('MAIL_PORT', 465),
            'encryption' => env('MAIL_ENCRYPTION', 'ssl'),
            'username' => env('MAIL_USERNAME', 'faisal@fashion-product.com.bd'),
            'password' => env('MAIL_PASSWORD', 'Jms@8346'),
            'timeout' => null,
            'auth_mode' => null,
            'stream' => [
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true,
                ],
            ],
        ],
        'log' => [
            'transport' => 'log',
            'channel' => env('MAIL_LOG_CHANNEL'),
        ],
    // Add other mailers if necessary, such as 'ses', 'mailgun', etc.
    ],
    'from' => [
        'address' => env('MAIL_FROM_ADDRESS', 'your_email@gmail.com'),
        'name' => env('MAIL_FROM_NAME', 'Your Name'),
    ],
    'markdown' => [
        'theme' => 'default',
        'paths' => [
            resource_path('views/vendor/mail'),
        ],
    ],
    'stream' => [
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true,
        ],
    ],
    'pretend' => false,
];
