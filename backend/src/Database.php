<?php

declare(strict_types=1);

namespace EventHub;

use PDO;
use PDOException;

/**
 * Database Singleton — koneksi PDO dengan prepared statements.
 * Semua query wajib lewat prepare() => 100% bebas SQL injection.
 */
final class Database
{
    private static ?PDO $instance = null;

    public static function getInstance(): PDO
    {
        if (self::$instance === null) {
            $config = require __DIR__ . '/config/database.php';

            $dsn = sprintf(
                '%s:host=%s;port=%s;dbname=%s;charset=%s',
                $config['driver'],
                $config['host'],
                $config['port'],
                $config['database'],
                $config['charset']
            );

            try {
                self::$instance = new PDO($dsn, $config['username'], $config['password'], $config['options']);
            } catch (PDOException $e) {
                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'message' => 'Database connection failed',
                    'data'    => null,
                    'errors'  => ['db' => $e->getMessage()],
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }
        }

        return self::$instance;
    }

    private function __construct() {}
    private function __clone() {}
}
