<?php
declare(strict_types=1);

namespace EventHub\Services;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\SignatureInvalidException;
use EventHub\Helpers\ResponseHelper;

/**
 * JWT Service — generate, validate, decode token (firebase/php-jwt).
 */
final class JwtService
{
    private string $secret;
    private string $algorithm;
    private int    $expiry;
    private array  $defaultClaims;

    public function __construct()
    {
        $config = require __DIR__ . '/../config/jwt.php';
        $this->secret        = $config['secret'];
        $this->algorithm     = $config['algorithm'];
        $this->expiry        = $config['expiry'];
        $this->defaultClaims = $config['default_claims'];
    }

    /**
     * Generate JWT untuk user.
     */
    public function generate(array $user): string
    {
        $now = time();
        $payload = array_merge($this->defaultClaims, [
            'iat'  => $now,
            'exp'  => $now + $this->expiry,
            'nbf'  => $now,
            'sub'  => (string)$user['id'],
            'data' => [
                'id'    => (int)$user['id'],
                'name'  => $user['name'],
                'email' => $user['email'],
                'role'  => $user['role'],
            ],
        ]);

        return JWT::encode($payload, $this->secret, $this->algorithm);
    }

    /**
     * Decode + validasi JWT. Kembalikan payload array, atau abort 401.
     */
    public function validate(string $token): ?array
    {
        try {
            $decoded = JWT::decode($token, new Key($this->secret, $this->algorithm));
            $payload = (array)$decoded;
            // JWT::decode mengembalikan object; cast `data` menjadi array
            // agar controller bisa akses $payload['data']['id'], dst.
            if (isset($payload['data']) && is_object($payload['data'])) {
                $payload['data'] = (array)$payload['data'];
            }
            return $payload;
        } catch (ExpiredException) {
            ResponseHelper::unauthorized('Token expired');
        } catch (SignatureInvalidException) {
            ResponseHelper::unauthorized('Invalid token signature');
        } catch (\Throwable) {
            ResponseHelper::unauthorized('Invalid token');
        }
        return null;
    }

    /**
     * Extract Bearer token dari header Authorization.
     */
    public static function extractFromHeader(): ?string
    {
        $auth = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        if (preg_match('/^Bearer\s+(.+)$/i', $auth, $matches)) {
            return $matches[1];
        }
        return null;
    }
}
