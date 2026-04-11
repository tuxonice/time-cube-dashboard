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
3. Sends data via REST API using Bearer token authentication

## Tech Stack

- **Backend**: PHP 8.3 (custom MVC framework, no Laravel/Symfony)
- **Database**: SQLite with PDO
- **Templating**: Twig 3.x
- **Frontend**: Vanilla JavaScript, CSS (no build tools)
- **Containerization**: Docker & Docker Compose
- **Web Server**: Apache with mod_rewrite
- **UI Design**: Based on [Clean Board](https://github.com/tuxonice/clean-board)

## Architecture

### Custom MVC Framework
All core components are hand-rolled in `src/Core/`:
- **Router** - Pattern-based routing with parameter extraction
- **Database** - SQLite singleton with auto-schema initialization
- **Auth** - Session-based authentication with API token support
- **Controller** - Base controller with auth helpers and Twig rendering

### Request Flow
```
public/index.php → App::run() → Router::dispatch() → Controller → Twig/JSON response
```

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

2. **Start the application**
```bash
make up
# or
docker compose up -d --build
```

3. **Access the application**
Open your browser to `http://localhost:8000`

4. **Create an account**
Register a new user account through the web interface

### Development Commands

```bash
make up       # Build and start Docker containers (first run or after Dockerfile changes)
make start    # Start existing containers
make stop     # Stop containers
make cli      # Open bash shell inside the app container
```

### Installing Dependencies

```bash
docker compose exec app composer install
```

## Project Structure

```
time-cube-dashboard/
├── config/
│   └── routes.php              # All route definitions
├── database/
│   ├── schema.sql              # Database schema (7 tables)
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
│   │   ├── Database.php        # SQLite singleton
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
├── composer.json               # PHP dependencies
├── Dockerfile                  # Container configuration
├── docker-compose.yml          # Service orchestration
└── Makefile                    # Development shortcuts
```

## API Documentation

### ESP32 Cube Endpoint

**POST** `/api/cube`

Receives cube rotation events from the physical device.

**Headers:**
```
Authorization: Bearer <api_token>
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

- **Web Interface**: Session-based (`$_SESSION['user_id']`)
- **API**: Bearer token authentication
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
