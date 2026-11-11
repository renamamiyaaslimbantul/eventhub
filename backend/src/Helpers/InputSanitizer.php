<?php
declare(strict_types=1);

namespace EventHub\Helpers;

/**
 * Helper sanitasi input — cegah XSS sebelum simpan/serve.
 * Dipanggil di setiap controller sebelum insert/update.
 */
final class InputSanitizer
{
    /**
     * Sanitasi string tunggal.
     */
    public static function sanitizeString(?string $value): string
    {
        if ($value === null) return '';
        $value = trim($value);
        $value = strip_tags($value);
        return htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    /**
     * Sanitasi email.
     */
    public static function sanitizeEmail(?string $value): string
    {
        if ($value === null) return '';
        $value = trim($value);
        $value = strip_tags($value);
        return filter_var($value, FILTER_SANITIZE_EMAIL);
    }

    /**
     * Sanitasi URL.
     */
    public static function sanitizeUrl(?string $value): ?string
    {
        if ($value === null || $value === '') return null;
        $value = trim($value);
        $value = strip_tags($value);
        $filtered = filter_var($value, FILTER_SANITIZE_URL);
        return filter_var($filtered, FILTER_VALIDATE_URL) ? $filtered : null;
    }

}
