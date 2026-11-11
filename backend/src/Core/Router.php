<?php
declare(strict_types=1);

namespace EventHub\Core;

/**
 * Router native PHP — dukung path param seperti /api/events/{id}.
 */
final class Router
{
    /** @var array<string, array<string, callable>> */
    private array $routes = [];

    public function get(string $path, callable $handler): void    { $this->add('GET', $path, $handler); }
    public function post(string $path, callable $handler): void   { $this->add('POST', $path, $handler); }
    public function put(string $path, callable $handler): void    { $this->add('PUT', $path, $handler); }
    public function patch(string $path, callable $handler): void  { $this->add('PATCH', $path, $handler); }
    public function delete(string $path, callable $handler): void { $this->add('DELETE', $path, $handler); }

    private function add(string $method, string $path, callable $handler): void
    {
        $path = '/' . trim($path, '/');
        $this->routes[$method][$path] = $handler;
    }

    /**
     * Dispatch request ke handler yang cocok.
     */
    public function dispatch(string $method, string $uri): void
    {
        $uri = '/' . trim(parse_url($uri, PHP_URL_PATH) ?? '', '/');

        // Strip base path (mis. /eventhub/backend/public)
        $script = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
        if ($script !== '/' && $script !== '' && str_starts_with($uri, $script)) {
            $uri = '/' . ltrim(substr($uri, strlen($script)), '/');
        }

        foreach ($this->routes[$method] ?? [] as $pattern => $handler) {
            $regex = $this->toRegex($pattern);
            if (preg_match($regex, $uri, $matches)) {
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                $handler(...array_values($params));
                return;
            }
        }

        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'Route not found',
            'data'    => null,
            'errors'  => null,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * /api/events/{id} -> #^/api/events/(?P<id>[^/]+)$#
     */
    private function toRegex(string $pattern): string
    {
        $regex = preg_replace_callback(
            '/\{([a-zA-Z_]+)\}/',
            fn(array $m) => '(?P<' . $m[1] . '>[^/]+)',
            $pattern
        );
        return '#^' . $regex . '$#';
    }
}
