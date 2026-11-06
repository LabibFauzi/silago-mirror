# SILAGO — Web & API

Backend **Laravel 12** + **panel admin Blade** untuk aplikasi marketplace kampus **SILAGO**. Aplikasi mobile Flutter (`silago-mobile`) memakai REST API dari project ini.

---

## Tech Stack

| Komponen | Teknologi / Versi |
|---|---|
| Bahasa | PHP **8.4+** (composer.json menulis `^8.2`, tetapi `composer.lock` menuntut `>= 8.4.1`) |
| Framework | **Laravel 12** (`laravel/framework ^12.0`) |
| Autentikasi API | **Laravel Sanctum 4** (Bearer token) + **Laravel Socialite** (Google OAuth) |
| Push notification | **kreait/laravel-firebase ^7.2** (FCM) |
| Manipulasi gambar | **intervention/image** (thumbnail chat, foto produk) |
| Panel admin | Blade + **Tailwind CSS** (CDN) + **Alpine.js 3** (CDN) + **Chart.js** (CDN) |
| Build tooling | **Vite 6** + **Tailwind CSS 4** (`resources/css/app.css`, `resources/js/app.js`) |
| Database dev | **MySQL 8** (Laragon, `DB_DATABASE=silago`) |
| Database produksi | **PostgreSQL** (Render — `render-start.sh` memaksa `DB_CONNECTION=pgsql`) |
| Web server produksi | Apache (`php:8.4-apache`) di Render |
| Testing | PHPUnit 11 + Laravel Pint |

> Catatan: kode mendukung **MySQL dan PostgreSQL**. Migrasi memiliki cabang khusus `pgsql` untuk perubahan kolom enum. SQLite hanya fallback bawaan `config/database.php` dan **tidak dipakai**.

---

## Database

### Lokal (Laragon)
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=silago
DB_USERNAME=root
DB_PASSWORD=
```

### Produksi (Render)
`render-start.sh` otomatis mendeteksi `DATABASE_URL` dan mengeset `DB_CONNECTION=pgsql`, `DB_PORT=5432`.

### Tabel (migrasi)

| Tabel | Fungsi |
|---|---|
| `users` | Akun (role `user`/`admin`/`moderator`, status, `google_id`, `avatar`, `phone`, `show_phone`, `fcm_token`, suspend) |
| `password_reset_tokens` | Token reset password |
| `sessions` | Sesi admin web |
| `cache`, `cache_locks` | Cache driver database |
| `jobs`, `job_batches`, `failed_jobs` | Queue database |
| `personal_access_tokens` | Token Sanctum |
| `categories` | Kategori produk (`name`, `slug`, `icon`) |
| `products` | Barang (`condition`, `specification`, `completeness`, `sell_reason`, `price`, `status`, `verification_status`, `scan_result`, `scan_note`, `expires_at`, soft delete) |
| `product_images` | Foto produk (`image_path`, `sort_order`) |
| `favorites` | Produk favorit (unik per user+product) |
| `cod_points` | Titik COD (`name`, `address`, `lat`, `lng`, `is_active`) |
| `conversations` | Chat (`product_id`, `buyer_id`, `seller_id`, `type` = `chat`/`transaction`) |
| `messages` | Pesan (`type` = `text`/`offer`/`image`/`location`/`system`, `offer_price`, `offer_status`, `parent_offer_id`, `reply_to_id`, koordinat, gambar, `read_at`, soft delete) |
| `marketplace_deals` | Negosiasi & transaksi (`status` = `negotiating`/`agreed`/`completed`/`cancelled`, `agreed_price`, `current_offer_price`, flag persetujuan, token/code QR, data penyelesaian) |
| `reviews` | Ulasan pembeli (`rating`, `comment`, unik per deal+reviewer) |
| `reports` | Laporan pelanggaran (`reason`, `status` = `pending`/`resolved`/`dismissed`, `admin_note`, `reviewed_by`) |
| `notifications` | Notifikasi in-app (`type`, `reference_id`, `data`, `is_read`) |
| `admin_audit_logs` | Jejak aksi admin (`category`, `action`, `target`, `note`, `meta`) |
| `settings` | Key-value pengaturan (`app_name`, `max_photos`, `listing_active_days`, `banned_words*`, dll.) |
| `announcements` | Pengumuman (audience, jadwal kirim) |
| `conversation_presence` | Status online per percakapan (`last_seen_at`) |

### Nilai status penting
- `products.status`: `available`, `reserved`, `sold`
- `products.verification_status`: `pending`, `verified`, `rejected`
- `messages.offer_status`: `pending`, `accepted`, `rejected`, `countered`, `withdrawn`
- `marketplace_deals.status`: `negotiating`, `agreed`, `completed`, `cancelled`
- `reports.status`: `pending`, `resolved`, `dismissed`

---

## Fitur

- **Autentikasi**: register/login email, Google OAuth (domain `@mhs.polines.ac.id`), verifikasi email/OTP, forgot/reset password, logout, token Sanctum.
- **Profil**: edit profil, foto, username/NIM, nomor WhatsApp opsional + kontrol visibilitas, ringkasan statistik, daftar ulasan.
- **Marketplace**: katalog + filter/pencarian, detail produk, tambah/edit/hapus produk, perpanjang (`renew`), ubah status, favorit, lapor produk.
- **Verifikasi & moderasi konten**: `ContentScanner` mencocokkan nama/deskripsi dengan daftar kata terlarang → `scan_result` = `safe`/`flagged` + `scan_note`.
- **Chat**: teks, gambar (thumbnail), lokasi, balas pesan (`reply_to_id`), hapus pesan (soft delete "untuk semua"), presence, mark read, unread count.
- **Negosiasi**: penawaran (`offer`), counter offer, terima/tolak/tarik, aturan satu offer pending per percakapan, deal otomatis saat diterima.
- **Transaksi**: QR completion dua arah, batalkan, nego ulang, ulasan, lapor transaksi.
- **Notifikasi**: in-app + push FCM (opsional), purge internal via secret token.
- **Panel admin**: dashboard, verifikasi barang, laporan, pengguna, stok barang, transaksi, kategori, titik COD, audit log, pengaturan.

---

## Struktur Penting

```
app/
├── Http/Controllers/Api/V1/    # REST API mobile
├── Http/Controllers/Admin/     # Panel admin web
├── Http/Resources/             # API Resource (Product, Deal, Message, dll.)
├── Http/Requests/Api/          # Validasi request API
├── Models/                     # Eloquent model
└── Services/                   # OfferService, DealService, ContentScanner,
                                # ProductLifecycleService, NotificationService,
                                # OtpService, GoogleAuthService, SettingService,
                                # AuditLogService, ChatPurgeService
database/
├── migrations/                 # Skema + data migration
├── seeders/                    # CategorySeeder, SettingSeeder, AdminSeeder
└── factories/
resources/views/admin/          # Blade panel admin
routes/
├── api.php                     # /api/v1/*
└── web.php                     # /admin/*
```

---

## Setup Lokal

Prasyarat: **PHP 8.4+**, Composer, MySQL 8, Node.js 20+ (opsional, untuk build asset).

```bash
composer install
cp .env.example .env
php artisan key:generate
```

Isi `.env`:
```env
APP_NAME=SILAGO
APP_URL=http://127.0.0.1:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=silago
DB_USERNAME=root
DB_PASSWORD=

FILESYSTEM_DISK=local
SESSION_DRIVER=database
QUEUE_CONNECTION=database
CACHE_STORE=database

GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=
GOOGLE_REDIRECT_URL=http://127.0.0.1:8000/auth/google/callback

FCM_ENABLED=false
FIREBASE_PROJECT_ID=
FIREBASE_CREDENTIALS=
CHAT_PURGE_SECRET=
```

Migrasi + seed + storage link:
```bash
php artisan migrate
php artisan db:seed
php artisan storage:link
```

Jalankan:
```bash
php artisan serve --host=0.0.0.0 --port=8000
```
API: `http://localhost:8000/api/v1` · Admin: `http://localhost:8000/admin/login`

> Laragon: pastikan vhost memakai **PHP 8.4.x** (bukan 8.3), karena dependency Composer menuntut `>= 8.4.1`.

---

## API

Base URL: `/api/v1`
Header terautentikasi: `Authorization: Bearer <token>` + `Accept: application/json`.

### Publik
| Method | Endpoint | Fungsi |
|---|---|---|
| GET | `/app-config` | Konfigurasi aplikasi |
| POST | `/auth/register` | Daftar |
| POST | `/auth/verify-email` | Verifikasi email |
| POST | `/auth/resend-verification` | Kirim ulang verifikasi |
| POST | `/auth/login` | Login email/password |
| POST | `/auth/verify-otp` | Verifikasi OTP |
| POST | `/auth/resend-otp` | Kirim ulang OTP |
| POST | `/auth/login-google` | Login Google |
| POST | `/auth/forgot-password` | Lupa password |
| POST | `/auth/reset-password` | Reset password |
| GET | `/categories` | Kategori publik |
| POST | `/internal/chat/purge` | Cron purge chat (secret) |
| POST | `/internal/notifications/purge` | Cron purge notifikasi (secret) |

### Terautentikasi (Sanctum + user aktif)
| Grup | Endpoint |
|---|---|
| Auth | `POST /auth/logout` |
| Profil | `GET/PUT /me`, `POST /me/fcm-token`, `POST /me/test-push`, `GET /me/summary`, `GET /me/reviews`, `GET /me/products`, `GET /me/favorites` |
| Telepon | `PUT /me/phone`, `POST /me/phone/visibility`, `DELETE /me/phone` |
| Produk | `GET/POST /products`, `GET/PUT/DELETE /products/{product}`, `POST /products/{product}/renew`, `POST /products/{product}/status`, `POST /products/{product}/favorite`, `POST /products/{product}/report` |
| COD | `GET /cod-points` |
| Chat | `GET /conversations`, `POST /products/{product}/conversations`, `GET /conversations/unread-count`, `POST /conversations/read-all`, `POST /conversations/{c}/presence`, `GET /conversations/{c}`, `GET/POST /conversations/{c}/messages`, `DELETE /conversations/{c}/messages/{m}`, `POST /conversations/{c}/read`, `POST /conversations/{c}/offers` |
| Penawaran | `POST /offers/{offer}/accept`, `POST /offers/{offer}/reject`, `POST /offers/{offer}/cancel` |
| Transaksi | `GET /deals`, `GET /deals/{deal}`, `POST /deals/{deal}/qr`, `POST /deals/{deal}/complete`, `POST /deals/{deal}/cancel`, `POST /deals/{deal}/renegotiate`, `POST /deals/{deal}/review`, `POST /deals/{deal}/report` |
| Profil publik | `GET /users/{user}`, `GET /users/{user}/reviews`, `GET /users/{user}/products`, `POST /users/{user}/report` |
| Notifikasi | `GET /notifications`, `GET /notifications/unread-count`, `POST /notifications/{n}/read`, `POST /notifications/read-all` |

---

## Panel Admin

Route prefix `/admin`, login: `/admin/login`.

| Halaman | Fungsi |
|---|---|
| Dashboard | Statistik pengguna/barang/transaksi, laporan terbaru (10 + pagination), chart 7 hari |
| Verifikasi | Tinjau barang + **Moderasi Konten** (`safe`/`flagged`), setujui/tolak |
| Laporan | Tinjau & selesaikan/tolak laporan |
| Pengguna | Cari/filter, lihat profil, suspend/aktifkan |
| Barang | Daftar & detail barang (foto, info, riwayat) |
| Transaksi | Daftar, detail, dukungan sengketa |
| Kategori | CRUD kategori + produk per kategori |
| Titik COD | CRUD titik COD |
| Audit | Riwayat aksi admin (10 + pagination) |
| Pengaturan | Nama app, email dukungan, max foto, masa aktif listing, session timeout, kata terlarang |

> Mode maintenance sudah dihapus. Tidak ada middleware/toggle maintenance.

---

## Deployment (Render)

Repo menyertakan `Dockerfile` + `render-start.sh`:

- Build asset Vite pada stage `node:22-alpine`.
- Runtime `php:8.4-apache` + ekstensi `pdo_pgsql`, `pgsql`, `zip`, `gd`, `intl`, `bcmath`, `exif`, `opcache`.
- Batas upload: `upload_max_filesize=12M`, `post_max_size=16M`, `memory_limit=512M`.
- Start script: buat direktori storage, deteksi `DATABASE_URL` → PostgreSQL, generate `APP_KEY`, `storage:link`, migrate, cache config/route/view, jalankan Apache pada `$PORT`.

Env wajib di Render: `APP_KEY`, `DATABASE_URL`, `APP_URL`, kredensial Google, dan (opsional) Firebase service account.

---

## Testing

```bash
php artisan test
php artisan test --filter=OfferNegotiationTest
vendor/bin/pint
```

---

## Terkait

- Mobile: [`silago-mobile`](../silago-mobile) — Flutter, konsumen REST API ini.