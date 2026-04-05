<?php

namespace Core;

class Router
{
    private array $routes      = [];
    private array $middleware  = [];
    private array $groupStack  = [];

    public function get(string $uri, array|callable|string $action): static
    {
        return $this->addRoute('GET', $uri, $action);
    }

    public function post(string $uri, array|callable|string $action): static
    {
        return $this->addRoute('POST', $uri, $action);
    }

    public function put(string $uri, array|callable|string $action): static
    {
        return $this->addRoute('PUT', $uri, $action);
    }

    public function patch(string $uri, array|callable|string $action): static
    {
        return $this->addRoute('PATCH', $uri, $action);
    }

    public function delete(string $uri, array|callable|string $action): static
    {
        return $this->addRoute('DELETE', $uri, $action);
    }

    public function any(string $uri, array|callable|string $action): static
    {
        foreach (['GET', 'POST', 'PUT', 'PATCH', 'DELETE'] as $method) {
            $this->addRoute($method, $uri, $action);
        }
        return $this;
    }

    public function middleware(string|array $middleware): static
    {
        $key   = array_key_last($this->routes);
        $group = end($this->routes);
        if ($group) {
            $mw = is_array($middleware) ? $middleware : [$middleware];
            $this->routes[$key]['middleware'] = array_merge(
                $this->routes[$key]['middleware'] ?? [],
                $mw
            );
        }
        return $this;
    }

    public function group(array $attributes, callable $callback): void
    {
        $this->groupStack[] = $attributes;
        $callback($this);
        array_pop($this->groupStack);
    }

    private function addRoute(string $method, string $uri, array|callable|string $action): static
    {
        $prefix = '';
        $middleware = [];

        foreach ($this->groupStack as $group) {
            $prefix     .= '/' . trim($group['prefix'] ?? '', '/');
            $middleware  = array_merge($middleware, $group['middleware'] ?? []);
        }

        $uri = '/' . ltrim(rtrim($prefix, '/') . '/' . ltrim($uri, '/'), '/');
        $uri = $uri === '//' ? '/' : $uri;

        $this->routes[] = [
            'method'     => $method,
            'uri'        => $uri,
            'action'     => $action,
            'middleware' => $middleware,
        ];

        return $this;
    }

    public function dispatch(Request $request): void
    {
        $method = $request->method();
        $uri    = $this->normalizeUri($request->uri());

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            $params = $this->match($route['uri'], $uri);
            if ($params === false) {
                continue;
            }

            // Execute middleware
            foreach ($route['middleware'] as $mw) {
                $this->runMiddleware($mw, $request);
            }

            // Execute action
            $this->runAction($route['action'], $request, $params);
            return;
        }

        // 404
        http_response_code(404);
        if (file_exists(VIEW_PATH . '/errors/404.php')) {
            echo View::render('errors/404', [], null);
        } else {
            echo '<h1>404 Not Found</h1>';
        }
    }

    private function match(string $routeUri, string $requestUri): array|false
    {
        $pattern = preg_replace('/\{([a-zA-Z_]+)\}/', '([^/]+)', $routeUri);
        $pattern = '#^' . $pattern . '$#';

        if (!preg_match($pattern, $requestUri, $matches)) {
            return false;
        }

        array_shift($matches);

        preg_match_all('/\{([a-zA-Z_]+)\}/', $routeUri, $keys);
        $params = [];
        foreach ($keys[1] as $i => $key) {
            $params[$key] = $matches[$i] ?? null;
        }

        return $params;
    }

    private function normalizeUri(string $uri): string
    {
        $uri = rtrim($uri, '/') ?: '/';
        return $uri;
    }

    private function runMiddleware(string $class, Request $request): void
    {
        $middlewareMap = [
            'auth'  => \App\Middlewares\AuthMiddleware::class,
            'guest' => \App\Middlewares\GuestMiddleware::class,
        ];

        $fqcn = $middlewareMap[$class] ?? $class;

        if (!class_exists($fqcn)) {
            throw new \RuntimeException("Middleware not found: {$fqcn}");
        }

        (new $fqcn())->handle($request);
    }

    private function runAction(array|callable|string $action, Request $request, array $params): void
    {
        if (is_callable($action)) {
            echo $action($request, $params);
            return;
        }

        if (is_string($action) && str_contains($action, '@')) {
            [$class, $method] = explode('@', $action);
            $action = [$class, $method];
        }

        if (is_array($action)) {
            [$class, $method] = $action;
            if (!str_contains($class, '\\')) {
                $class = 'App\\Controllers\\' . $class;
            }
            if (!class_exists($class)) {
                throw new \RuntimeException("Controller not found: {$class}");
            }
            $controller = new $class();
            if (!method_exists($controller, $method)) {
                throw new \RuntimeException("Method not found: {$class}::{$method}");
            }
            echo $controller->$method($request, ...array_values($params));
            return;
        }

        throw new \RuntimeException("Invalid route action");
    }

    public function getRoutes(): array
    {
        return $this->routes;
    }
}
