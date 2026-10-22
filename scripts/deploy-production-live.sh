#!/usr/bin/env sh
set -eu

COMPOSE_FILE="${COMPOSE_FILE:-docker-compose.prod.yml}"
ENV_FILE="${ENV_FILE:-.env.production}"
BUILD_ASSETS=false
RECREATE_APP=false

for argument in "$@"; do
    case "$argument" in
        --assets)
            BUILD_ASSETS=true
            ;;
        --recreate)
            RECREATE_APP=true
            ;;
        *)
            echo "Opsi tidak dikenal: $argument" >&2
            echo "Penggunaan: $0 [--assets] [--recreate]" >&2
            exit 2
            ;;
    esac
done

compose() {
    docker compose --env-file "$ENV_FILE" -f "$COMPOSE_FILE" "$@"
}

compose run --rm --no-deps --entrypoint composer app install \
    --no-dev \
    --no-interaction \
    --prefer-dist \
    --optimize-autoloader

if [ "$BUILD_ASSETS" = true ]; then
    compose run --rm assets
fi

if [ "$RECREATE_APP" = true ]; then
    compose up -d --force-recreate app
else
    compose up -d app
fi

compose exec -T app php artisan migrate --force --no-interaction
compose exec -T app php artisan optimize:clear --no-interaction
compose exec -T app php artisan optimize --no-interaction

echo "Deployment selesai tanpa rebuild image aplikasi."
