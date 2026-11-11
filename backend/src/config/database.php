<?php
declare(strict_types=1);

/**
 * Konfigurasi Database
 * Menggunakan PDO dengan prepared statements untuk cegah SQL injection.
 */
return [
    /*
    |--------------------------------------------------------------------------
    | Database Connection
    |--------------------------------------------------------------------------
    */
    'driver'    => $_ENV['DB_CONNECTION']  ?? 'mysql',
    'host'      => $_ENV['DB_HOST']         ?? '127.0.0.1',
    'port'      => $_ENV['DB_PORT']         ?? '3306',
    'database'  => $_ENV['DB_DATABASE']     ?? 'eventhub',
    'username'  => $_ENV['DB_USERNAME']     ?? 'root',
    'password'  => $_ENV['DB_PASSWORD']     ?? '',
    'charset'   => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
    'prefix'    => '',

    /*
    |--------------------------------------------------------------------------
    | PDO Options
    |--------------------------------------------------------------------------
    */
    'options'   => [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false, // Gunakan prepared statements nyata
        PDO::ATTR_STRINGIFY_FETCHES  => false,
    ],
];
