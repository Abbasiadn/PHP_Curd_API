<?php

class Router {
    private $routes = [];

    public function get($route, $callback) {
        $this->addRoute('GET', $route, $callback);
    }

    public function post($route, $callback) {
        $this->addRoute('POST', $route, $callback);
    }
     // Add PUT method
     public function put($route, $callback) {
        $this->addRoute('PUT', $route, $callback);
    }

    // Add DELETE method
    public function delete($route, $callback) {
        $this->addRoute('DELETE', $route, $callback);
    }

    private function addRoute($method, $route, $callback) {
        $this->routes[] = ['method' => $method, 'route' => $route, 'callback' => $callback];
    }

    // public function dispatch($method, $uri) {
    //     // Normalize the URI by removing the base path and query string
    //     $basePath = '/test/CURD/';
    //     $uri = parse_url($uri, PHP_URL_PATH);
        
    //     if (strpos($uri, $basePath) === 0) {
    //         $uri = substr($uri, strlen($basePath));
            
    //     }

    //     foreach ($this->routes as $route) {
    //             var_dump($route['route']  , $uri); die();
    //             var_dump(preg_match($this->convertToRegex($route['route']),$uri,$params)); die();
    //         if ($method === $route['method'] && preg_match($this->convertToRegex($route['route']), $uri, $params)) {
    //             array_shift($params); // Remove the full match
              
    //             return call_user_func_array($route['callback'], [$params, $this->getQueryParams()]);
    //         }
    //     }

    //     // If no route matches
    //     http_response_code(404);
    //     echo json_encode(['status' => 'error', 'message' => 'Route not found']);
    // }

    public function dispatch($method, $uri) {
    // Normalize the URI by removing the base path and query string
    $basePath = '/test/CURD/';
    $uri = parse_url($uri, PHP_URL_PATH);
        
    // Strip the base path from the URI
    if (strpos($uri, $basePath) === 0) {
        $uri = substr($uri, strlen($basePath));
        // echo "og uri ",$uri;
    }

    // Normalize both route and URI by removing leading and trailing slashes
    $uri = rtrim($uri, '/');  // Remove trailing slash if any
    // echo "Normalized URI: " . $uri . "<br>";  // Debugging: Check normalized URI

    foreach ($this->routes as $route) {
        // Normalize route by removing leading and trailing slashes
        $routePattern = rtrim(ltrim($route['route'], '/'), '/');
        // echo "Normalized Route: " . $routePattern . "<br>";  // Debugging: Check normalized route

        // Convert route to regex and debug it
        $regex = $this->convertToRegex($routePattern);
        // echo "Generated Regex: " . $regex . "<br>";  // Debugging: Check regex

        // Test if URI matches the route regex
        $match = preg_match($regex, $uri, $params);
        // echo "preg_match result: " . $match . "<br>";  // Debugging: Check match result

        if ($method === $route['method'] && $match) {
            array_shift($params); // Remove the full match
            return call_user_func_array($route['callback'], [$params, $this->getQueryParams()]);
        }
    }

    // If no route matches
    http_response_code(404);
    echo json_encode(['status' => 'error', 'message' => 'Route not found']);
}


    private function convertToRegex($route) {
        // Convert route path to regex
        $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '([^/]+)', $route);
        return "#^" . rtrim($pattern, '/') . "$#";
    }

    private function getQueryParams() {
        // Extract query parameters from the current request URI
        $queryParams = [];
        parse_str($_SERVER['QUERY_STRING'] ?? '', $queryParams);
        return $queryParams;
    }
}
