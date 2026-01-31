<?php

require __DIR__ . '/../vendor/autoload.php';

use NafeesAhmed\Enapi\ApiController;
use NafeesAhmed\Enapi\Database;

// Database configuration
$dbConfig = [
    'driver' => 'mysql',
    'host' => 'localhost',
    'port' => 3306,
    'database' => 'your_database',
    'username' => 'root',
    'password' => '',
    'charset' => 'utf8mb4'
];

// Create database connection
$db = new Database($dbConfig);

// Initialize API controller with models auto-loading
$api = new ApiController(
    __DIR__ . '/Models',  // Path to models directory
    'App\\Models',         // Models namespace
    $db                    // Database instance
);

// Automatically creates these routes for each model:
// GET    /api/users          - List all users
// GET    /api/users/{id}     - Get single user
// POST   /api/users          - Create user
// PUT    /api/users/{id}     - Update user
// DELETE /api/users/{id}     - Delete user

// Same for products, posts, and any other models in the Models directory

// You can also add custom routes
$api->get('/api/status', function() {
    return [
        'success' => true,
        'message' => 'API is running',
        'timestamp' => date('Y-m-d H:i:s')
    ];
});

// Handle the request
$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

header('Content-Type: application/json');
echo json_encode($api->handleRequest($method, $path));
