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

## Auto-Generated Endpoints

For each model, you get **8 powerful endpoints**:

### Basic CRUD (5 endpoints)
- `GET /api/{resource}` - List all (with search, filters, pagination, sorting)
- `GET /api/{resource}/{id}` - Get single record
- `POST /api/{resource}` - Create record
- `PUT /api/{resource}/{id}` - Update record
- `DELETE /api/{resource}/{id}` - Delete record

### Advanced Features (3 endpoints)
- `GET /api/{resource}/stats` - Statistics & analytics
- `GET /api/{resource}/export` - Export data
- `POST /api/{resource}/batch` - Batch operations

**[See all features →](API-FEATURES.md)**

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

### Users API (Basic CRUD)
- `GET /api/users` - List all users
- `GET /api/users/1` - Get single user
- `POST /api/users` - Create user
- `PUT /api/users/1` - Update user
- `DELETE /api/users/1` - Delete user

### Advanced Features (Auto-generated for all models)
- `GET /api/users/stats` - Get user statistics
- `GET /api/users/export` - Export users
- `POST /api/users/batch` - Batch update/delete users

Same endpoints for **products**, **posts**, and every other model!

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

### Search Users
```bash
curl "http://localhost:8000/api/users?search=john"
```

### Filter Products by Category
```bash
curl "http://localhost:8000/api/products?category_id=5&status=active"
```

### Get Products with Pagination
```bash
curl "http://localhost:8000/api/products?page=1&per_page=20&sort=price&order=asc"
```

### Get Sales Statistics
```bash
curl "http://localhost:8000/api/orders/stats?date_field=created_at&date_from=2026-01-01&aggregate=total"
```

### Batch Delete Products
```bash
curl -X POST http://localhost:8000/api/products/batch \
  -H "Content-Type: application/json" \
  -d '{"action":"delete","ids":[1,2,3]}'
```

### Export Products
```bash
curl "http://localhost:8000/api/products/export?category_id=5"
```

**[See all advanced features →](API-FEATURES.md)**

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
