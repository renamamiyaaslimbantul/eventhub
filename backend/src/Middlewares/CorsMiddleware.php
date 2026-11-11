<?php
declare(strict_types=1);

namespace EventHub\Middlewares;

/**
 * Middleware CORS — set header Access-Control-* sesuai config.
 */
final class CorsMiddleware
{
    public static function handle(): void
    {
        $config = require __DIR__ . '/../config/cors.php';

        $origin  = $_SERVER['HTTP_ORIGIN'] ?? '';
        $allowed = in_array($origin, $config['allowed_origins'], true)
                || in_array('*', $config['allowed_origins'], true);

        header('Access-Control-Allow-Origin: ' . ($allowed ? $origin : $config['allowed_origins'][0]));
        header('Access-Control-Allow-Methods: ' . implode(', ', $config['allowed_methods']));
        header('Access-Control-Allow-Headers: ' . implode(', ', $config['allowed_headers']));
        header('Access-Control-Max-Age: ' . $config['max_age']);
        header('Access-Control-Allow-Credentials: true');

        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(204);
            exit;
        }
    }
}
