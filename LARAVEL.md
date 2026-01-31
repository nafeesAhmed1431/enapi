# Laravel Integration

Use ENAPI with your existing Laravel application and Eloquent models!

## Installation

```bash
composer require nafeesahmed1431/enapi
```

## Setup

### 1. Register the Service Provider

Add to `config/app.php`:

```php
'providers' => [
    // ...
    NafeesAhmed\Enapi\Laravel\LaravelServiceProvider::class,
],
```

**For Laravel 11+**, it auto-discovers the service provider.

### 2. Publish Configuration

```bash
php artisan vendor:publish --tag=enapi-config
```

This creates `config/enapi.php` where you can configure:
- Models path
- API prefix
- Middleware
- Excluded models

### 3. That's It!

Your existing Laravel models now have full CRUD API endpoints automatically!

## Example

If you have these Laravel models:

```php
// app/Models/User.php
class User extends Model {
    protected $fillable = ['name', 'email', 'password'];
}

// app/Models/Product.php
class Product extends Model {
    protected $fillable = ['name', 'price', 'description'];
}

// app/Models/Post.php
class Post extends Model {
    protected $fillable = ['title', 'content', 'user_id'];
}
```

You automatically get these endpoints:

### Users API
- `GET /api/users` - List all users
- `GET /api/users/1` - Get single user
- `POST /api/users` - Create user
- `PUT /api/users/1` - Update user
- `DELETE /api/users/1` - Delete user

### Products API
- `GET /api/products` - List all products
- `GET /api/products/1` - Get single product
- `POST /api/products` - Create product
- `PUT /api/products/1` - Update product
- `DELETE /api/products/1` - Delete product

### Posts API
- `GET /api/posts` - List all posts
- `GET /api/posts/1` - Get single post
- `POST /api/posts` - Create post
- `PUT /api/posts/1` - Update post
- `DELETE /api/posts/1` - Delete post

## Configuration

### Exclude Models

In `config/enapi.php`:

```php
'excluded_models' => [
    'User',  // Don't auto-generate API for User model
],
```

### Change API Prefix

```php
'api_prefix' => 'v1',  // Changes routes to /v1/users, etc.
```

### Add Middleware

```php
'middleware' => ['api', 'auth:sanctum'],  // Add authentication
```

### Manual Route Registration

Set `auto_register_routes` to `false` in config, then in `routes/api.php`:

```php
use NafeesAhmed\Enapi\Laravel\LaravelApiController;

$api = app(LaravelApiController::class);
$api->registerModels(app_path('Models'), 'App\\Models');

Route::prefix('api')->group(function () use ($api) {
    Route::get('products', fn() => $api->index('products'));
    Route::get('products/{id}', fn($id) => $api->show('products', $id));
    Route::post('products', fn() => $api->store(request(), 'products'));
    Route::put('products/{id}', fn($id) => $api->update(request(), 'products', $id));
    Route::delete('products/{id}', fn($id) => $api->destroy('products', $id));
});
```

## Testing API Endpoints

### Create a Product
```bash
curl -X POST http://localhost:8000/api/products \
  -H "Content-Type: application/json" \
  -d '{"name":"Laptop","price":999.99,"description":"Gaming laptop"}'
```

### Get All Products
```bash
curl http://localhost:8000/api/products
```

### Get Single Product
```bash
curl http://localhost:8000/api/products/1
```

### Update Product
```bash
curl -X PUT http://localhost:8000/api/products/1 \
  -H "Content-Type: application/json" \
  -d '{"price":899.99}'
```

### Delete Product
```bash
curl -X DELETE http://localhost:8000/api/products/1
```

## Response Format

### Success Response
```json
{
    "success": true,
    "data": {
        "id": 1,
        "name": "Laptop",
        "price": 999.99,
        "created_at": "2026-01-31T10:00:00.000000Z",
        "updated_at": "2026-01-31T10:00:00.000000Z"
    }
}
```

### List Response
```json
{
    "success": true,
    "data": [...],
    "count": 10
}
```

### Error Response
```json
{
    "success": false,
    "error": "Record not found"
}
```

## Advanced Usage

### Custom API Logic

Create your own controller extending Laravel's Controller:

```php
use NafeesAhmed\Enapi\Laravel\LaravelApiController;

class CustomProductController extends Controller
{
    protected $api;

    public function __construct(LaravelApiController $api)
    {
        $this->api = $api;
        $this->api->registerModels(app_path('Models'), 'App\\Models');
    }

    public function index()
    {
        // Use the generated API
        return $this->api->index('products');
    }

    public function featured()
    {
        // Custom endpoint
        $products = Product::where('featured', true)->get();
        return response()->json(['data' => $products]);
    }
}
```

## Requirements

- Laravel 8.x, 9.x, 10.x, or 11.x
- PHP 7.4 or higher
- Eloquent ORM

## Notes

- Respects your model's `$fillable` property
- Uses Laravel's built-in validation (you can add validation in middleware)
- Works with all Eloquent features (relationships, scopes, etc.)
- Automatically uses Laravel's JSON responses
- Integrates with Laravel's middleware and authentication
