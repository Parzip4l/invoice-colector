# Docker

Project ini memakai Laravel 11 dengan database PostgreSQL.

## Development

Development memakai hot reload lewat service Vite.

```sh
docker compose up --build
```

- Laravel: http://localhost:8000
- Vite HMR: http://localhost:5173
- PostgreSQL host port: 5433

## Production

Production memakai image terpisah dari `Dockerfile.production`, build asset Vite di dalam image, dan menjalankan Laravel lewat Apache.

```sh
cp .env.production.example .env.production
```

Isi `APP_KEY` dengan hasil `php artisan key:generate --show` dari environment server, ubah `DB_PASSWORD`, lalu jalankan di server:

```sh
set -a
. ./.env.production
set +a
docker compose -f docker-compose.prod.yml up --build -d
```

- App production: http://localhost:8080
- Migration production opsional: set `RUN_MIGRATIONS=true`
