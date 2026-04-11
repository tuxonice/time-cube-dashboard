# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Development Commands

```bash
make up       # Build and start Docker containers (first run or after Dockerfile changes)
make start    # Start existing containers
make stop     # Stop containers
make cli      # Open bash shell inside the app container
```

The app runs at `http://localhost:8000`.

To install/update PHP dependencies inside the container:
```bash
docker compose exec app composer install
```

There are no build steps, linting tools, or tests — plain PHP/CSS/JS with no frontend toolchain.

## Architecture

**PHP 8.1+ custom MVC framework** with Twig templating and SQLite via PDO. No Laravel/Symfony — all core components are hand-rolled in `src/Core/`.

**Request flow:** `public/index.php` → `App::run()` → `Router::dispatch()` → Controller method → Twig render or JSON response

**Routing:** Defined in `config/routes.php`. The router (`src/Core/Router.php`) converts `{param}` placeholders to regex and passes extracted values to controllers.

**Database:** SQLite singleton (`src/Core/Database.php`). Schema auto-initializes from `database/schema.sql` on first connection. The DB file is at `database/app.db` (gitignored, persisted via Docker volume `db-data`).

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
- `database/schema.sql` — authoritative schema (7 tables)
- `templates/layout.twig` — main layout with sidebar nav