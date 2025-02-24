<?php

define('APP_ROOT', dirname(__FILE__));

// Debug flag - set to false in production
$DEBUG = true;

// Buffer output when debugging
if ($DEBUG) {
    ob_start();
}

function debug($message) {
    global $DEBUG;
    if ($DEBUG) {
        echo "DEBUG: " . $message . "\n";
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
            $_REQUEST['params'] = $params;
            require_once $indexFile;
            return true;
        }
        debug("No index file found");
        return false;
    }

    $segment = array_shift($uriParts);
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
        $_REQUEST['params'] = $params;
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
            $newParams = $params;
            $newParams[$paramName] = $segment;
            if (handleRoute($uriParts, $dynamicDir, $newParams)) {
                return true;
            }
        }
        debug("No matching dynamic directory found");
    }

    debug("No matching route found at this level");
    return false;
}

// Get the request URI from server's PATH_INFO or REQUEST_URI
$requestUri = isset($_SERVER['PATH_INFO']) ?
    trim($_SERVER['PATH_INFO'], '/') :
    (isset($_SERVER['REQUEST_URI']) ?
        trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/') :
        ''
    );

// Remove 'index.php' if it's in the URI
$requestUri = str_replace('index.php', '', $requestUri);
$uriParts = $requestUri ? explode('/', $requestUri) : [];

debug("REQUEST_URI: " . ($_SERVER['REQUEST_URI'] ?? 'not set'));
debug("PATH_INFO: " . ($_SERVER['PATH_INFO'] ?? 'not set'));
debug("Processed URI: " . $requestUri);
debug("URI parts: " . implode(', ', $uriParts));

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
    echo "\nFull Server vars: " . print_r($_SERVER, true);
}