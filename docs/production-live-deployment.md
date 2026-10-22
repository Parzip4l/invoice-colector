# Production deployment tanpa rebuild setiap push

`docker-compose.prod.yml` menjalankan source aplikasi langsung dari checkout server. Image PHP/Apache hanya perlu dibangun ulang jika Dockerfile atau ekstensi sistem berubah.

## Aktivasi pertama

Sebelum container lama di-recreate, salin storage dari container ke checkout host agar upload yang sudah ada tidak hilang:

```bash
docker compose --env-file .env.production -f docker-compose.prod.yml exec app php artisan down
docker cp invoice-collector-production-app:/var/www/html/storage/. ./storage/
sudo chown -R 33:33 storage bootstrap/cache
docker compose --env-file .env.production -f docker-compose.prod.yml run --rm assets
./scripts/deploy-production-live.sh --recreate
docker compose --env-file .env.production -f docker-compose.prod.yml exec app php artisan up
```

## Deployment rutin

Untuk perubahan PHP, Blade, route, migration, atau gambar public:

```bash
git pull --ff-only
./scripts/deploy-production-live.sh
```

Jika `resources/js`, `resources/scss`, `package.json`, atau `package-lock.json` berubah:

```bash
git pull --ff-only
./scripts/deploy-production-live.sh --assets
```

Jika `.env.production` berubah, tambahkan `--recreate` agar environment container dibaca ulang:

```bash
./scripts/deploy-production-live.sh --recreate
```

Image hanya perlu dibangun ulang untuk perubahan `Dockerfile.production`, konfigurasi Apache/PHP, atau dependency OS/PHP extension:

```bash
docker compose --env-file .env.production -f docker-compose.prod.yml up -d --build --force-recreate app
```

## Reset data transaksi testing

Reset tidak tersedia melalui browser. Aktifkan sementara di `.env.production`:

```dotenv
INVOICE_VERIFICATION_ALLOW_PRODUCTION_RESET=true
INVOICE_VERIFICATION_PRODUCTION_RESET_TOKEN=isi-token-random-panjang
```

Recreate container agar environment baru terbaca, lalu selalu lakukan dry-run:

```bash
docker compose --env-file .env.production -f docker-compose.prod.yml exec -T postgres sh -lc 'pg_dump -U "$POSTGRES_USER" "$POSTGRES_DB"' > backup-before-reset.sql
./scripts/deploy-production-live.sh --recreate
docker compose --env-file .env.production -f docker-compose.prod.yml exec app php artisan invoice-verification:reset-operational-data --dry-run --confirm=isi-token-random-panjang
docker compose --env-file .env.production -f docker-compose.prod.yml exec app php artisan invoice-verification:reset-operational-data --confirm=isi-token-random-panjang
```

Setelah selesai, ubah kembali `INVOICE_VERIFICATION_ALLOW_PRODUCTION_RESET=false` dan jalankan `./scripts/deploy-production-live.sh --recreate`.

Command reset mempertahankan akun SSO, user, divisi, departemen, vendor, bank, memo, kontrak, tipe transaksi/dokumen, template, dan approval flow.
