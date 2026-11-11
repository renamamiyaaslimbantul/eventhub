<?php
declare(strict_types=1);

namespace EventHub\Middlewares;

use EventHub\Services\JwtService;
use EventHub\Helpers\ResponseHelper;

/**
 * Middleware JWT — proteksi endpoint privat.
 */
final class JwtMiddleware
{
    /**
     * Verifikasi Bearer token. Keluar + 401 jika tidak valid.
     * Payload disimpan di $_REQUEST['__jwt_payload'] untuk controller.
     */
    public static function verify(): array
    {
        $token = JwtService::extractFromHeader();

        if ($token === null) {
            ResponseHelper::unauthorized('Authorization token not provided');
        }

        $jwt = new JwtService();
        $payload = $jwt->validate($token);

        if ($payload === null) {
            ResponseHelper::unauthorized('Invalid or expired token');
        }

        $_REQUEST['__jwt_payload'] = $payload;
        return ['payload' => $payload];
    }
}
