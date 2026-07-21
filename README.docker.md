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

## Bootstrap Whitelist User

Jika production masih kosong dan login menampilkan `Account is inactive or not whitelisted.`, buat user internal pertama dari server:

```sh
docker compose --env-file .env.production -f docker-compose.prod.yml exec app php artisan invoice:whitelist-user admin@company.com \
  --name="Admin Invoice" \
  --role=ADMIN_DIVISI \
  --ldap-uid=admin@company.com \
  --division-code=DIV-OPS \
  --division-name="Divisi Operasional" \
  --department-code=DEP-OPS-ADM \
  --department-name="Departemen Administrasi Operasional"
```

Jika LDAP belum aktif dan butuh login password lokal sementara:

```sh
docker compose --env-file .env.production -f docker-compose.prod.yml exec app php artisan invoice:whitelist-user admin@company.com \
  --name="Admin Invoice" \
  --role=ADMIN_DIVISI \
  --password="password-kuat" \
  --division-code=DIV-OPS \
  --department-code=DEP-OPS-ADM
```

## Import Data E-Procurement Sementara

Selama API e-proc belum tersedia, export Excel bisa dikonversi ke CSV lalu diimport ke master data lokal:

- `eproc_vendors.csv` mengisi `vendors` dari data vendor aktif.
- `eproc_purchasing_po.csv` mengisi `agreement_references` dari ringkasan PO/pengadaan.

Copy CSV ke server host:

```sh
scp eproc_vendors.csv eproc_purchasing_po.csv root@SERVER:/var/www/invoice-colector/storage/app/imports/
```

Masukkan CSV ke container production:

```sh
docker compose --env-file .env.production -f docker-compose.prod.yml exec app mkdir -p /var/www/html/storage/app/imports
docker cp storage/app/imports/eproc_vendors.csv invoice-collector-production-app:/var/www/html/storage/app/imports/eproc_vendors.csv
docker cp storage/app/imports/eproc_purchasing_po.csv invoice-collector-production-app:/var/www/html/storage/app/imports/eproc_purchasing_po.csv
```

Jalankan import di server:

```sh
docker compose --env-file .env.production -f docker-compose.prod.yml exec app php artisan eproc:import-csv \
  --vendors=/var/www/html/storage/app/imports/eproc_vendors.csv \
  --purchasing=/var/www/html/storage/app/imports/eproc_purchasing_po.csv \
  --division-code=EPROC \
  --division-name="E-Procurement" \
  --created-by=muhamad.sobirin@lrtjakarta.co.id
```
