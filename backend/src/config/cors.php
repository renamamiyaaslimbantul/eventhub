<?php
declare(strict_types=1);

/**
 * Konfigurasi CORS
 */
return [
    'allowed_origins'  => array_map('trim', explode(',', $_ENV['CORS_ALLOWED_ORIGINS']  ?? 'http://localhost:3000')),
    'allowed_methods'  => array_map('trim', explode(',', $_ENV['CORS_ALLOWED_METHODS'] ?? 'GET,POST,PUT,PATCH,DELETE,OPTIONS')),
    'allowed_headers'  => array_map('trim', explode(',', $_ENV['CORS_ALLOWED_HEADERS'] ?? 'Content-Type,Authorization,X-Requested-With')),
    'max_age'          => 86400,
];
