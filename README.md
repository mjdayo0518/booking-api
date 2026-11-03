# Booking API

A clean Laravel API for reserving shared resources (e.g., meeting rooms and equipment), demonstrating production-grade practices around concurrency, asynchronous processing, and automated testing.

## Tech Stack

- **Laravel:** 13
- **PHP:** 8.3
- **Database:** SQLite (development), MySQL/PostgreSQL ready
- **Authentication:** Laravel Sanctum
- **Queue:** Database queue driver with `after_commit` safety

## Quick Start

1. Clone the repository.

2. Copy the environment file:

   ```bash
   cp .env.example .env
   ```

3. Install dependencies:

   ```bash
   composer install
   ```

4. Generate the application key:

   ```bash
   php artisan key:generate
   ```

5. Create the SQLite database:

   ```bash
   touch database/database.sqlite
   ```

6. Run migrations and seeders:

   ```bash
   php artisan migrate --seed
   ```

7. Start the development server:

   ```bash
   php artisan serve
   ```

The API will be available at:

[http://127.0.0.1:8000](http://127.0.0.1:8000)

## Test User

Seed data includes a test user:

| Field | Value |
|---|---|
| Email | `test@example.com` |
| Password | `password` |

### Generate an API Token

Open Laravel Tinker:

```bash
php artisan tinker
```

Generate a Sanctum API token:

```php
$user = App\Models\User::where('email', 'test@example.com')->first();

echo $user->createToken('dev')->plainTextToken;
```

Use the generated token when authenticating API requests.

## Core Features

### 1. Booking Creation with Conflict Detection

Creating a booking requires an authenticated request.

**Endpoint**

```http
POST /api/bookings
```

**Headers**

```http
Authorization: Bearer YOUR_TOKEN
Content-Type: application/json
Accept: application/json
```

**Request Payload**

```json
{
    "resource_id": 1,
    "start_time": "2026-11-10 09:00:00",
    "end_time": "2026-11-10 10:00:00"
}
```

**Double-Booking Prevention**

The booking service uses a database transaction with `lockForUpdate()` to prevent concurrent requests from creating overlapping bookings.

When multiple requests target overlapping time slots, only one booking should succeed. Conflicting requests receive a `409 Conflict` response.

**Overlap Detection**

The implementation uses the standard interval overlap formula:

- `existing.start_time < requested.end_time`
- `existing.end_time > requested.start_time`

This ensures:

- Overlapping bookings are rejected.
- Adjacent bookings are allowed (e.g., 09:00–10:00 and 10:00–11:00).
- Cancelled bookings do not block new bookings.

### 2. Asynchronous Booking Confirmation

After a successful booking, a `SendBookingConfirmation` job is dispatched.

**Development**

For easy debugging, configure the queue driver to run jobs synchronously:

```env
QUEUE_CONNECTION=sync
```

**Production**

Switch to a database or Redis queue driver and run the queue worker:

```bash
php artisan queue:work
```

The `after_commit` configuration ensures jobs are dispatched only after the booking transaction successfully commits.

This prevents jobs from processing bookings that have not yet been committed to the database.

## Testing

Run the automated test suite:

```bash
php artisan test
```

### Feature Test Coverage

The feature tests cover:

- Successful booking creation.
- Rejection of overlapping bookings (`409 Conflict`).
- Allowance of adjacent bookings.
- Cancelled bookings do not block new bookings.
- Confirmation job is dispatched after successful booking creation.

## Architecture Notes

### Service Layer

Booking business logic lives in `App\Services\BookingService` rather than directly in controllers.

This keeps controllers thin, separates business logic from HTTP concerns, and makes the implementation easier to test and maintain.

### Custom Exception

`BookingConflictException` handles booking conflicts and automatically renders a `409 Conflict` response.

Controllers do not need additional try/catch blocks for booking conflicts.

### Enum Status

The `BookingStatus` enum provides type-safe booking status values instead of relying on hardcoded strings.

### Composite Database Index

The bookings table uses a composite index on:

```sql
(resource_id, start_time, end_time)
```

This helps optimize booking conflict queries as the application grows.

### Database Flexibility

SQLite is used during development to provide a zero-configuration setup for reviewers.

The application can switch to MySQL or PostgreSQL through environment configuration without requiring application code changes.

## Future Work

Potential improvements include:

- Resource availability windows (business hours).
- Booking rescheduling and cancellation endpoints.
- Admin views for managing all bookings.
- Rate limiting per user.

## License

This project is licensed under the [MIT License](LICENSE).