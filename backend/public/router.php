<?php

declare(strict_types=1);

/**
 * Router untuk PHP built-in server (php -S).
 * File statis diserahkan langsung, request lain ke index.php.
 * Jalankan: php -S 0.0.0.0:8080 -t public public/router.php
 */

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$file = __DIR__ . $path;

if ($path !== '/' && is_file($file)) {
    return false; // serahkan ke built-in server
}

require __DIR__ . '/index.php';
