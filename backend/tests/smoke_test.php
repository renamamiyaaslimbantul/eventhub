<?php
declare(strict_types=1);

/**
 * Smoke test sederhana — jalankan: php tests/smoke_test.php
 * Cek konfigurasi & koneksi DB tanpa butuh HTTP server.
 */

require __DIR__ . '/../vendor/autoload.php';

\Dotenv\Dotenv::createImmutable(__DIR__ . '/..')->safeLoad();

echo "== EventHub Smoke Test ==\n\n";

// 1. Config terbaca?
$jwt = require __DIR__ . '/../src/config/jwt.php';
assert(is_array($jwt) && isset($jwt['secret']));
echo "[OK] JWT config loaded\n";

// 2. Koneksi DB?
try {
    $db = EventHub\Database::getInstance();
    echo "[OK] Database connected: {$_ENV['DB_DATABASE']}\n";

    // 3. Tabel ada?
    foreach (['users', 'events', 'registrations'] as $table) {
        $stmt = $db->query("SELECT 1 FROM {$table} LIMIT 1");
        echo "[OK] Table {$table} accessible\n";
    }
} catch (Throwable $e) {
    echo "[FAIL] DB error: {$e->getMessage()}\n";
    exit(1);
}

// 4. JWT encode/decode?
$svc = new EventHub\Services\JwtService();
$token = $svc->generate(['id' => 1, 'name' => 'Test', 'email' => 't@t.com', 'role' => 'admin']);
$payload = $svc->validate($token);
assert($payload !== null && $payload['sub'] === '1');
echo "[OK] JWT generate + validate\n";

echo "\nSemua test lulus. Silakan jalankan server & import Postman collection.\n";
