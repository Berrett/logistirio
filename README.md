# Logistirio

A Laravel-based application with Filament, running in Docker.

## Prerequisites

Before you begin, ensure you have the following installed on your machine:

- [Docker](https://www.docker.com/get-started)
- [Docker Compose](https://docs.docker.com/compose/install/)

## Installation Steps

Follow these steps to set up the project locally:

### 1. Clone the repository

```bash
git clone https://github.com/Berrett/logistirio
cd logistirio
```

### 2. Configure Environment Files

The project uses two `.env` files: one in the root for Docker configuration and one in the `application/` directory for Laravel.

**Root `.env` (Docker):**
```bash
cp .env.example .env
```
Default settings in root `.env`:
- `NGINX_PORT=80` (The application will be accessible at http://localhost)
- `FORWARD_DB_PORT=3306` (MySQL will be accessible from your host at port 3306)

**Application `.env` (Laravel):**
```bash
cp application/.env.example application/.env
```
Ensure the database settings in `application/.env` match the Docker service name when running inside the container:
```env
DB_CONNECTION=mysql
DB_HOST=logistirio_mysql
DB_PORT=3306
DB_DATABASE=logistirio_database
DB_USERNAME=logistirio
DB_PASSWORD=logistirio

APP_URL=http://localhost
ASSET_URL=http://localhost
```

### 3. Start Docker Containers

Build and start the containers in detached mode:

```bash
docker-compose up -d
```

### 4. Install Dependencies

Run composer install inside the app container:

```bash
docker exec -it logistirio_app composer install
```

### 5. Generate Application Key

```bash
docker exec -it logistirio_app php artisan key:generate
```

### 6. Run Database Migrations

```bash
docker exec -it logistirio_app php artisan migrate:fresh --seed
```

### 7. Access the Application

- **Web Interface:** [http://localhost](http://localhost) (or the port defined in your `.env` file)

## Troubleshooting

### Connecting to the Database

- **From inside the Docker container (e.g., Artisan commands):** Use `DB_HOST=logistirio_mysql` and `DB_PORT=3306`.
- **From your host machine (e.g., GUI tools like Sequel Ace):** Use `DB_HOST=127.0.0.1` and `DB_PORT=3306`.

### Assets not loading (Port issues)

If assets are not loading correctly when running on a custom port:
1. Ensure `ASSET_URL` in `application/.env` includes the port (e.g., `http://localhost`).
2. The Nginx proxy is configured to forward the host header correctly.
3. Laravel is configured to trust proxies in `bootstrap/app.php`.

## Docker Services

- **logistirio_app**: PHP-FPM application container.
- **logistirio_mysql**: MySQL 8.0 database.
- **logistirio_redis**: Redis for caching and sessions.
- **logistirio_proxy**: Nginx proxy.
