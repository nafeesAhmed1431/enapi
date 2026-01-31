# Advanced API Features

ENAPI now includes powerful analytics, filtering, and batch operations out of the box!

## 🎯 Auto-Generated Endpoints Per Resource

For each model (e.g., `users`, `products`, `posts`), you get:

### Basic CRUD
- `GET /api/{resource}` - List all (with filtering, search, pagination)
- `GET /api/{resource}/{id}` - Get single record
- `POST /api/{resource}` - Create record
- `PUT /api/{resource}/{id}` - Update record
- `DELETE /api/{resource}/{id}` - Delete record

### Advanced Features
- `GET /api/{resource}/stats` - Get statistics and analytics
- `GET /api/{resource}/export` - Export data
- `POST /api/{resource}/batch` - Batch operations (update/delete multiple)

---

## 📊 Statistics & Analytics

### Get Basic Stats

```bash
GET /api/products/stats
```

Response:
```json
{
  "success": true,
  "resource": "products",
  "stats": {
    "total_count": 150
  }
}
```

### Aggregations (Sum, Avg, Min, Max)

```bash
GET /api/products/stats?aggregate=price,stock
```

Response:
```json
{
  "success": true,
  "resource": "products",
  "stats": {
    "total_count": 150,
    "sum_price": 45000.50,
    "avg_price": 300.00,
    "min_price": 10.99,
    "max_price": 1999.99,
    "sum_stock": 5000,
    "avg_stock": 33.33,
    "min_stock": 0,
    "max_stock": 500
  }
}
```

### Group By

```bash
GET /api/products/stats?group_by=category_id
```

Response:
```json
{
  "success": true,
  "resource": "products",
  "stats": {
    "total_count": 150,
    "grouped_by": "category_id",
    "groups": [
      {"category_id": 1, "count": 45},
      {"category_id": 2, "count": 60},
      {"category_id": 3, "count": 45}
    ]
  }
}
```

### Date Range Filtering

```bash
GET /api/orders/stats?date_field=created_at&date_from=2026-01-01&date_to=2026-01-31&aggregate=total
```

Response:
```json
{
  "success": true,
  "resource": "orders",
  "stats": {
    "total_count": 234,
    "sum_total": 125600.75,
    "avg_total": 536.76,
    "min_total": 5.99,
    "max_total": 2500.00
  }
}
```

---

## 🔍 Search & Filtering

### Full-Text Search

Search across all fillable fields:

```bash
GET /api/users?search=john
```

Finds users with "john" in name, email, or any searchable field.

### Filter by Field

```bash
GET /api/products?category_id=5&status=active
```

### Multiple Filters

```bash
GET /api/orders?status=completed&user_id=10&payment_method=credit_card
```

---

## 📄 Pagination

### Basic Pagination

```bash
GET /api/products?page=1&per_page=20
```

Response:
```json
{
  "success": true,
  "data": [...],
  "pagination": {
    "total": 150,
    "per_page": 20,
    "current_page": 1,
    "last_page": 8,
    "from": 1,
    "to": 20
  }
}
```

### Without Pagination

```bash
GET /api/products
```

Returns all records (no `page` parameter).

---

## 🔢 Sorting

### Sort Ascending

```bash
GET /api/products?sort=price&order=asc
```

### Sort Descending

```bash
GET /api/products?sort=created_at&order=desc
```

---

## 📋 Field Selection

Select only specific fields:

```bash
GET /api/users?fields=id,name,email
```

Response:
```json
{
  "success": true,
  "data": [
    {"id": 1, "name": "John", "email": "john@example.com"},
    {"id": 2, "name": "Jane", "email": "jane@example.com"}
  ],
  "count": 2
}
```

---

## 🔄 Batch Operations

### Batch Delete

```bash
POST /api/products/batch
Content-Type: application/json

{
  "action": "delete",
  "ids": [1, 2, 3, 4, 5]
}
```

Response:
```json
{
  "success": true,
  "message": "5 records deleted",
  "count": 5
}
```

### Batch Update

```bash
POST /api/products/batch
Content-Type: application/json

{
  "action": "update",
  "ids": [10, 11, 12],
  "data": {
    "status": "inactive",
    "stock": 0
  }
}
```

Response:
```json
{
  "success": true,
  "message": "3 records updated",
  "count": 3
}
```

---

## 📤 Export Data

### Export as JSON

```bash
GET /api/products/export
```

### Export with Filters

```bash
GET /api/products/export?category_id=5&status=active
```

### Export as CSV (data structure)

```bash
GET /api/products/export?format=csv
```

Returns data in a format ready to be converted to CSV on the client side.

---

## 🎨 Complex Query Examples

### E-commerce Dashboard Stats

```bash
GET /api/orders/stats?date_field=created_at&date_from=2026-01-01&date_to=2026-01-31&aggregate=total&group_by=status
```

### Product Search with Filters

```bash
GET /api/products?search=laptop&category_id=1&sort=price&order=asc&page=1&per_page=10
```

### User Analytics

```bash
GET /api/users/stats?date_field=created_at&date_from=2026-01-01&group_by=status
```

### Top Products by Sales

```bash
GET /api/products?sort=sales_count&order=desc&fields=id,name,sales_count,price&per_page=10
```

---

## 💡 Real-World Use Cases

### 1. Sales Dashboard

```javascript
// Get monthly sales stats
fetch('/api/orders/stats?date_field=created_at&date_from=2026-01-01&date_to=2026-01-31&aggregate=total')
  .then(res => res.json())
  .then(data => {
    console.log('Total Orders:', data.stats.total_count);
    console.log('Total Revenue:', data.stats.sum_total);
    console.log('Average Order:', data.stats.avg_total);
  });
```

### 2. Product Management

```javascript
// Search products and filter by category
fetch('/api/products?search=wireless&category_id=5&sort=price&order=asc&page=1')
  .then(res => res.json())
  .then(data => {
    console.log('Products:', data.data);
    console.log('Total:', data.pagination.total);
  });
```

### 3. Bulk Operations

```javascript
// Delete multiple products
fetch('/api/products/batch', {
  method: 'POST',
  headers: {'Content-Type': 'application/json'},
  body: JSON.stringify({
    action: 'delete',
    ids: [1, 2, 3]
  })
})
.then(res => res.json())
.then(data => console.log(data.message));
```

### 4. Analytics Report

```javascript
// Get sales by status
fetch('/api/orders/stats?group_by=status&aggregate=total')
  .then(res => res.json())
  .then(data => {
    data.stats.groups.forEach(group => {
      console.log(`${group.status}: ${group.count} orders`);
    });
  });
```

---

## 🚀 All Query Parameters

| Parameter | Description | Example |
|-----------|-------------|---------|
| `search` | Full-text search | `?search=john` |
| `{field}` | Filter by field value | `?status=active` |
| `sort` | Sort by field | `?sort=price` |
| `order` | Sort order (asc/desc) | `?order=desc` |
| `page` | Page number | `?page=1` |
| `per_page` | Items per page | `?per_page=20` |
| `fields` | Select specific fields | `?fields=id,name,email` |
| `aggregate` | Aggregate fields | `?aggregate=price,stock` |
| `group_by` | Group results | `?group_by=category_id` |
| `date_field` | Date field for filtering | `?date_field=created_at` |
| `date_from` | Start date | `?date_from=2026-01-01` |
| `date_to` | End date | `?date_to=2026-01-31` |

---

## ✨ Summary

With ENAPI, you get:

✅ **Full-text search** - Search across all fields  
✅ **Advanced filtering** - Filter by any field  
✅ **Pagination** - Built-in pagination support  
✅ **Sorting** - Sort by any field, any direction  
✅ **Field selection** - Get only the fields you need  
✅ **Statistics** - Count, sum, avg, min, max  
✅ **Grouping** - Group by any field  
✅ **Date ranges** - Filter by date ranges  
✅ **Batch operations** - Update/delete multiple records  
✅ **Export** - Export data in JSON/CSV  

All automatically generated for every model! 🎉
