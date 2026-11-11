<?php
declare(strict_types=1);

/**
 * Konfigurasi JWT Authentication
 */
return [
    /*
    |--------------------------------------------------------------------------
    | JWT Secret Key
    |--------------------------------------------------------------------------
    | Minimum 256-bit (32 karakter+). Generate dengan:
    |   php -r "echo bin2hex(random_bytes(32));"
    */
    'secret'    => $_ENV['JWT_SECRET']     ?? 'change-me-in-production-32chars!!',
    'algorithm' => $_ENV['JWT_ALGORITHM']   ?? 'HS256',
    'expiry'    => (int)($_ENV['JWT_EXPIRY'] ?? 86400), // detik

    /*
    |--------------------------------------------------------------------------
    | Payload default (claims)
    |--------------------------------------------------------------------------
    */
    'default_claims' => [
        'iss' => $_ENV['APP_URL'] ?? 'http://localhost:8080',
    ],
];
