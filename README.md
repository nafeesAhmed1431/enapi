# ENAPI - Dynamic API Package

A lightweight, flexible PHP package for building dynamic REST APIs with Composer integration.

## Features

- 🚀 Simple route registration
- 🔄 RESTful HTTP methods (GET, POST, PUT, DELETE)
- 🛡️ Middleware support
- 📦 Composer integration
- 🔌 Easy to extend

## Installation

Add this to your `composer.json` or require it directly:

```bash
composer require nafeesahmed1431/enapi
```

## Quick Start

```php
<?php

require 'vendor/autoload.php';

use NafeesAhmed\Enapi\ApiController;

$api = new ApiController();

// Define routes
$api->get('/users', function() {
    return ['users' => [/* your data */]];
});

$api->post('/users', function() {
    return ['message' => 'User created'];
});

$api->get('/users/{id}', function() {
    return ['user_id' => $_GET['id'] ?? null];
});

// Handle request
$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

header('Content-Type: application/json');
echo json_encode($api->handleRequest($method, $path));
```

## Usage

### Basic Routes

```php
$api->get('/api/endpoint', function() {
    return ['data' => 'response'];
});

$api->post('/api/endpoint', function() {
    return ['status' => 'created'];
});

$api->put('/api/endpoint/{id}', function() {
    return ['status' => 'updated'];
});

$api->delete('/api/endpoint/{id}', function() {
    return ['status' => 'deleted'];
});
```

### Middleware

```php
$api->middleware(function() {
    // Add your middleware logic
});
```

## Testing

```bash
composer test
```

## Requirements

- PHP 7.4 or higher
- Composer

## License

MIT License - see LICENSE file for details

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## Support

For issues and questions, visit: https://github.com/nafeesAhmed1431/enapi/issues
