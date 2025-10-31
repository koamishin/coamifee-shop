# Docker Deployment Guide

This guide explains how to deploy the Coamifee Shop Laravel application using Docker.

## Overview

The application uses a multi-stage Docker setup optimized for Laravel 12 with:
- **PHP 8.4** with required extensions
- **Nginx** web server
- **Supervisor** for process management
- **Node.js** for frontend asset building
- **SQLite** database (configurable)
- **Laravel 12** with **Filament 4**, **Livewire 3**, and **Tailwind CSS 4**

## Quick Start

### Local Development

1. **Build and start the application:**
   ```bash
   make build
   make up
   ```

2. **With hot reloading (recommended for development):**
   ```bash
   make up-dev
   ```

3. **Access the application:**
   - Main app: http://localhost:8080
   - Admin panel: http://localhost:8080/admin
   - Health check: http://localhost:8080/health

### Production Deployment

1. **Build the production image:**
   ```bash
   make build-prod
   ```

2. **Run the production container:**
   ```bash
   docker run -d \
     --name coamifee-shop \
     -p 8080:8080 \
     -e APP_ENV=production \
     -e APP_DEBUG=false \
     -e APP_URL=https://yourdomain.com \
     coamifee-shop:latest
   ```

## Available Commands

Use the Makefile for common operations:

```bash
make help          # Show all available commands
make build         # Build Docker image
make up            # Start application
make down          # Stop application
make shell         # Access container shell
make artisan       # Run Artisan commands
make npm           # Run npm commands
make logs          # View application logs
make test          # Run tests
make migrate       # Run database migrations
make clear         # Clear Laravel caches
make clean         # Clean up Docker resources
```

## Configuration

### Environment Variables

Key environment variables for production:

```bash
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com
DB_CONNECTION=sqlite          # or mysql/pgsql
DB_DATABASE=/var/www/html/database/database.sqlite
CACHE_DRIVER=redis           # or file/database
SESSION_DRIVER=database
QUEUE_CONNECTION=database
```

### Database Options

**SQLite (Default):**
```bash
DB_CONNECTION=sqlite
DB_DATABASE=/var/www/html/database/database.sqlite
```

**MySQL:**
```bash
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=coamifee_shop
DB_USERNAME=laravel
DB_PASSWORD=your_password
```

**PostgreSQL:**
```bash
DB_CONNECTION=pgsql
DB_HOST=postgres
DB_PORT=5432
DB_DATABASE=coamifee_shop
DB_USERNAME=laravel
DB_PASSWORD=your_password
```

## Development Workflow

1. **Make changes to your code locally**
2. **Test changes:**
   ```bash
   make logs     # Check logs
   make test     # Run tests
   make artisan  # Run specific commands
   ```
3. **Build for production:**
   ```bash
   make build-prod
   ```
4. **Deploy to your production environment**

## File Structure

```
├── Dockerfile              # Multi-stage Docker build
├── docker-compose.yml      # Local development setup
├── Makefile               # Common Docker commands
├── .dockerignore          # Files to exclude from Docker build
├── docker/                # Configuration files
│   ├── supervisord.conf   # Supervisor configuration
│   ├── nginx.conf         # Nginx configuration
│   ├── php.ini            # PHP configuration
│   └── php-fpm.conf       # PHP-FPM configuration
└── DEPLOYMENT.md          # This file
```

## Health Checks

The application includes a health check endpoint:
- **URL:** `/health`
- **Response:** `healthy` with status 200

## Performance Optimizations

The Docker setup includes:

- **PHP OPcache** for optimized PHP performance
- **Nginx gzip compression** for faster asset delivery
- **Static file caching** with long expiration times
- **Laravel optimizations** (config, route, view caching)
- **Multi-stage builds** for smaller production images

## Security Features

- **Non-root user** for application processes
- **Security headers** (CSP, XSS protection, etc.)
- **Denied access** to sensitive files (.env, storage, etc.)
- **Limited directory permissions** for writable directories

## Troubleshooting

### Common Issues

1. **Permission errors:**
   ```bash
   docker-compose exec app chown -R laravel:laravel storage bootstrap/cache
   docker-compose exec app chmod -R 775 storage bootstrap/cache
   ```

2. **Database connection issues:**
   - Check database configuration in `.env`
   - Ensure database service is running (if using MySQL/PostgreSQL)

3. **Asset build failures:**
   ```bash
   docker-compose exec app npm install
   docker-compose exec app npm run build
   ```

4. **Clear caches:**
   ```bash
   make clear
   ```

### Logs

- **Application logs:** `make logs`
- **All service logs:** `make logs-all`
- **Container logs:** `docker logs coamifee-shop-app`

## Production Considerations

1. **Environment:** Set `APP_ENV=production` and `APP_DEBUG=false`
2. **Database:** Use MySQL/PostgreSQL for production instead of SQLite
3. **Caching:** Configure Redis for caching and sessions
4. **SSL:** Configure HTTPS in production
5. **Backups:** Set up regular database backups
6. **Monitoring:** Implement application monitoring and alerting

## Deployment Platforms

This Docker setup is compatible with:
- **Docker Swarm**
- **Kubernetes**
- **AWS ECS/EKS**
- **Google Cloud Run**
- **Azure Container Instances**
- **DigitalOcean App Platform**
- **Heroku**
- **Railway**
- **Coolify**

## Support

For issues related to:
- **Docker setup:** Check this guide and Docker documentation
- **Laravel application:** Refer to Laravel documentation
- **Filament admin:** Refer to Filament documentation