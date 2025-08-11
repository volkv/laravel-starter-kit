# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is a Laravel 12 boilerplate project configured with Docker for containerized development. The project uses:
- **Backend**: PHP 8.2+ with Laravel 12 framework
- **Database**: PostgreSQL 17.4 with Redis for caching
- **Frontend**: Vite with Less for styling
- **Infrastructure**: Docker Compose with nginx, PHP-FPM, PostgreSQL, and Redis

## Development Commands

### Environment Setup
```bash
# Copy environment configuration
cp .env.example .env

# Initial project setup (key generation, storage link, npm install, build)
make setup

# Start local development environment
make update-local

# Start production environment  
make update-prod
```

### Docker Operations
```bash
# Build and start containers
make docker-build

# Stop all containers
make docker-stop-all

# Execute commands in PHP-FPM container
make exec cmd="your-command"

# Execute as root in PHP-FPM container
make exec-root cmd="your-command"

# Get bash shell in container
make bash

# Get root bash shell
make bash-root
```

### Laravel Artisan Commands
```bash
# Database migrations
make migrate
make migrate-rollback
make migrate-fresh

# Database seeding
make seed-db

# Clear all caches (custom volkv:cache command)
make cache

# Clear caches without IDE helpers
make cache-noide

# Clear OPcache
make opcache-clear

# Generate application key
make key-generate

# Create storage symlink
make storage-link
```

### Asset Building
```bash
# Install npm dependencies
make npm-install

# Build assets for production
make npm-prod

# Watch assets for changes
make npm-watch

# Development asset building (using npm run dev)
make npm-dev
```

### Composer Operations
```bash
# Update all dependencies
make composer-update

# Update production dependencies only
make composer-update-prod

# Install dependencies
make composer-install

# Install production dependencies only
make composer-install-prod

# Dump autoloader
make composer-dump
```

### Testing
```bash
# Run all tests (includes pre-test setup and post-test cleanup)
make test

# Run only feature tests
make _test-feature

# Custom test command
make cmd-test
```

### Database Operations
```bash
# Create database backup
make backup-db

# Restore database from backup
make restore-db

# Pull database from remote server
make pull-db

# Pull and restore database
make pull-restore-db
```

### Logs and Monitoring
```bash
# View queue logs
make log-queue

# View SQL logs
make log-sql

# View nginx access logs
make log-access

# View scheduler logs
make log-scheduler

# View nginx logs
make log-nginx
```

## Architecture

### Custom Artisan Commands
- `php artisan volkv:cache` - Comprehensive cache clearing command that handles:
  - Composer autoload optimization
  - IDE helper generation (local only)
  - Laravel optimization clearing
  - OPcache clearing (if enabled)
  - Queue restart

### Docker Services
- **php-fpm**: Main PHP application container
- **nginx**: Web server with SSL support for local development
- **sql**: PostgreSQL database
- **redis**: Redis cache/session store

### Frontend Assets
- **Vite** configuration in `vite.config.js`
- **Less** stylesheets in `resources/less/`
- **PurgeCSS** integration for optimized CSS builds
- Entry points: `resources/less/app.less` and `resources/js/app.js`

### File Structure
- `app/Console/Commands/` - Custom Artisan commands
- `app/Http/Controllers/` - HTTP controllers
- `app/Models/` - Eloquent models
- `resources/views/` - Blade templates
- `routes/web.php` - Web routes
- `docker/` - Docker configuration files
- `docker-compose.yml` - Main Docker compose file
- Additional compose files for local, production, and queue configurations

### Development Workflow
1. The project uses a custom `dev` script in `composer.json` that runs server, queue, logs, and Vite concurrently
2. SSL certificates can be generated with mkcert for local HTTPS development
3. The application runs on `https://localhost:8080/` in local development
4. File permissions are managed via `make perm` command

## Testing Configuration
- PHPUnit configured in `phpunit.xml`
- Test suites: Unit (`tests/Unit/`) and Feature (`tests/Feature/`)
- Test environment uses array drivers for cache, mail, and session