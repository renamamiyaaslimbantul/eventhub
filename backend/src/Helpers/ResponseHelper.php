<?php
declare(strict_types=1);

namespace EventHub\Helpers;

/**
 * Helper response JSON konsisten.
 * Format: { success, message, data, errors }
 * Native PHP — tidak pakai framework.
 */
final class ResponseHelper
{
    /**
     * Buat response JSON dan kirim langsung ke output.
     * Controller tinggal panggil method ini lalu return.
     */
    public static function send(bool $success, string $message, mixed $data, ?array $errors, int $httpCode): void
    {
        http_response_code($httpCode);
        echo json_encode(
            ['success' => $success, 'message' => $message, 'data' => $data, 'errors' => $errors],
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );
        exit;
    }

    public static function success(mixed $data = null, string $message = 'OK', int $httpCode = 200): void
    {
        self::send(true, $message, $data, null, $httpCode);
    }

    public static function created(mixed $data = null, string $message = 'Created'): void
    {
        self::send(true, $message, $data, null, 201);
    }

    public static function error(string $message, int $httpCode = 400, ?array $errors = null): void
    {
        self::send(false, $message, null, $errors, $httpCode);
    }

    public static function badRequest(string $message = 'Bad Request', ?array $errors = null): void
    {
        self::error($message, 400, $errors);
    }

    public static function unauthorized(string $message = 'Unauthorized'): void
    {
        self::error($message, 401);
    }

    public static function forbidden(string $message = 'Forbidden'): void
    {
        self::error($message, 403);
    }

    public static function notFound(string $message = 'Not Found'): void
    {
        self::error($message, 404);
    }

    public static function unprocessable(string $message = 'Validation Failed', ?array $errors = null): void
    {
        self::error($message, 422, $errors);
    }

    public static function serverError(string $message = 'Internal Server Error'): void
    {
        self::error($message, 500);
    }
}
