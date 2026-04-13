# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Development Commands

```bash
make up       # Build and start Docker containers (first run or after Dockerfile changes)
make start    # Start existing containers
make stop     # Stop containers
make cli      # Open bash shell inside the app container
make phpcs    # Run PHP_CodeSniffer (PSR-12)
make phpcbf   # Auto-fix code style issues
```

The app runs at `http://localhost:8000`.

To install/update PHP dependencies inside the container:
```bash
docker compose exec app composer install
```

### Code Quality (run inside the container)

```bash
composer test             # Run PEST tests
composer test:coverage    # Run tests with coverage report
composer phpstan          # Run PHPStan static analysis (level 5)
```

## Architecture

**PHP 8.3 custom MVC framework** with Twig templating and SQLite via Doctrine DBAL. No Laravel/Symfony — all core components are hand-rolled in `src/Core/`.

**Request flow:** `public/index.php` → `App::run()` → `Router::dispatch()` → Controller method → Twig render or JSON response

**Routing:** Defined in `config/routes.php`. The router (`src/Core/Router.php`) converts `{param}` placeholders to regex and passes extracted values to controllers.

**Database:** SQLite singleton (`src/Core/Database.php`) using Doctrine DBAL. Pending migrations are run automatically on first connection. The DB file is at `storage/database/app.db` (gitignored, persisted via Docker volume `db-data`). Migrations live in `database/migrations/` and are configured via `migrations-config.php`.

**Auth:** Session-based (`$_SESSION['user_id']`). Controllers call `$this->requireAuth()` from the base `Controller` class. The API endpoint uses X-Time-Cube-Token token auth via `api_tokens` table.

**Namespace:** All PHP classes are `App\*` (PSR-4, rooted at `src/`).

## Data Model

```
users → projects → tasks → time_entries
users → api_tokens
users → cubes → cube_face_mappings → tasks
```

`cube_face_mappings` links physical cube face colors to tasks — when the ESP32 rotates to a face, it POSTs to `/api/cube` and the mapped task starts a time entry.

## Key Files

- `config/routes.php` — all route definitions
- `src/Core/` — Router, App bootstrap, Database singleton, Auth, base Controller
- `src/Controllers/ApiController.php` — ESP32 device endpoint (`POST /api/cube`)
- `database/migrations/` — Doctrine migration files (authoritative schema source)
- `migrations-config.php` — Doctrine Migrations configuration
- `templates/layout.twig` — main layout with sidebar nav
- `phpstan.neon` — PHPStan configuration
- `phpcs.xml` — PHP_CodeSniffer configuration (PSR-12)
- `tests/` — PEST test suites (`Unit/`, `Feature/`)