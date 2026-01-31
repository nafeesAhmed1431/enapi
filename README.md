# ENAPI - Dynamic API Package

A lightweight, flexible PHP package for building dynamic REST APIs with automatic CRUD generation and Composer integration.

## Features

- 🚀 **Auto-generate APIs** - Automatically creates CRUD endpoints for all your models
- 🔄 **RESTful HTTP methods** - GET, POST, PUT, DELETE support
- 🗄️ **Database abstraction** - Built-in PDO wrapper with query builder
- 📦 **Model-based architecture** - Simple ORM-like base model class
- 🛡️ **Middleware support** - Add custom middleware to routes
- 🔌 **Easy to extend** - Override any method or add custom routes
- ⚡ **Zero configuration** - Just point to your models directory and go!

## Installation

Add this to your `composer.json` or require it directly:

```bash
composer require nafeesahmed1431/enapi
```

## Quick Start

### 1. Create Your Models

```php
<?php

namespace App\Models;

use NafeesAhmed\Enapi\Model;

class User extends Model
{
    protected string $table = 'users';
    protected string $resourceName = 'users';
    protected array $fillable = ['name', 'email', 'password'];
}
```

### 2. Set Up Your API

```php
<?php

require 'vendor/autoload.php';

use NafeesAhmed\Enapi\ApiController;
use NafeesAhmed\Enapi\Database;

// Configure database
$db = new Database([
    'driver' => 'mysql',
    'host' => 'localhost',
    'database' => 'mydb',
    'username' => 'root',
    'password' => ''
]);

// Initialize API with auto-loading
$api = new ApiController(
    __DIR__ . '/Models',  // Path to models
    'App\\Models',         // Models namespace
    $db
);

// Handle request
$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

header('Content-Type: application/json');
echo json_encode($api->handleRequest($method, $path));
```

### 3. That's It!

Your API is ready with these auto-generated endpoints:

- `GET /api/users` - List all users
- `GET /api/users/{id}` - Get single user
- `POST /api/users` - Create user
- `PUT /api/users/{id}` - Update user
- `DELETE /api/users/{id}` - Delete user

**Same pattern applies to ALL your models automatically!**

## API Endpoints

For each model in your `Models` directory, the following endpoints are automatically created:

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/{resource}` | List all records |
| GET | `/api/{resource}/{id}` | Get single record |
| POST | `/api/{resource}` | Create new record |
| PUT | `/api/{resource}/{id}` | Update record |
| DELETE | `/api/{resource}/{id}` | Delete record |

## Example Requests

### Create a User
```bash
curl -X POST http://localhost/api/users \
  -H "Content-Type: application/json" \
  -d '{"name":"John Doe","email":"john@example.com","password":"secret"}'
```

### Get All Users
```bash
curl http://localhost/api/users
```

### Get Single User
```bash
curl http://localhost/api/users/1
```

### Update User
```bash
curl -X PUT http://localhost/api/users/1 \
  -H "Content-Type: application/json" \
  -d '{"name":"Jane Doe"}'
```

### Delete User
```bash
curl -X DELETE http://localhost/api/users/1
```

## Advanced Usage

### Custom Routes

Add custom routes alongside auto-generated ones:

```php
$api->get('/api/status', function() {
    return ['status' => 'running'];
});

$api->get('/api/users/search', function() {
    // Custom search logic
    return ['results' => []];
});
```

### Middleware

```php
$api->middleware(function() {
    // Add your middleware logic
    // Authentication, logging, etc.
});
```

### Model Methods

```php
// In your custom controllers or routes
$user = new User($db);

// Get all records
$users = $user->all();

// Find by ID
$user = $user->find(1);

// Create
$id = $user->create(['name' => 'John', 'email' => 'john@example.com']);

// Update
$user->update(1, ['name' => 'Jane']);

// Delete
$user->delete(1);

// Custom queries
$users = $user->where('status', 'active');
$users = $user->query("SELECT * FROM users WHERE age > ?", [18]);
```

## Database Tables Example

```sql
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE products (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    stock INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
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
