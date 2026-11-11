<?php
declare(strict_types=1);

/**
 * Konfigurasi Aplikasi
 */
return [
    'name'    => $_ENV['APP_NAME'] ?? 'EventHub API',
    'env'     => $_ENV['APP_ENV']  ?? 'local',
    'debug'   => (bool)($_ENV['APP_DEBUG'] ?? true),
    'url'     => $_ENV['APP_URL']  ?? 'http://localhost:8080',
    'timezone'=> 'Asia/Jakarta',
];
