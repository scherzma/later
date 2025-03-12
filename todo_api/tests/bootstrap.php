<?php

// This is the bootstrap file for PHPUnit tests

// Composer autoloader
require_once __DIR__ . '/../vendor/autoload.php';

// Load mock classes
require_once __DIR__ . '/Mocks/MockDatabase.php';

// Define constants used in the application
if (!defined('DB_HOST')) {
    define('DB_HOST', 'localhost');
    define('DB_USER', 'test_user');
    define('DB_PASSWORD', 'test_password');
    define('DB_NAME', 'todo_db_test');
}

// Instead of redefining Todo_DB, we'll use the MockDatabase class directly in the tests
// This avoids conflicts when the real Todo_DB is loaded