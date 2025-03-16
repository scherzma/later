<?php

namespace Tests;

/**
 * Class to execute API controllers directly for testing
 */
class ApiExecutor
{
    private $controllerDir;
    
    public function __construct()
    {
        $this->controllerDir = dirname(__DIR__) . '/controllers/';
    }
    
    /**
     * Execute an API request
     * 
     * @param string $method HTTP method (GET, POST, etc)
     * @param string $endpoint API endpoint (e.g., /users/login)
     * @param array $data Request data
     * @param array $headers Request headers
     * @return array Response data
     */
    public function executeRequest($method, $endpoint, $data = [], $headers = [])
    {
        // Reset HttpMock state
        HttpMock::reset();
        
        // Clean the endpoint
        $endpoint = trim($endpoint, '/');
        
        // Set up environment
        $_SERVER['REQUEST_METHOD'] = $method;
        $_SERVER['REQUEST_URI'] = '/' . $endpoint;
        $_SERVER['PATH_INFO'] = '/' . $endpoint;
        
        // Set up headers
        foreach ($headers as $key => $value) {
            $_SERVER['HTTP_' . strtoupper(str_replace('-', '_', $key))] = $value;
        }
        
        // Set up input data
        if ($method === 'POST' || $method === 'PUT') {
            // Set up the raw input
            PhpStreamMock::$mockData = json_encode($data);
        } elseif ($method === 'GET') {
            // Set up query parameters
            $_GET = $data;
            $_REQUEST = $data;
        }
        
        // Start output buffering
        ob_start();
        
        // Parse the URI into parts
        $uriParts = $endpoint ? explode('/', $endpoint) : [];
        
        // Execute the appropriate controller
        $handled = $this->routeRequest($uriParts);
        
        // Get the response content
        $content = ob_get_clean();
        
        // If no route was handled, return 404
        if (!$handled) {
            return [
                'error' => 'Route not found',
                'status_code' => 404
            ];
        }
        
        // Parse the JSON response
        $response = json_decode($content, true);
        
        // If parsing failed, return the raw content
        if ($response === null && $content !== '') {
            $response = ['raw_content' => $content];
        }
        
        // Add status code to the response
        $response['status_code'] = HttpMock::getStatusCode();
        
        return $response;
    }
    
    /**
     * Route a request to the appropriate controller
     * 
     * @param array $uriParts URI segments
     * @param string $currentDir Current directory
     * @param array $params Route parameters
     * @return bool True if a route was handled
     */
    private function routeRequest($uriParts, $currentDir = null, $params = [])
    {
        if ($currentDir === null) {
            $currentDir = $this->controllerDir;
        }
        
        // If no more URI parts, look for index.php
        if (empty($uriParts)) {
            $indexFile = $currentDir . 'index.php';
            if (file_exists($indexFile)) {
                // Set params for the controller
                $_REQUEST['params'] = $params;
                
                // Include the controller
                require $indexFile;
                return true;
            }
            return false;
        }
        
        $segment = array_shift($uriParts);
        
        // Sanitize the segment to prevent directory traversal
        $segment = str_replace(['..', './', '/'], '', $segment);
        
        $staticDir = $currentDir . $segment . '/';
        $staticFile = $currentDir . $segment . '.php';
        
        // Check for static directory (e.g., /tasks/)
        if (is_dir($staticDir)) {
            return $this->routeRequest($uriParts, $staticDir, $params);
        }
        // Check for static file (e.g., /tasks.php)
        elseif (file_exists($staticFile)) {
            // Set params for the controller
            $_REQUEST['params'] = $params;
            
            // Include the controller
            require $staticFile;
            return true;
        }
        // Check for dynamic directories (e.g., _taskId/)
        else {
            $dynamicDirs = glob($currentDir . '_*/', GLOB_ONLYDIR);
            foreach ($dynamicDirs as $dynamicDir) {
                $paramName = substr(basename($dynamicDir), 1); // Remove leading '_'
                
                $newParams = $params;
                $newParams[$paramName] = $segment;
                
                if ($this->routeRequest($uriParts, $dynamicDir, $newParams)) {
                    return true;
                }
            }
        }
        
        return false;
    }
}