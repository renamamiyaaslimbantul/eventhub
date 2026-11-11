<?php
declare(strict_types=1);

namespace EventHub\Middlewares;

use EventHub\Helpers\ResponseHelper;

/**
 * Middleware Role — proteksi endpoint berdasarkan role user.
 * Dipanggil setelah JwtMiddleware.
 */
final class RoleMiddleware
{
    /**
     * @param string[] $allowedRoles
     */
    public static function enforce(array $allowedRoles): void
    {
        $payload = $_REQUEST['__jwt_payload'] ?? null;
        $user    = $payload['data'] ?? null;

        if ($user === null) {
            ResponseHelper::unauthorized('Unauthorized');
        }

        $role = $user['role'] ?? null;
        if (!in_array($role, $allowedRoles, true)) {
            ResponseHelper::forbidden('Access denied: insufficient permissions');
        }
    }
}
