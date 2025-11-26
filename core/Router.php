<?php
/**
 * Simple Router Class
 */

class Router
{
    private $routes = [];
    private $notFoundCallback;

    /**
     * Add a GET route
     */
    public function get($pattern, $callback)
    {
        $this->addRoute('GET', $pattern, $callback);
    }

    /**
     * Add a POST route
     */
    public function post($pattern, $callback)
    {
        $this->addRoute('POST', $pattern, $callback);
    }

    /**
     * Add a PUT route
     */
    public function put($pattern, $callback)
    {
        $this->addRoute('PUT', $pattern, $callback);
    }

    /**
     * Add a DELETE route
     */
    public function delete($pattern, $callback)
    {
        $this->addRoute('DELETE', $pattern, $callback);
    }

    /**
     * Add a route to the routing table
     */
    private function addRoute($method, $pattern, $callback)
    {
        $this->routes[] = [
            'method' => $method,
            'pattern' => $pattern,
            'callback' => $callback
        ];
    }

    /**
     * Set 404 not found callback
     */
    public function notFound($callback)
    {
        $this->notFoundCallback = $callback;
    }

    /**
     * Dispatch the request to the appropriate route
     */
    public function dispatch()
    {
        $requestMethod = $_SERVER['REQUEST_METHOD'];
        $requestUri = $_SERVER['REQUEST_URI'];

        // Remove query string
        if (($pos = strpos($requestUri, '?')) !== false) {
            $requestUri = substr($requestUri, 0, $pos);
        }

        // Remove base path if exists
        $basePath = dirname($_SERVER['SCRIPT_NAME']);
        if ($basePath !== '/' && strpos($requestUri, $basePath) === 0) {
            $requestUri = substr($requestUri, strlen($basePath));
        }

        // Ensure leading slash
        if (empty($requestUri) || $requestUri[0] !== '/') {
            $requestUri = '/' . $requestUri;
        }

        // Try to match a route
        foreach ($this->routes as $route) {
            if ($route['method'] === $requestMethod) {
                $params = $this->matchRoute($route['pattern'], $requestUri);
                
                if ($params !== false) {
                    call_user_func_array($route['callback'], $params);
                    return;
                }
            }
        }

        // No route matched, call 404 handler
        if ($this->notFoundCallback) {
            call_user_func($this->notFoundCallback);
        } else {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'message' => 'Route not found'
            ]);
        }
    }

    /**
     * Match a route pattern against the request URI
     */
    private function matchRoute($pattern, $uri)
    {
        // Convert route pattern to regex
        $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '([^/]+)', $pattern);
        $pattern = '#^' . $pattern . '$#';

        if (preg_match($pattern, $uri, $matches)) {
            // Remove the full match, keep only captured groups
            array_shift($matches);
            return $matches;
        }

        return false;
    }
}



