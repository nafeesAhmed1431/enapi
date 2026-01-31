# Changelog

All notable changes to this project will be documented in this file.

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
