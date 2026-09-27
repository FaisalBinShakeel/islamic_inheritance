<?php

declare(strict_types=1);

namespace App;

/**
 * A small path router.
 *
 * The locale prefix is stripped before matching, so each route is declared
 * once and serves every locale. URLs carry no dates and no .php extension, and
 * a trailing slash is normalised away with a redirect rather than serving two
 * URLs for one page.
 */
final class Router
{
    /** @var list<array{method:string,pattern:string,handler:callable,name:string}> */
    private array $routes = [];

    public function get(string $pattern, callable $handler, string $name = ''): void
    {
        $this->routes[] = ['method' => 'GET', 'pattern' => $pattern, 'handler' => $handler, 'name' => $name];
    }

    public function post(string $pattern, callable $handler, string $name = ''): void
    {
        $this->routes[] = ['method' => 'POST', 'pattern' => $pattern, 'handler' => $handler, 'name' => $name];
    }

    /** @return array{locale:string,path:string} */
    public static function splitLocale(string $path): array
    {
        $path = '/' . trim($path, '/');
        $segments = array_values(array_filter(explode('/', $path), static fn ($s) => $s !== ''));

        if ($segments !== [] && Locale::exists($segments[0]) && $segments[0] !== 'en') {
            $locale = array_shift($segments);

            return ['locale' => $locale, 'path' => '/' . implode('/', $segments)];
        }

        return ['locale' => 'en', 'path' => '/' . implode('/', $segments)];
    }

    public function dispatch(string $method, string $uri): mixed
    {
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';
        $path = rawurldecode($path);

        $split = self::splitLocale($path);
        Locale::setActive($split['locale']);
        $routePath = rtrim($split['path'], '/');
        if ($routePath === '') {
            $routePath = '/';
        }

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }
            $parameters = self::match($route['pattern'], $routePath);
            if ($parameters === null) {
                continue;
            }

            return ($route['handler'])(...array_values($parameters));
        }

        return null;
    }

    /** @return array<string,string>|null */
    private static function match(string $pattern, string $path): ?array
    {
        if ($pattern === $path) {
            return [];
        }
        if (!str_contains($pattern, '{')) {
            return null;
        }

        // Split on the placeholders so the literal parts can be quoted and the
        // placeholders turned into named groups, without quoting one another.
        $parts = preg_split('~\{([a-z_]+)\}~', $pattern, -1, PREG_SPLIT_DELIM_CAPTURE);
        if ($parts === false) {
            return null;
        }

        $regex = '';
        foreach ($parts as $index => $part) {
            $regex .= $index % 2 === 0
                ? preg_quote($part, '~')
                : '(?P<' . $part . '>[^/]+)';
        }

        if (preg_match('~^' . $regex . '$~u', $path, $matches) !== 1) {
            return null;
        }

        return array_filter($matches, static fn ($key) => !is_int($key), ARRAY_FILTER_USE_KEY);
    }
}
