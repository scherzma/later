<?php

// Debug flag - set to false in production
$DEBUG = false;

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

function handleRoute($uriParts, $currentDir = __DIR__ . '/controllers/', $params = []) {
    header('Content-Type: application/json');

    debug("Handling route with URI parts: " . implode('/', $uriParts));
    debug("Current directory: " . $currentDir);

    if (empty($uriParts[0])) {
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
    $staticPath = $currentDir . $segment;
    $dynamicPath = $currentDir . '[id]';

    debug("Processing segment: " . $segment);
    debug("Checking static path: " . $staticPath);
    debug("Checking dynamic path: " . $dynamicPath);

    if (is_dir($staticPath)) {
        debug("Found directory, recursing");
        return handleRoute($uriParts, $staticPath . '/', $params);
    }
    elseif (file_exists($staticPath . '.php')) {
        debug("Found static file: " . $staticPath . '.php');
        $_REQUEST['params'] = $params;
        require_once $staticPath . '.php';
        return true;
    }
    elseif (file_exists($dynamicPath . '.php')) {
        debug("Found dynamic route file: " . $dynamicPath . '.php');
        $params['id'] = $segment;
        debug("Setting param id = " . $segment);
        $_REQUEST['params'] = $params;
        require_once $dynamicPath . '.php';
        return true;
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

// Remove 'index.php' if it's in the URI (in case someone accesses it directly)
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