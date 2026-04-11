# Time Cube Dashboard

A time tracking management system with physical IoT device integration. Track your work time by rotating a physical ESP32-powered cube, with each face mapped to different tasks.

## Features

### Core Functionality
- **Project & Task Management** - Organize work into projects and tasks
- **Time Tracking** - Start/stop time entries manually or via physical cube
- **Physical Cube Integration** - ESP32 device that automatically starts time tracking when rotated
- **User Profiles** - Avatar uploads and profile management
- **API Token Management** - Secure authentication for IoT devices
- **Dashboard Analytics** - Overview of time entries and active tasks

### Physical Time Cube
The system integrates with an ESP32-based physical cube device. Each face of the cube can be mapped to a specific task. When you rotate the cube to a face, it automatically:
1. Stops any currently running time entry
2. Starts a new time entry for the task mapped to that face color
3. Sends data via REST API using X-Time-Cube-Token header token authentication

## Tech Stack

- **Backend**: PHP 8.3 (custom MVC framework with middleware)
- **Database**: SQLite with Doctrine DBAL
- **Database Migrations**: Doctrine Migrations
- **Code Quality**: PHP_CodeSniffer (PSR-12)
- **Templating**: Twig 3.x
- **HTTP Foundation**: Symfony HTTP Foundation (Request/Response/Session)
- **Caching**: Symfony Cache (FilesystemAdapter)
- **Frontend**: Vanilla JavaScript, CSS (no build tools)
- **Containerization**: Docker & Docker Compose (Laravel Sail-style user mapping)
- **Web Server**: Apache with mod_rewrite
- **UI Design**: Based on [Clean Board](https://github.com/tuxonice/clean-board)

## Architecture

### Middleware System

The application uses a flexible middleware pipeline for request handling. Middleware can short-circuit requests (e.g., redirect unauthenticated users) or pass data to controllers.

**Available Middleware:**
- `AuthMiddleware` - Requires user to be logged in (session-based)
- `GuestMiddleware` - Requires user to NOT be logged in (redirects authenticated users)
- `ApiTokenMiddleware` - Requires valid API token via `X-Time-Cube-Token` header
- `CorsMiddleware` - Adds CORS headers to responses (for API endpoints)
- `GeoIpMiddleware` - Restricts access based on visitor's country (uses ip-api.com)

**How it works:**
```php
// In src/Core/App.php - Global middleware (runs on ALL routes)
$router->addGlobalMiddleware(\App\Core\Middleware\CorsMiddleware::class);

// In routes.php - Route-specific middleware
$router->get('/profile', 'ProfileController', 'show', [AuthMiddleware::class]);
$router->post('/api/cube', 'ApiController', 'cube', [ApiTokenMiddleware::class]);
```

**Middleware execution flow:**
1. Request arrives at Router
2. Router matches route and builds middleware stack
3. Global middleware executes first (if any)
4. Route-specific middleware executes second
5. Middleware can return Response (short-circuit) or null (continue)
6. If all middleware pass, controller action executes
7. Response returned to client

**Global middleware:**
Middleware that runs on every single route without needing to specify it in each route definition.

```php
// In src/Core/App.php
$router->addGlobalMiddleware(\App\Core\Middleware\CorsMiddleware::class);
$router->addGlobalMiddleware(\App\Core\Middleware\LoggingMiddleware::class);

// Now ALL routes will have CORS and Logging middleware
```

**Use cases for global middleware:**
- CORS headers for API endpoints
- Request/response logging
- Security headers
- Rate limiting
- Request ID generation
- Geographic access control

**Example: Geographic access control**
```php
// In .env file
ALLOWED_COUNTRIES=US,GB,DE,FR,ES

// In src/Core/App.php
$router->addGlobalMiddleware(\App\Core\Middleware\GeoIpMiddleware::class);

// Now only visitors from US, GB, DE, FR, ES can access the site
// Uses ip-api.com to determine country from IP address
// Caches results for 1 hour using Symfony Cache (FilesystemAdapter)
// Automatically allows localhost/private IPs for development
```

**Creating custom middleware:**
```php
namespace App\Core\Middleware;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class CustomMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, callable $next): ?Response
    {
        // Check condition
        if (!$someCondition) {
            return new Response('Forbidden', 403); // Short-circuit
        }
        
        // Add data to request for controller
        $request->attributes->set('custom_data', $value);
        
        return null; // Continue to next middleware
    }
}

### Custom MVC Framework
All core components are hand-rolled in `src/Core/`:
- **Router** - Pattern-based routing with parameter extraction using Symfony Request/Response
- **Database** - Doctrine DBAL wrapper with SQLite, providing query builder and schema management
- **Auth** - Session-based authentication with API token support
- **Controller** - Base controller with Request/Response handling, auth helpers, and Twig rendering

### Request Flow
```
public/index.php → App::run() → Request::createFromGlobals() → Router::dispatch() → Controller → Response::send()
```

All controllers return Symfony Response objects (Response, RedirectResponse, or JsonResponse), providing a clean abstraction over raw PHP output.

### Data Model
```
users
  ├── projects
  │     └── tasks
  │           └── time_entries
  ├── api_tokens
  └── cubes
        └── cube_face_mappings → tasks
```

## Quick Start

### Prerequisites
- Docker & Docker Compose
- Make (optional, for convenience commands)

### Installation

1. **Clone the repository**
```bash
git clone <repository-url>
cd time-cube-dashboard
```

2. **Configure user permissions (recommended)**
```bash
./setup-user.sh
```
This creates a `.env` file with your host user ID/group ID to avoid Docker permission issues.

3. **Start the application**
```bash
make up
# or
docker compose up -d --build
```

4. **Access the application**
Open your browser to `http://localhost:8000`

5. **Create an account**
Register a new user account through the web interface

### Development Commands

```bash
make up        # Build and start Docker containers (first run or after Dockerfile changes)
make start     # Start existing containers
make stop      # Stop containers
make cli       # Open bash shell inside the app container
make phpcs     # Check code style with PHP_CodeSniffer
make phpcbf    # Fix code style issues automatically
make test-code # Run all code quality checks
```

### Installing Dependencies

```bash
docker compose exec app composer install
```

### Database Migrations

The project uses Doctrine Migrations for database schema management. Migrations run automatically on application startup, but you can also manage them manually.

**Check migration status:**
```bash
docker compose exec app php bin/migrations status
```

**Run pending migrations:**
```bash
docker compose exec app php bin/migrations migrate
```

**Generate a new migration:**
```bash
docker compose exec app php bin/migrations generate
```

**List all migrations:**
```bash
docker compose exec app php bin/migrations list
```

**Migration files location:** `database/migrations/`

The database schema is version-controlled through migrations, eliminating the need for manual SQL execution.

### Code Quality (PHPCS)

The project uses PHP_CodeSniffer to enforce PSR-12 coding standards and maintain code quality.

**Check code style:**
```bash
docker compose exec app php vendor/bin/phpcs
```

**Automatically fix code style issues:**
```bash
docker compose exec app php vendor/bin/phpcbf
```

**Check specific file or directory:**
```bash
docker compose exec app php vendor/bin/phpcs src/Controllers/
```

**Configuration:** `phpcs.xml` - Configured for PSR-12 with custom rules for line length and migration files.

### Docker User Mapping

To avoid permission issues with files created inside Docker containers, the project maps the container user to your host user.

**Automatic setup:**
```bash
./setup-user.sh
```

**Manual setup:**
```bash
# Get your user and group IDs
id -u  # USER_ID
id -g  # GROUP_ID

# Create .env file
echo "USER_ID=$(id -u)" > .env
echo "GROUP_ID=$(id -g)" >> .env

# Rebuild containers
make up
```

**How it works (Laravel Sail-style):**
- A `sail` user is created in the container with your host UID/GID (e.g., 1000:1000)
- Apache is configured to run as the `sail` user instead of `www-data`
- All application code executes as the `sail` user (non-root)
- Files created by the application are owned by your host user
- Use `make cli` to open a shell as the `sail` user
- No more `sudo` needed to edit files created by Docker

**Running commands:**
```bash
# As sail user (recommended for most tasks)
docker compose exec -u sail app bash
docker compose exec -u sail app php bin/migrations status
make cli  # Shortcut for bash as sail user

# As root (only when needed for system tasks)
docker compose exec app bash
```

**Security benefits:**
- Container starts as root only for initial permission setup
- Apache and PHP run as non-root `sail` user
- Application code never executes as root
- Files created by the application are owned by your host user
- Same security model as Laravel Sail

**Configuration files:**
- `.env` - Your user/group IDs (create from `.env.example`)
- `docker-compose.yml` - Passes USER_ID/GROUP_ID as build args
- `Dockerfile` - Creates sail user, configures Apache to run as sail
- `docker-entrypoint.sh` - Handles permissions and user switching

## Project Structure

```
time-cube-dashboard/
├── bin/
│   ├── migrations              # Doctrine migrations CLI
│   ├── phpcs                   # PHP_CodeSniffer wrapper
│   └── phpcbf                  # PHP Code Beautifier wrapper
├── config/
│   └── routes.php              # All route definitions
├── database/
│   ├── migrations/             # Doctrine migration files
│   ├── schema.sql              # Legacy schema (replaced by migrations)
│   └── app.db                  # SQLite database (auto-created)
├── public/
│   ├── index.php               # Application entry point
│   ├── css/                    # Stylesheets
│   ├── js/                     # JavaScript files
│   └── uploads/                # User avatars
├── src/
│   ├── Core/                   # Framework core components
│   │   ├── App.php             # Application bootstrap
│   │   ├── Router.php          # Request routing
│   │   ├── Database.php        # DBAL wrapper with migrations
│   │   ├── Auth.php            # Authentication helpers
│   │   └── Controller.php      # Base controller
│   ├── Controllers/            # Application controllers
│   │   ├── ApiController.php   # ESP32 device endpoint
│   │   ├── AuthController.php
│   │   ├── DashboardController.php
│   │   ├── ProjectController.php
│   │   ├── TaskController.php
│   │   ├── TimeEntryController.php
│   │   ├── CubeConfigController.php
│   │   ├── ProfileController.php
│   │   └── SettingsController.php
│   └── Models/                 # Data models
│       ├── User.php
│       ├── Project.php
│       ├── Task.php
│       ├── TimeEntry.php
│       ├── CubeConfig.php
│       └── ApiToken.php
├── templates/                  # Twig templates
│   ├── layout.twig             # Main layout with sidebar
│   ├── auth_layout.twig        # Authentication layout
│   └── [feature]/              # Feature-specific templates
├── .gitignore
├── composer.json               # PHP dependencies
├── docker-compose.yml          # Service orchestration
├── Dockerfile                  # Container configuration
├── Makefile                    # Development shortcuts
├── phpcs.xml                   # PHP_CodeSniffer configuration
├── migrations.php              # Doctrine migrations bootstrap
└── migrations-config.php       # Doctrine migrations config
```

## API Documentation

### ESP32 Cube Endpoint

**POST** `/api/cube`

Receives cube rotation events from the physical device.

**Headers:**
```
X-Time-Cube-Token: <api_token>
Content-Type: application/json
```

**Request Body:**
```json
{
  "cube_id": "unique-cube-identifier",
  "face_color": "red"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Time entry started for task: Task Name",
  "time_entry_id": 123
}
```

### Authentication

- **Web Interface**: Session-based using Symfony Session component
- **API**: X-Time-Cube-Token header token authentication
- Generate API tokens in Settings → API Tokens

## Database Schema

The application uses SQLite with 7 tables:

- **users** - User accounts
- **projects** - User projects
- **tasks** - Tasks within projects
- **time_entries** - Time tracking records
- **api_tokens** - API authentication tokens
- **cubes** - Registered physical cube devices
- **cube_face_mappings** - Maps cube faces to tasks

Schema auto-initializes from `database/schema.sql` on first connection.

## Configuration

### Cube Face Mapping

1. Navigate to **Cubes** in the sidebar
2. Register a new cube with a unique ID
3. Map each face color to a task
4. Configure your ESP32 device with:
   - Cube ID
   - API token (from Settings)
   - API endpoint: `http://your-server:8000/api/cube`

### Supported Face Colors
Configure your ESP32 to send one of these color values:
- red
- blue
- green
- yellow
- orange
- purple
- (or any custom color string you define)

## Development

### No Build Tools
This project intentionally avoids frontend build tools. All CSS and JavaScript are vanilla files served directly.

### Code Style
- **Namespace**: All PHP classes use `App\*` namespace (PSR-4)
- **Controllers**: Extend `App\Core\Controller`
- **Models**: Plain PHP classes with static methods for database operations
- **Views**: Twig templates with inheritance

### Adding New Routes

Edit `config/routes.php`:
```php
$router->get('/path/{param}', 'ControllerName', 'methodName');
$router->post('/path/{param}', 'ControllerName', 'methodName');
```

Parameters in `{brackets}` are extracted and passed to controller methods.

## Deployment

### Docker Production

The included Dockerfile is production-ready:
- PHP 8.3 with Apache
- Composer dependencies optimized
- Proper file permissions for database and uploads
- Document root set to `public/`

### Environment Considerations

- Ensure `database/` directory is writable
- Ensure `public/uploads/` directory is writable
- Database persists via Docker volume
- No environment variables required (SQLite-based)

## Troubleshooting

### Database Issues
```bash
# Reset database (WARNING: deletes all data)
docker compose exec app rm /var/www/html/database/app.db
# Restart to auto-recreate from schema
docker compose restart
```

### Permission Issues
```bash
docker compose exec app chown -R www-data:www-data /var/www/html/database /var/www/html/public/uploads
```

### View Logs
```bash
docker compose logs -f app
```

## License

This project is proprietary software.

## Contributing

This is a personal project. Contributions are not currently accepted.

## Support

For issues or questions, please contact the project maintainer.
