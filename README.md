# Laravel 12 Starter Kit

A modern, production-ready Laravel 12 boilerplate with Docker Alpine containerization, optimized for rapid development and deployment.

## 🚀 Features

- **Laravel 12** - Latest PHP web framework with modern architecture
- **PHP 8.5** - Cutting-edge PHP performance and features
- **Docker Alpine** - Lightweight containerized development environment (~280 MB PHP image)
- **PostgreSQL 18** - Robust relational database
- **Redis 8** - High-performance caching and session storage
- **Nginx Alpine** - Production-grade web server with automatic SSL and security headers
- **Vite 8** - Lightning-fast frontend build tool with Less support
- **Custom Artisan Commands** - Enhanced cache management and utilities
- **Auto SSL/HTTPS** - Automatic mkcert certificate generation
- **Makefile** - Simplified command management
- **PHPUnit 12** - Modern testing framework
- **OPcache** - PHP performance optimization with signed remote reset
- **Telegram Error Alerts** - Optional exception notifications

## 🛠 Tech Stack

| Component | Version | Purpose |
|-----------|---------|---------|
| Laravel | 12.x | PHP Web Framework |
| PHP | 8.5.4 | Server-side Language |
| PostgreSQL | 18.3 | Primary Database |
| Redis | 8.x | Caching & Sessions |
| Node.js | 24.x | Frontend Tooling |
| Vite | 8.x | Asset Building |
| Nginx | 1.29.1-alpine | Web Server |
| Docker | Alpine Linux | Container Base |

## 📋 Prerequisites

- **Docker & Docker Compose**
- **Git**
- **Make** (for convenient commands)
- **mkcert** (for trusted local SSL certificates)

## 🚀 Quick Start

### 1. Install mkcert (Required for SSL)

Install and setup mkcert for trusted local SSL certificates:

```bash
# Install mkcert (https://github.com/FiloSottile/mkcert)
# Linux/WSL
curl -JLO "https://dl.filippo.io/mkcert/latest?for=linux/amd64"
chmod +x mkcert-v*-linux-amd64
sudo mv mkcert-v*-linux-amd64 /usr/local/bin/mkcert

# Install local CA in system and browsers
mkcert -install
```

### 2. Configure Environment

```bash
# Copy environment configuration
cp .env.local .env

# Edit APP_NAME and other settings as needed
# APP_NAME=your-app-name
```

### 3. Install System Dependencies (Linux/Ubuntu)

```bash
# Install Git and Make
sudo apt install git make

# Install Docker
curl -fsSL https://get.docker.com -o get-docker.sh
sudo sh get-docker.sh
```

### 4. Build and Start Application

For **local development** (includes automatic SSL certificate generation):
```bash
make update-local
```

For **production** (copy `.env.prod` to `.env` first, set `APP_DEBUG=false`, real DB password and domain):
```bash
make update-prod
```

### 5. Initialize Application

```bash
# Generate app key, create storage link, install dependencies
make setup

# Start asset watching for development
make npm-watch
```

### 6. Access Your Application

🌐 **Local**: https://localhost:8080/ (with trusted SSL certificate)

**Note**: SSL certificates are automatically generated using your local mkcert CA, so you'll see a green lock in your browser without any security warnings!

## 📚 Available Commands

### Environment Management
```bash
make setup              # Initial setup (key, storage, dependencies)
make update-local       # Build and start local environment (auto SSL)
make update-prod        # Build and start production environment
make ssl-generate       # Generate SSL certificates manually
```

### Docker Operations
```bash
make docker-build       # Build containers (includes SSL generation)
make docker-down        # Stop project containers
make exec cmd="command" # Execute command in PHP container
make bash              # Get bash shell in container
```

### Dependency Management
```bash
make composer-install  # Install PHP dependencies from composer.lock
make composer-update   # Update PHP dependencies (refreshes composer.lock)
make npm-install       # Install npm dependencies
make npm-ci            # Clean install from package-lock.json (used on prod)
```

Both `composer.lock` and `package-lock.json` are committed: deployments run
`composer install` / `npm ci`, so production gets exactly the versions you
tested. Upgrade dependencies deliberately via `make composer-update` /
`make npm-update` and commit the refreshed lock files.

### Asset Building
```bash
make npm-dev          # Build assets for development
make npm-prod         # Build assets for production
make npm-watch        # Watch assets for changes
```

### Database Management
```bash
make migrate          # Run database migrations
make migrate-fresh    # Fresh migration (drop all tables)
make seed-db         # Run database seeders
make backup-db       # Create database backup
make restore-db      # Restore database from backup
```

### Cache & Performance
```bash
make cache           # Clear all caches (includes IDE helpers)
make cache-noide     # Clear caches without IDE helpers
make opcache-clear   # Clear OPcache (CLI + web via signed request)
```

### Testing
```bash
make test           # Run all tests with setup
make cmd-test       # Trigger a test exception (checks Telegram alerts)
```

### Logs & Monitoring
```bash
make log-queue      # View queue logs
make log-sql        # View SQL logs
make log-nginx      # View nginx logs
make log-access     # View access logs
make log-scheduler  # View scheduler logs
```

## 🏗 Architecture

### Docker Services

- **php-fpm**: Main PHP application container (PHP 8.5-FPM Alpine, runs as UID 1000)
- **nginx**: Web server with automatic SSL certificate generation (Alpine)
- **nginx-base**: Base nginx image with common configuration (Alpine)
- **sql**: PostgreSQL 18 database
- **redis**: Redis 8 cache and session store
- **scheduler / queue-default** (`docker-compose.queues.yml`): `schedule:work`
  and queue workers — included in the production `COMPOSE_FILE` out of the box

Compose files are selected via the `COMPOSE_FILE` variable in `.env`:
local uses `docker-compose.yml:docker-compose.local.yml`, production adds
`docker-compose.prod.yml:docker-compose.queues.yml` (queue workers and the
scheduler run automatically).

### Custom Features

- **Enhanced Cache Command**: `php artisan volkv:cache` - Comprehensive cache clearing
- **OPcache Remote Reset**: `php artisan opcache:clear` clears CLI and web
  OPcache via an HMAC-signed internal HTTP request (`opcache.validate_timestamps=0`
  in production for maximum performance)
- **Auto SSL Support**: Automatic mkcert certificate generation on build
- **Asset Pipeline**: Vite with Less preprocessing and PurgeCSS
- **Telegram Alerts**: Uncaught exceptions are reported to a Telegram chat
  (configure `TELEGRAM_BOT_TOKEN` / `TELEGRAM_CHAT_ID`; rate-limited, HTML-safe)
- **Alpine Linux**: PHP image is ~280 MB — build dependencies are removed
  after compiling extensions

### File Structure

```
├── app/
│   ├── Console/Commands/     # Custom Artisan commands
│   ├── Http/Controllers/     # HTTP controllers
│   ├── Models/              # Eloquent models
│   └── Services/            # Application services (TelegramService)
├── docker/                  # Docker configuration
│   ├── nginx/              # Nginx configs
│   └── php-fpm/            # PHP-FPM configs
├── resources/
│   ├── js/                 # JavaScript assets
│   ├── less/               # Less stylesheets
│   └── views/              # Blade templates
└── docker-compose.yml      # Main Docker compose
```

## 🔧 Configuration

### Environment Variables

Key environment variables to configure in `.env`:

```env
APP_NAME=YourAppName
APP_ENV=local
APP_DEBUG=true
APP_URL=https://localhost:8080

DB_CONNECTION=pgsql
DB_HOST=sql
DB_PORT=5432
DB_DATABASE=postgres
DB_USERNAME=root
DB_PASSWORD=root

CACHE_STORE=redis
SESSION_DRIVER=redis
SESSION_SECURE_COOKIE=true
QUEUE_CONNECTION=redis

REDIS_HOST=redis
REDIS_PASSWORD=null
```

### OPcache Configuration

OPcache is pre-configured and can be controlled via:
```env
PHP_OPCACHE=1
```

## 🧪 Testing

The project includes PHPUnit 12; tests run against an in-memory SQLite database:

```bash
# Run all tests
make test

# Run specific test suites
make exec cmd="vendor/bin/phpunit --testsuite=Unit"
make exec cmd="vendor/bin/phpunit --testsuite=Feature"
```

## 📈 Performance

This starter kit includes several performance optimizations:

- **Alpine Linux**: PHP image ~280 MB (build toolchain removed after compiling extensions)
- **OPcache**: `validate_timestamps=0` in production with managed reset
- **Redis**: For caching and sessions
- **Nginx**: Optimized Alpine configuration with gzip compression
- **Asset Optimization**: Vite with PurgeCSS for minimal CSS

## 🛡 Security

Security features included:

- **HTTPS by default** with automatic certificate generation
- **Trusted SSL certificates** using mkcert local CA
- **Security headers** in Nginx: `X-Frame-Options`, `X-Content-Type-Options`,
  `Referrer-Policy` (enable HSTS at your CDN/edge or in a prod-only config —
  it is intentionally not set globally to avoid poisoning `localhost` in dev)
- **Hidden files blocked** (everything under `/.` except `.well-known`)
- **Secure session cookies** (`SESSION_SECURE_COOKIE=true`)
- **`APP_DEBUG=false`** in the production template
- **Environment separation** between local/production
- **Unprivileged containers**: PHP runs as UID 1000, DB/Redis ports bound to
  `127.0.0.1` in production
- **Alpine Linux** base reduces attack surface

## 📝 Development Notes

- **Concurrent Development**: Use `composer run dev` for concurrent server, queue, logs, and Vite processes
- **Auto SSL**: Certificates automatically generated on `make docker-build`
- **Hot Reloading**: Vite provides instant asset updates during development
- **Database**: PostgreSQL chosen for production-grade features
- **Alpine Benefits**: Faster builds, smaller images, better security
- **Cloudflare**: `docker/nginx/prod/cloudflare.conf` restores real client IPs
  when running behind Cloudflare

## 🌟 Other Projects by Pavel Volkov

Explore more innovative projects built with cutting-edge technologies:

### 🤖 AI & Machine Learning
- **[tokencraft.ai](https://tokencraft.ai)** - Comprehensive AI prompts library where developers and AI enthusiasts discover, share, and collaborate on high-quality prompts across diverse categories. Features community voting, detailed discussions, and curated collections for optimal neural network interactions.

- **[skynetcountdown.com](https://skynetcountdown.com)** - AI news analysis platform that tracks artificial intelligence developments and provides predictive insights on AI safety, AGI timeline, and technological singularity.

- **[songsense.io](https://songsense.io)** - Advanced lyrics analysis platform that provides deep contextual interpretation of song meanings, combining natural language processing with cultural insights.

### 🎮 Gaming & Esports
- **[ensitics.io](https://ensitics.io)** - Professional esports companion platform providing advanced match prediction analytics, deep statistical analysis, and data-driven insights for competitive gaming.

- **[cq.ru](https://cq.ru)** - Leading gaming and esports platform featuring comprehensive esports database, cosplay galleries, analytics, game libraries, and streaming integration. Serving millions of passionate gamers worldwide.

### 🔧 Tools & Utilities
- **[calculat.io](https://calculat.io)** - Comprehensive multilingual calculator platform optimized for SEO performance, featuring specialized calculators for various industries and use cases.

### 👨‍💻 Portfolio
- **[volkv.com](https://volkv.com)** - Premium developer portfolio showcasing fullstack expertise and vibe-coding philosophy, built with modern technologies and optimized for performance.

---

## 🤝 Contributing

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## 📄 License

This project is open-sourced software licensed under the [MIT license](LICENSE).

---

**Built with ❤️ for the Laravel community by [Pavel Volkov](https://volkv.com)**
