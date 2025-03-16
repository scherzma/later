<?php

namespace Tests\Utils;

/**
 * Utility functions from the API for testing
 * These are copied from index.php to avoid header issues
 */
class ApiUtils
{
    /**
     * Securely filters any input values to prevent XSS attacks
     *
     * @param mixed $data The data to be sanitized
     * @return mixed The sanitized data
     */
    public static function filterInput($data) {
        if (is_array($data)) {
            $filtered = [];
            foreach ($data as $key => $value) {
                // Sanitize array keys as well
                $filteredKey = self::filterInput($key);
                $filtered[$filteredKey] = self::filterInput($value);
            }
            return $filtered;
        } else {
            // Convert to string and filter
            return htmlspecialchars(trim((string)$data), ENT_QUOTES, 'UTF-8');
        }
    }
    
    /**
     * Sanitizes URI parts to prevent path traversal and other attacks
     *
     * @param array $uriParts Array of URI segments to sanitize
     * @return array Sanitized URI segments
     */
    public static function sanitizeUriParts($uriParts) {
        $sanitized = [];
        foreach ($uriParts as $part) {
            // Prevent path traversal by removing ".." and "."
            $part = str_replace(['..', './'], '', $part);

            // Only allow alphanumeric characters, hyphens, and underscores for URI parts
            $part = preg_replace('/[^a-zA-Z0-9_-]/', '', $part);

            if (!empty($part)) {
                $sanitized[] = $part;
            }
        }
        return $sanitized;
    }
    
    /**
     * Validates and sanitizes dynamic parameter values
     *
     * @param string $paramName The parameter name
     * @param string $paramValue The parameter value to sanitize
     * @return string|int The sanitized parameter value
     */
    public static function sanitizeParam($paramName, $paramValue) {
        // Convert IDs to integers if they end with "Id"
        if (preg_match('/Id$/', $paramName)) {
            return filter_var($paramValue, FILTER_VALIDATE_INT);
        }

        // Special handling for different parameter types
        switch($paramName) {
            case 'email':
                return filter_var($paramValue, FILTER_SANITIZE_EMAIL);
            case 'username':
                // Only allow alphanumeric and some special characters
                return preg_replace('/[^a-zA-Z0-9_.-]/', '', $paramValue);
            default:
                // General string sanitization
                return htmlspecialchars(trim($paramValue), ENT_QUOTES, 'UTF-8');
        }
    }
}