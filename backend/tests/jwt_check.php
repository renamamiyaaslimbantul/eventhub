<?php

declare(strict_types=1);

// Quick JWT smoke test — run: php tests/jwt_check.php

require __DIR__ . '/../vendor/autoload.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

$key = '0123456789abcdef0123456789abcdef0123456789abcdef0123456789abcdef';

$payload = [
    'iat'  => time(),
    'exp'  => time() + 3600,
    'sub'  => '1',
    'data' => ['id' => 1, 'name' => 'Test', 'email' => 't@t.com', 'role' => 'admin'],
];

$token = JWT::encode($payload, $key, 'HS256');
echo "encode OK: " . substr($token, 0, 40) . "...\n";

$decoded = (array) JWT::decode($token, new Key($key, 'HS256'));
echo "decode OK: sub={$decoded['sub']} role={$decoded['data']->role}\n";

// Tampered token harus lempar exception
try {
    $bad = substr($token, 0, -2) . ($token[-1] === 'x' ? 'y' : 'x');
    JWT::decode($bad, new Key($key, 'HS256'));
    echo "FAIL: tampered token accepted!\n";
    exit(1);
} catch (Throwable $e) {
    echo "tamper detection OK: " . get_class($e) . "\n";
}

// Wrong key harus lempar exception
try {
    JWT::decode($token, new Key('wrong-key-32-chars-long-aaaaaaaaaa', 'HS256'));
    echo "FAIL: wrong key accepted!\n";
    exit(1);
} catch (Throwable $e) {
    echo "wrong key rejection OK: " . get_class($e) . "\n";
}

echo "ALL JWT CHECKS PASSED\n";
