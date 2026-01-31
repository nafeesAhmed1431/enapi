<?php

namespace NafeesAhmed\Enapi;

class ApiController
{
    protected array $routes = [];
    protected array $middleware = [];

    /**
     * Register a GET route
     */
    public function get(string $path, callable $handler): self
    {
        return $this->registerRoute('GET', $path, $handler);
    }

    /**
     * Register a POST route
     */
    public function post(string $path, callable $handler): self
    {
        return $this->registerRoute('POST', $path, $handler);
    }

    /**
     * Register a PUT route
     */
    public function put(string $path, callable $handler): self
    {
        return $this->registerRoute('PUT', $path, $handler);
    }

    /**
     * Register a DELETE route
     */
    public function delete(string $path, callable $handler): self
    {
        return $this->registerRoute('DELETE', $path, $handler);
    }

    /**
     * Register a route with any HTTP method
     */
    protected function registerRoute(string $method, string $path, callable $handler): self
    {
        $this->routes[] = [
            'method' => $method,
            'path' => $path,
            'handler' => $handler,
        ];

        return $this;
    }

    /**
     * Add middleware
     */
    public function middleware(callable $middleware): self
    {
        $this->middleware[] = $middleware;
        return $this;
    }

    /**
     * Get all registered routes
     */
    public function getRoutes(): array
    {
        return $this->routes;
    }

    /**
     * Handle incoming request
     */
    public function handleRequest(string $method, string $path): mixed
    {
        foreach ($this->routes as $route) {
            if ($route['method'] === $method && $this->pathMatches($route['path'], $path)) {
                return call_user_func($route['handler']);
            }
        }

        return $this->notFound();
    }

    /**
     * Check if route path matches request path
     */
    protected function pathMatches(string $routePath, string $requestPath): bool
    {
        return preg_match($this->pathToRegex($routePath), $requestPath) === 1;
    }

    /**
     * Convert route path to regex
     */
    protected function pathToRegex(string $path): string
    {
        $path = str_replace('/', '\/', $path);
        $path = preg_replace('/\{[^}]+\}/', '([^/]+)', $path);
        return '/^' . $path . '$/';
    }

    /**
     * 404 Not Found response
     */
    protected function notFound(): array
    {
        http_response_code(404);
        return ['error' => 'Not Found'];
    }
}
