# E-Commerce Management REST API

Laravel REST API backend for an e-commerce management system. The API includes Sanctum authentication, product and category management, order placement with stock deduction, dashboard statistics, seed data, and live API documentation through `dedoc/scramble`.

## Tech Stack

- PHP 8.2+
- Laravel 12
- Laravel Sanctum
- dedoc/scramble
- MySQL or SQLite

## Setup

Install dependencies:

```bash
composer install
```

Create the environment file:

```bash
copy .env.example .env
php artisan key:generate
```

Configure your database in `.env`.

For MySQL:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=ecomm
DB_USERNAME=root
DB_PASSWORD=
```

For SQLite:

```env
DB_CONNECTION=sqlite
DB_DATABASE=database/database.sqlite
```

Create the SQLite file if you use SQLite:

```bash
type nul > database\database.sqlite
```

Run migrations and seeders:

```bash
php artisan migrate --seed
```

Create the public storage link for uploaded product images:

```bash
php artisan storage:link
```

Start the API server:

```bash
php artisan serve
```

The API will run at:

```text
http://127.0.0.1:8000
```

## Seeded Users

```text
Admin
Email: admin@example.com
Password: password123

Customer
Email: test@example.com
Password: password123
```

## API Documentation

This project uses `dedoc/scramble`, so no separate Postman collection is required.

Open the generated API docs after starting the server:

```text
http://127.0.0.1:8000/docs/api
```

OpenAPI JSON is available at:

```text
http://127.0.0.1:8000/docs/api.json
```

Use the login/register endpoints to get a Bearer token, then authorize requests in Scramble with:

```text
Bearer YOUR_TOKEN
```

## Main Endpoints

Authentication:

```text
POST /api/register
POST /api/login
POST /api/logout
GET  /api/user
```

Products:

```text
GET    /api/products
POST   /api/products
GET    /api/products/{product}
PUT    /api/products/{product}
DELETE /api/products/{product}
```

Categories:

```text
GET    /api/categories
POST   /api/categories
GET    /api/categories/{category}
PUT    /api/categories/{category}
DELETE /api/categories/{category}
```

Orders:

```text
POST /api/orders
GET  /api/orders
GET  /api/orders/{order}
POST /api/orders/{order}/cancel
```

Dashboard:

```text
GET /api/dashboard
```

## Product Search And Filters

Product listing supports search, filtering, sorting, and pagination:

```text
GET /api/products?search=iphone&category=smartphones&min_price=10000&max_price=50000&sort=price_asc&page=1&per_page=15
```

Supported query parameters:

- `search`
- `category`: category slug or name
- `min_price`
- `max_price`
- `status`
- `in_stock`
- `low_stock`
- `sort`: `latest`, `oldest`, `price_asc`, `price_desc`, `name_asc`, `name_desc`
- `page`
- `per_page`

## Image Upload

Create or update products with `multipart/form-data`.

Use the `images[]` field for multiple uploads. Accepted types are `jpeg`, `png`, `jpg`, and `gif`, with a maximum size of 2 MB per image.

## Standard API Response

Success responses follow this structure:

```json
{
  "success": true,
  "message": "Product created successfully",
  "data": {}
}
```

Paginated responses also include `meta` and `links`.

Error responses follow this structure:

```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {}
}
```

## Testing

Run the test suite:

```bash
php artisan test
```

Run Laravel Pint formatting:

```bash
vendor\bin\pint
```
