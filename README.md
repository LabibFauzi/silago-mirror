# SILAGO Backend API

Laravel backend untuk aplikasi SILAGO (Sistem Laporan Mahasiswa Online).

## Setup

1. Install dependencies:
```bash
composer install
```

2. Copy `.env.example` ke `.env`:
```bash
cp .env.example .env
```

3. Generate application key:
```bash
php artisan key:generate
```

4. Setup database di `.env`:
```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_DATABASE=silago
DB_USERNAME=root
DB_PASSWORD=
```

5. Jalankan migration:
```bash
php artisan migrate
```

## Run Server

```bash
php artisan serve --host=0.0.0.0 --port=8000
```

Server berjalan di `http://localhost:8000` (atau `http://IP_ADDRESS:8000` dari device lain)

## Key Features

- **Autentikasi**: Email/password login + Google OAuth (`@mhs.polines.ac.id`)
- **Email Domain**: Restricted ke `@mhs.polines.ac.id` only
- **Login**: Langsung token, tanpa OTP
- **Laporan**: CRUD laporan kegiatan mahasiswa

## API Base URL

```
http://localhost:8000/api/v1
```

## Auth Endpoints

- `POST /register` - Register (email `@mhs.polines.ac.id` only)
- `POST /login` - Login email/password (return token)
- `POST /auth/google/login` - Google OAuth (domain validated)

## Protected Endpoints (require Bearer token)

- `GET /me` - User profile
- `PUT /me` - Update profile
- `GET /me/summary` - User stats
- `GET /reports` - List laporan
- `POST /reports` - Create laporan
- `GET /reports/{id}` - Detail laporan
- `PUT /reports/{id}` - Update laporan
- `DELETE /reports/{id}` - Delete laporan
