# Changelog

All notable changes to this project will be documented in this file.

## [1.3.0] - 2026-01-31

### Added - Advanced API Features 🚀
- **Statistics & Analytics API** - Automatic stats endpoints for all resources
  - Count, sum, avg, min, max aggregations
  - Group by any field
  - Date range filtering
- **Advanced Filtering** - Built-in search and filter capabilities
  - Full-text search across all fillable fields
  - Filter by any field value
  - Multiple simultaneous filters
- **Pagination** - Automatic pagination support
  - Configurable items per page
  - Full pagination metadata
- **Sorting** - Sort by any field, ascending or descending
- **Field Selection** - Select only specific fields to return
- **Batch Operations** - Update or delete multiple records at once
  - Batch delete by IDs
  - Batch update by IDs
- **Export API** - Export data endpoints
  - JSON export with filters
  - CSV-ready data structure
- Complete API features documentation (API-FEATURES.md)

### Changed
- Updated `LaravelApiController::index()` to support all new query features
- Routes now include `/stats`, `/export`, and `/batch` endpoints
- Enhanced documentation with real-world examples

## [1.2.0] - 2026-01-31

### Added
- **Laravel Integration** - Full support for Laravel/Eloquent models
- `LaravelServiceProvider` for automatic service registration
- `LaravelApiController` for Eloquent model handling
- Auto-discovery support for Laravel 5.5+
- Configuration file for Laravel integration
- Laravel-specific documentation (LARAVEL.md)
- Respects Laravel's `$fillable` property
- Works with Laravel's middleware and authentication

### Changed
- Updated README with Laravel integration information
- Added Laravel and Eloquent keywords to composer.json
- Added illuminate/support and illuminate/database as suggested dependencies

## [1.0.0] - 2026-01-31

### Added
- Initial release
- Basic API controller with route registration
- Support for GET, POST, PUT, DELETE HTTP methods
- **Auto-generate CRUD APIs** for all models
- **Database abstraction** with PDO wrapper
- **Base Model class** with ORM-like functionality
- **Automatic model loading** from directory
- Dynamic route parameter extraction
- Middleware support
- Comprehensive documentation and examples
- Example models (User, Product, Post)

### Features
- Automatically creates 5 endpoints per model (list, get, create, update, delete)
- Fillable fields protection
- Transaction support in Database class
- Query builder methods in Model class
- JSON request/response handling
- Error responses with proper HTTP status codes

### Security
- MIT License

[1.0.0]: https://github.com/nafeesAhmed1431/enapi/releases/tag/v1.0.0
