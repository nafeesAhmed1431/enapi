# Example Usage

This directory contains example implementations of the ENAPI package.

## Quick Start

### 1. Create Your Models

Create model classes that extend `NafeesAhmed\Enapi\Model`:

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

### 2. Set Up Your API Endpoint

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
    __DIR__ . '/Models',  // Models directory
    'App\\Models',         // Models namespace
    $db
);

// Handle request
$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

header('Content-Type: application/json');
echo json_encode($api->handleRequest($method, $path));
```

### 3. Automatic API Endpoints

For each model (User, Product, Post), these endpoints are automatically created:

**Users:**
- `GET /api/users` - List all users
- `GET /api/users/1` - Get user by ID
- `POST /api/users` - Create new user
- `PUT /api/users/1` - Update user
- `DELETE /api/users/1` - Delete user

**Products:**
- `GET /api/products` - List all products
- `GET /api/products/1` - Get product by ID
- `POST /api/products` - Create new product
- `PUT /api/products/1` - Update product
- `DELETE /api/products/1` - Delete product

**Posts:**
- `GET /api/posts` - List all posts
- `GET /api/posts/1` - Get post by ID
- `POST /api/posts` - Create new post
- `PUT /api/posts/1` - Update post
- `DELETE /api/posts/1` - Delete post

## API Request Examples

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
  -d '{"name":"Jane Doe","email":"jane@example.com"}'
```

### Delete User
```bash
curl -X DELETE http://localhost/api/users/1
```

## Database Tables

Create your database tables:

```sql
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE products (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    stock INT DEFAULT 0,
    category_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE posts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    content TEXT,
    author_id INT,
    status ENUM('draft', 'published') DEFAULT 'draft',
    published_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

## Adding More Models

Simply create a new model file in the `Models` directory:

```php
<?php

namespace App\Models;

use NafeesAhmed\Enapi\Model;

class Order extends Model
{
    protected string $table = 'orders';
    protected string $resourceName = 'orders';
    protected array $fillable = ['user_id', 'total', 'status'];
}
```

The API endpoints will be automatically generated!

## Custom Routes

You can still add custom routes:

```php
$api->get('/api/custom', function() {
    return ['message' => 'Custom endpoint'];
});
```
