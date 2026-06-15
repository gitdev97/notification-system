# Task Notification System

A full-stack task management application with real-time notifications. Users can create tasks, assign them to team members, update statuses, and receive instant push notifications for every meaningful change — assignments, status transitions, and content edits. The frontend is a single-page app powered by Inertia.js, so there is no separate API client; the browser talks directly to Laravel controllers through Inertia's protocol.

Built with **Laravel 13**, **Inertia.js**, **React + TypeScript**, and **Pusher** for real-time updates.

## Architecture

The backend follows a layered architecture. Controllers are kept thin and delegate to service classes (`TaskService`, `NotificationService`) which hold the business logic. Services interact with the database through repository interfaces (`TaskRepositoryInterface`, `NotificationRepositoryInterface`), bound via a dedicated `RepositoryServiceProvider`. This keeps the codebase testable and swappable — you could replace the Eloquent repositories with an API-backed implementation without touching the service layer.

Domain events (`TaskAssigned`, `TaskStatusChanged`, `TaskUpdated`, `TaskCompleted`) are dispatched by the services and handled by queued listeners that create persisted notifications and broadcast them to the frontend via Pusher. The React UI picks up those broadcasts through Laravel Echo and refreshes the relevant page data automatically.

Validation is handled by dedicated FormRequest classes (`StoreTaskRequest`, `UpdateTaskRequest`, `UpdateTaskStatusRequest`), and all API responses go through API Resource transformers.

## Setup Instructions

### Prerequisites

- PHP 8.4+
- Composer
- Node.js 24+
- SQLite (default) or MySQL

### Installation

```bash
# Unzip the archive and enter the project directory
unzip notification-system.zip
cd notification-system

# Install PHP dependencies
composer install

# Install Node dependencies
npm install --legacy-peer-deps

# Copy environment file and generate app key
cp .env.example .env
php artisan key:generate

# Run migrations and seed sample data
php artisan migrate
php artisan db:seed

# Build frontend assets
npm run build
```

### Pusher Configuration

For real-time notifications, configure Pusher in your `.env`:

```
BROADCAST_CONNECTION=pusher
PUSHER_APP_ID=your-app-id
PUSHER_APP_KEY=your-app-key
PUSHER_APP_SECRET=your-app-secret
PUSHER_APP_CLUSTER=mt1

VITE_PUSHER_APP_KEY="${PUSHER_APP_KEY}"
VITE_PUSHER_APP_CLUSTER="${PUSHER_APP_CLUSTER}"
```

### Running the Application

```bash
# Start the development server
php artisan serve

# In a separate terminal, start Vite
npm run dev

# In a separate terminal, start the queue worker
php artisan queue:work
```

### Seeded Users

| Email              | Password   | Role  |
|--------------------|------------|-------|
| admin@example.com  | password   | Admin |
| john@example.com   | password   | User  |
| jane@example.com   | password   | User  |
| mike@example.com   | password   | User  |

## API Documentation

Full API documentation is available via Swagger UI at [`/api/documentation`](/api/documentation) when the application is running. It covers every endpoint — request/response schemas, parameters, and authentication requirements — so refer to that for the complete reference.

All endpoints require session-based authentication (Laravel Sanctum). Log in through the web form first, then use the same browser session for API calls.

## Testing

```bash
# Run all tests
php artisan test

# Run specific test suites
php artisan test --filter=TaskTest
php artisan test --filter=NotificationTest
```

### Test Coverage

- **TaskTest** (10 tests): Task creation, validation, event dispatching, status updates, dashboard rendering
- **NotificationTest** (7 tests): Notification creation via listeners, listing, marking as read, unread counts, authorization

## Queue Processing

Notifications are processed asynchronously via Laravel's queue system.

```bash
# Process queued jobs
php artisan queue:work
```

## Code Quality

```bash
# Format code with Pint (PSR-12 / Laravel preset)
./vendor/bin/pint

# Check without fixing
./vendor/bin/pint --test
```

## Tech Stack

| Layer      | Technology                                    |
|------------|-----------------------------------------------|
| Backend    | Laravel 13, PHP 8.4                           |
| Frontend   | React 19, TypeScript, Inertia.js, Tailwind 4  |
| Auth       | Laravel Breeze + Sanctum                      |
| Queue      | Database driver (configurable to Redis)        |
| Broadcast  | Pusher + Laravel Echo                         |
| Testing    | PHPUnit, Faker                                |
| Code Style | Laravel Pint (PSR-12 / Laravel preset)        |
| API Docs   | Swagger / OpenAPI (l5-swagger)                |
