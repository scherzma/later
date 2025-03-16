<?php

namespace Tests;

/**
 * Mock HTTP functions for testing
 */
class HttpMock
{
    private static $statusCode = 200;
    private static $headers = [];
    
    /**
     * Reset all state
     */
    public static function reset()
    {
        self::$statusCode = 200;
        self::$headers = [];
    }
    
    /**
     * Mock for http_response_code
     */
    public static function responseCode($code = null)
    {
        if ($code !== null) {
            self::$statusCode = $code;
        }
        
        return self::$statusCode;
    }
    
    /**
     * Mock for header
     */
    public static function header($header, $replace = true, $statusCode = null)
    {
        if ($statusCode !== null) {
            self::$statusCode = $statusCode;
        }
        
        // Parse header name and value
        $parts = explode(':', $header, 2);
        
        if (count($parts) === 2) {
            $name = trim($parts[0]);
            $value = trim($parts[1]);
            
            if ($replace || !isset(self::$headers[$name])) {
                self::$headers[$name] = $value;
            }
        } else {
            // Handle special headers like "HTTP/1.1 200 OK"
            if (strpos($header, 'HTTP/') === 0) {
                // Extract status code
                preg_match('/HTTP\/[\d.]+\s+(\d+)/', $header, $matches);
                if (isset($matches[1])) {
                    self::$statusCode = (int) $matches[1];
                }
            }
            
            self::$headers[] = $header;
        }
    }
    
    /**
     * Get all headers
     */
    public static function getHeaders()
    {
        return self::$headers;
    }
    
    /**
     * Get status code
     */
    public static function getStatusCode()
    {
        return self::$statusCode;
    }
}