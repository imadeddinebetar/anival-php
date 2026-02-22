# Anival PHP 2.x

A custom PHP framework skeleton built on top of modern PHP standards and battle-tested open-source libraries. Designed for building robust, scalable web applications and APIs with a clean, expressive developer experience.

---

## Table of Contents

- [Requirements](#requirements)
- [Tech Stack](#tech-stack)
- [Project Structure](#project-structure)
- [Getting Started](#getting-started)
- [Environment Variables](#environment-variables)
- [Make Commands](#make-commands)
- [Running Tests](#running-tests)
- [Code Quality](#code-quality)
- [Architecture Overview](#architecture-overview)
- [License](#license)

---

## Requirements

| Tool           | Version |
| -------------- | ------- |
| PHP            | ^8.3    |
| Composer       | ^2.x    |
| Docker         | ^24.x   |
| Docker Compose | ^2.x    |
| Make           | any     |

---

## Tech Stack

| Layer        | Library / Tool                                        |
| ------------ | ----------------------------------------------------- |
| HTTP         | PSR-7 / PSR-15 · `nyholm/psr7` · `nyholm/psr7-server` |
| Routing      | `nikic/fast-route`                                    |
| DI Container | `php-di/php-di`                                       |
| ORM          | `illuminate/database`                                 |
| Templating   | `illuminate/view`                                     |
| Events       | `illuminate/events`                                   |
| Validation   | `respect/validation`                                  |
| Cache        | `symfony/cache` + Redis                               |
| Session      | `symfony/http-foundation` sessions                    |
| CSRF         | `symfony/security-csrf`                               |
| Queue        | Redis-backed with Supervisord workers                 |
| WebSocket    | `workerman/workerman`                                 |
| Hashing      | `illuminate/hashing`                                  |
| Logging      | `monolog/monolog`                                     |
| Env Loading  | `vlucas/phpdotenv`                                    |
| Web Server   | Nginx + PHP-FPM                                       |
| Database     | MySQL (PostgreSQL & SQLite also supported)            |

---

## Project Structure

```
.
├── docker/                  # Docker & infrastructure configuration
│   ├── docker-compose.yml   # Main service orchestration
│   ├── Dockerfile
│   ├── nginx.conf
│   ├── php.ini
│   ├── supervisord.conf     # Queue worker process manager
│   ├── mysql/               # Local MySQL service
│   └── redis/               # Redis configuration & ACL
├── makefile                 # Developer shortcuts
└── src/                     # Application source
    ├── app/                 # Application layer (controllers, models, services…)
    │   ├── Controllers/
    │   ├── Enums/
    │   ├── Events/
    │   ├── Jobs/
    │   ├── Listeners/
    │   ├── Middleware/
    │   ├── Models/
    │   ├── Providers/
    │   └── Services/
    ├── bootstrap/           # Application bootstrap & service providers
    ├── bin/                 # CLI entry points (worker, WebSocket server)
    ├── config/              # Configuration files
    ├── core/                # Framework core modules
    │   ├── Auth/
    │   ├── Cache/
    │   ├── Config/
    │   ├── Console/
    │   ├── Container/
    │   ├── Database/
    │   ├── Debug/
    │   ├── Events/
    │   ├── Exceptions/
    │   ├── Http/
    │   ├── Log/
    │   ├── Queue/
    │   ├── Security/
    │   ├── Session/
    │   ├── Support/
    │   ├── Translation/
    │   ├── Validation/
    │   ├── View/
    │   └── WebSocket/
    ├── database/            # Migrations, factories & seeders
    ├── public/              # Web root (index.php, assets)
    ├── resources/           # Views & language files
    ├── routes/              # Route definitions (api.php, web.php)
    ├── storage/             # Logs, cache, compiled views
    └── tests/               # Unit & Feature test suites
```

---

## Getting Started

### 1. Clone the repository

```bash
git clone <repository-url>
cd anival-php-v2
```

### 2. Configure the environment

```bash
cp src/.env.example src/.env
```

Edit `src/.env` and fill in the required values (see [Environment Variables](#environment-variables)).

### 3. Build and start the containers

```bash
make build
make up
```

The application will be available at **http://localhost:8080**.

### 4. Start the local MySQL service (optional, separate stack)

```bash
make mysql_up
```

---

## Environment Variables

The following variables are required at minimum:

| Variable           | Description                       | Example                 |
| ------------------ | --------------------------------- | ----------------------- |
| `APP_NAME`         | Application display name          | `Anival`                |
| `APP_ENV`          | Runtime environment               | `local` / `production`  |
| `APP_KEY`          | Application encryption key        | `base64:...`            |
| `APP_URL`          | Full base URL                     | `http://localhost:8080` |
| `APP_DEBUG`        | Enable debug mode                 | `true` / `false`        |
| `DB_CONNECTION`    | Database driver                   | `mysql`                 |
| `DB_HOST`          | Database host                     | `127.0.0.1`             |
| `DB_PORT`          | Database port                     | `3306`                  |
| `DB_DATABASE`      | Database name                     | `anival`                |
| `DB_USERNAME`      | Database user                     | `root`                  |
| `DB_PASSWORD`      | Database password                 | `secret`                |
| `REDIS_HOST`       | Redis host                        | `redis`                 |
| `REDIS_PORT`       | Redis port                        | `6379`                  |
| `REDIS_USERNAME`   | Redis ACL username                | `default`               |
| `REDIS_PASSWORD`   | Redis password                    | `secret`                |
| `QUEUE_CONNECTION` | Queue driver                      | `redis`                 |
| `WEBSOCKET_HOST`   | WebSocket server host             | `0.0.0.0`               |
| `WEBSOCKET_PORT`   | WebSocket server port             | `8282`                  |
| `TRUSTED_PROXIES`  | Comma-separated trusted proxy IPs | `10.0.0.1,10.0.0.2`     |

---

## Make Commands

### Application

| Command                          | Description                                     |
| -------------------------------- | ----------------------------------------------- |
| `make up`                        | Start all containers in detached mode           |
| `make down`                      | Stop and remove containers                      |
| `make build`                     | Build (or rebuild) Docker images                |
| `make restart`                   | Shortcut for `down` then `up`                   |
| `make clean`                     | Stop, remove containers, volumes & prune system |
| `make bash`                      | Open a shell inside the `app` container         |
| `make log`                       | Tail logs from the `app` container              |
| `make test`                      | Run the full test suite inside Docker           |
| `make test-filter filter=MyTest` | Run a specific test by name filter              |

### MySQL local service

| Command              | Description                             |
| -------------------- | --------------------------------------- |
| `make mysql_up`      | Start the local MySQL container         |
| `make mysql_down`    | Stop the local MySQL container          |
| `make mysql_build`   | Build the local MySQL image             |
| `make mysql_restart` | Restart the local MySQL container       |
| `make mysql_bash`    | Open a shell inside the MySQL container |
| `make mysql_log`     | Tail MySQL container logs               |
| `make mysql_clean`   | Remove MySQL containers and volumes     |

---

## Running Tests

Tests are written with **PHPUnit 11** and divided into two suites:

- `Unit` — isolated unit tests (`tests/Unit/`)
- `Feature` — end-to-end HTTP-level tests (`tests/Feature/`)

```bash
# Run all tests via Docker
make test

# Run a specific test class or method
make test-filter filter=UserControllerTest

# Run outside Docker (requires local PHP + dependencies)
cd src
composer test
```

Test environment uses an **in-memory SQLite** database, so no external database setup is needed.

---

## Code Quality

```bash
cd src

# Static analysis (PHPStan)
composer analyse

# Code style fixer (PHP_CodeSniffer)
composer format
```

---

## Architecture Overview

```
HTTP Request
     │
     ▼
  Nginx (port 8080)
     │
     ▼
  PHP-FPM  ──►  public/index.php
                    │
                    ▼
              Bootstrap (app.php)
                    │
                    ▼
           Service Providers (config/app.php)
                    │
                    ▼
           PSR-15 Middleware Pipeline
           (CORS → RequestId → Sanitize → Security
            → Session → CSRF → Route Dispatch)
                    │
                    ▼
              Router (FastRoute)
                    │
                    ▼
              Controller → Service → Model (ORM)
                    │
                    ▼
              PSR-7 Response

Async services (background containers):
  ├── Queue Worker  ─── Redis ─── Supervisord
  └── WebSocket     ─── Workerman
```

### Global Middleware Stack

| Middleware            | Scope  | Purpose                        |
| --------------------- | ------ | ------------------------------ |
| `CorsMiddleware`      | Global | Cross-origin resource sharing  |
| `RequestIdMiddleware` | Global | Attaches a unique request ID   |
| `SanitizeInput`       | Global | Input sanitisation             |
| `SecurityHeaders`     | Global | HTTP security response headers |
| `SessionMiddleware`   | Web    | Session initialisation         |
| `VerifyCsrfToken`     | Web    | CSRF token validation          |
| `DebugBarMiddleware`  | Web    | Debug bar injection (dev only) |
| `AuthenticateApi`     | API    | Bearer token authentication    |

---

## License

MIT © [Imad Eddine Betar](mailto:contac@imadeddinebetar.com)
