<?php
// Allow requests from the frontend origin
header("Access-Control-Allow-Origin: *");
// Specify allowed HTTP methods
header("Access-Control-Allow-Methods: POST, GET, OPTIONS, PUT, DELETE");
// Allow specific headers used in your requests (e.g., for JSON and JWT)
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Session-ID");

// Handle preflight OPTIONS requests
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    header("HTTP/1.1 200 OK");
    exit();
}

define('APP_ROOT', dirname(__FILE__));
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/inc/bootstrap.php';

// Debug flag - set to false in production
$DEBUG = false;

// Buffer output when debugging
if ($DEBUG) {
    ob_start();
}

function debug($message) {
    global $DEBUG;
    if ($DEBUG) {
        echo "DEBUG: " . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . "\n";
    }
}

/**
 * Securely filters any input values to prevent XSS attacks
 *
 * @param mixed $data The data to be sanitized
 * @return mixed The sanitized data
 */
function filterInput($data) {
    if (is_array($data)) {
        $filtered = [];
        foreach ($data as $key => $value) {
            // Sanitize array keys as well
            $filteredKey = filterInput($key);
            $filtered[$filteredKey] = filterInput($value);
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
function sanitizeUriParts($uriParts) {
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
function sanitizeParam($paramName, $paramValue) {
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

/**
 * Handles routing by processing URI parts recursively.
 *
 * @param array $uriParts Remaining URI segments to process
 * @param string $currentDir Current directory in the filesystem
 * @param array $params Accumulated parameters from dynamic segments
 * @return bool True if a route is handled, false otherwise
 */
function handleRoute($uriParts, $currentDir = __DIR__ . '/controllers/', $params = []) {
    header('Content-Type: application/json');

    debug("Handling route with URI parts: " . implode('/', $uriParts));
    debug("Current directory: " . $currentDir);

    // If no more URI parts, look for index.php
    if (empty($uriParts)) {
        $indexFile = $currentDir . 'index.php';
        debug("Checking for index file: " . $indexFile);
        if (file_exists($indexFile)) {
            debug("Found index file, including it");

            // Sanitize all params before passing to the controller
            $sanitizedParams = [];
            foreach ($params as $key => $value) {
                $sanitizedParams[$key] = sanitizeParam($key, $value);
            }

            $_REQUEST['params'] = $sanitizedParams;
            require_once $indexFile;
            return true;
        }
        debug("No index file found");
        return false;
    }

    $segment = array_shift($uriParts);

    // Sanitize the segment to prevent directory traversal
    $segment = str_replace(['..', './', '/'], '', $segment);

    $staticDir = $currentDir . $segment . '/';
    $staticFile = $currentDir . $segment . '.php';

    debug("Processing segment: " . $segment);
    debug("Checking static directory: " . $staticDir);
    debug("Checking static file: " . $staticFile);

    // Check for static directory (e.g., /tasks/)
    if (is_dir($staticDir)) {
        debug("Found static directory, recursing");
        return handleRoute($uriParts, $staticDir, $params);
    }
    // Check for static file (e.g., /tasks.php)
    elseif (file_exists($staticFile)) {
        debug("Found static file: " . $staticFile);

        // Sanitize all params before passing to the controller
        $sanitizedParams = [];
        foreach ($params as $key => $value) {
            $sanitizedParams[$key] = sanitizeParam($key, $value);
        }

        $_REQUEST['params'] = $sanitizedParams;
        require_once $staticFile;
        return true;
    }
    // Check for dynamic directories (e.g., _taskId/)
    else {
        $dynamicDirs = glob($currentDir . '_*/', GLOB_ONLYDIR);
        debug("Checking for dynamic directories: " . implode(', ', $dynamicDirs));
        foreach ($dynamicDirs as $dynamicDir) {
            $paramName = substr(basename($dynamicDir), 1); // Remove leading '_'
            debug("Found dynamic directory for parameter: " . $paramName);

            // Sanitize parameter value based on parameter name
            $paramValue = sanitizeParam($paramName, $segment);

            $newParams = $params;
            $newParams[$paramName] = $paramValue;

            if (handleRoute($uriParts, $dynamicDir, $newParams)) {
                return true;
            }
        }
        debug("No matching dynamic directory found");
    }

    debug("No matching route found at this level");
    return false;
}

// Security: Sanitize $_GET, $_POST, and $_REQUEST globally
$_GET = filterInput($_GET);
$_POST = filterInput($_POST);
$_REQUEST = filterInput($_REQUEST);

// Get the request URI from server's PATH_INFO or REQUEST_URI
$requestUri = isset($_SERVER['PATH_INFO']) ?
    trim($_SERVER['PATH_INFO'], '/') :
    (isset($_SERVER['REQUEST_URI']) ?
        trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/') :
        ''
    );

// Remove 'index.php' if it's in the URI
$requestUri = str_replace('index.php', '', $requestUri);

// Sanitize the URI segments
$uriParts = $requestUri ? sanitizeUriParts(explode('/', $requestUri)) : [];

debug("REQUEST_URI: " . (isset($_SERVER['REQUEST_URI']) ? htmlspecialchars($_SERVER['REQUEST_URI'], ENT_QUOTES, 'UTF-8') : 'not set'));
debug("PATH_INFO: " . (isset($_SERVER['PATH_INFO']) ? htmlspecialchars($_SERVER['PATH_INFO'], ENT_QUOTES, 'UTF-8') : 'not set'));
debug("Processed URI: " . htmlspecialchars($requestUri, ENT_QUOTES, 'UTF-8'));
debug("URI parts: " . implode(', ', array_map('htmlspecialchars', $uriParts)));

// Handle the route
if (!handleRoute($uriParts)) {
    debug("No route matched after full processing");
    http_response_code(404);
    echo json_encode(['error' => 'Route not found']);
}

// Output debug info
if ($DEBUG) {
    $debugOutput = ob_get_clean();
    echo $debugOutput;

    // Sanitize server variables before displaying them
    $sanitizedServerVars = [];
    foreach ($_SERVER as $key => $value) {
        if (is_string($value)) {
            $sanitizedServerVars[$key] = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
        } else {
            $sanitizedServerVars[$key] = $value;
        }
    }

    echo "\nFull Server vars: " . print_r($sanitizedServerVars, true);
}