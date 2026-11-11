<?php

declare(strict_types=1);

/**
 * EventHub API — Entry Point (front controller)
 * Semua request HTTP masuk lewat file ini.
 */

// 1. Composer autoload
require_once __DIR__ . '/../vendor/autoload.php';

// 2. Load .env (vlucas/phpdotenv)
if (file_exists(__DIR__ . '/../.env') && class_exists('Dotenv\Dotenv')) {
    Dotenv\Dotenv::createImmutable(__DIR__ . '/..')->safeLoad();
}

// 3. Timezone
date_default_timezone_set('Asia/Jakarta');

// 4. Error handling: tampilkan error saat dev, sembunyikan di prod
$debug = ($_ENV['APP_DEBUG'] ?? 'true') === 'true';
if ($debug) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    error_reporting(0);
}

// 5. Header JSON
header('Content-Type: application/json; charset=utf-8');

// 6. CORS
EventHub\Middlewares\CorsMiddleware::handle();

// 7. Dispatch
$router = require __DIR__ . '/../routes/api.php';

$method = $_SERVER['REQUEST_METHOD'];
$uri    = $_SERVER['REQUEST_URI'] ?? '/';

try {
    $router->dispatch($method, $uri);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $debug ? $e->getMessage() : 'Internal Server Error',
        'data'    => null,
        'errors'  => $debug ? ['exception' => get_class($e), 'line' => $e->getLine()] : null,
    ], JSON_UNESCAPED_UNICODE);
}
