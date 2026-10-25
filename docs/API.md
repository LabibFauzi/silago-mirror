# SILAGO API Documentation

> Marketplace kampus Polines — Laravel 12 + Sanctum
> Base URL: `http://localhost:8000/api/v1` (production ganti host)
> OpenAPI source: [`../openapi.yaml`](../openapi.yaml)
> Postman collection: [`./postman-collection.json`](./postman-collection.json)

---

## Table of Contents

1. [Overview](#overview)
2. [Authentication](#authentication)
3. [Standard Responses & Errors](#standard-responses--errors)
4. [Pagination](#pagination)
5. [Endpoints](#endpoints)
   - [1. Config](#1-config)
   - [2. Auth](#2-auth)
   - [3. Profile](#3-profile)
   - [4. Catalog](#4-catalog)
   - [5. Products](#5-products)
   - [6. Conversations](#6-conversations)
   - [7. Messages](#7-messages)
   - [8. Offers](#8-offers)
   - [9. Deals](#9-deals)
   - [10. Reviews](#10-reviews)
   - [11. Reports](#11-reports)
   - [12. Users](#12-users)
   - [13. Notifications](#13-notifications)
   - [14. Favorites](#14-favorites)
6. [Schemas](#schemas)
7. [cURL Examples](#curl-examples)
8. [Changelog](#changelog)

---

## Overview

| Item | Value |
|------|-------|
| Prefix | `/api/v1` |
| Auth | `Authorization: Bearer <token>` (Sanctum `personal_access_tokens`) |
| Content-Type | `application/json` (multipart untuk `photos` / `profile_photo`) |
| Locale | `id` (Carbon locale, pagination Tailwind) |
| Storage URL | `Storage::url(path)` → `/storage/...` |
| Rate limits | `login` 5/min per IP · `verify-email` 3/min · `api` 60/min (user\|IP) · `resend_verification` 1/60s |
| Maintenance | Middleware `maintenance`: semua `/api/v1/*` 503 kecuali `POST /auth/login` (via `withoutMiddleware`) |
| Suspended | Middleware `user.active`: jika `user.status === 'suspended'` → revoke `currentAccessToken()` → 401 |

---

## Authentication

Flow:

```
POST /auth/register {name, nim, email, password, password_confirmation}
  → 201 + UserResource + email_verify_{email} cache 15 menit (6 digit, Log::info)
POST /auth/verify-email {email, code}
  → 200 + email_verified_at = now()
POST /auth/login {login|email|nim|username, password, ?fcm_token}
  → 200 + {token, user}   // token = createToken('silago_api')->plainTextToken
Header: Authorization: Bearer <token>
POST /auth/logout → delete currentAccessToken()
```

- `login` field auto-filled dari `email`/`nim`/`username` di `LoginRequest::prepareForValidation`.
- Login coba `email` dulu (FILTER_VALIDATE_EMAIL), fallback `nim` → `username`.
- Email domain whitelist: `settings.allowed_email_domains` (newline-separated, `str_ends_with` case-insensitive).
- `fcm_token` disimpan ke `users.fcm_token` saat login atau via `POST /me/fcm-token`.

---

## Standard Responses & Errors

Success (resource):
```json
{ "id": 1, "name": "Budi", ... }
```

Paginated:
```json
{ "data": [...], "links": {...}, "meta": {"current_page":1, "last_page":3, "per_page":15, "total": 42 } }
```

Errors (dari `bootstrap/app.php`):
| Status | Body |
|--------|------|
| 401 | `{"message":"Silakan login terlebih dahulu."}` |
| 403 | `{"message":"Akses tidak diizinkan."}` |
| 404 | `{"message":"Data tidak ditemukan."}` |
| 422 | `{"message":"Data yang dikirim tidak valid.","errors":{"field":["msg"]}}` |
| 429 | `{"message":"Terlalu banyak permintaan. Coba lagi nanti."}` |
| 503 | `{"message":"Aplikasi sedang dalam pemeliharaan...","starts_at":...,"ends_at":...}` |

Bisnis error (dari Services): `409 Conflict`, `423 Locked`, `400 Bad Request` dengan `{"message":"..."}`.

---

## Pagination

- Default `per_page`: 15 (products, deals, favorites, my-products), 20 (conversations, notifications), 10 (user reviews).
- Query: `?page=1`, response `links` + `meta`.

---

## Endpoints

### 1. Config

#### GET /app-config
Publik, tanpa auth. Tidak terblokir maintenance? (semua publik pakai `maintenance`, tapi app-config tetap lewat middleware).

**Response 200:**
```json
{
  "max_photos": 5,
  "maintenance_mode": false,
  "listing_active_days": 30,
  "low_stock_threshold": 3,
  "report_reasons": ["Penipuan","Barang terlarang","Sengketa transaksi","Perilaku pengguna","Foto tidak sesuai","Spam chat","Lainnya"],
  "allowed_email_domains": "@polines.ac.id\n@students.polines.ac.id"
}
```

---

### 2. Auth

#### POST /auth/register
| Field | Rule |
|-------|------|
| name | required string max100 |
| nim | required string max20 unique regex `/^[0-9.]+$/` |
| email | required email unique + domain whitelist |
| password | required min8 confirmed |

→ 201 `{message, user: UserResource}` · 422 validation

#### POST /auth/verify-email
Body `email`, `code` (string 6 digit). Cache `email_verify_{email}` 15 menit.
→ 200 `{message, user}` · 400 `{message:"Kode verifikasi tidak valid atau kedaluwarsa.", errors:{code:["Kode tidak valid"]}}`

#### POST /auth/resend-verification
Body `email`. Rate limit 1/60s (RateLimiter `resend_verification_{email}`).
→ 200 `{message}` · 400 sudah terverifikasi · 429

#### POST /auth/login
Body `login|email|nim|username` + `password` + `?fcm_token`. Rate limit `login:{ip}` 5/min.
→ 200 `{token, user}` · 401 kredensial salah / suspended · 429

#### POST /auth/logout
Auth required. `currentAccessToken()->delete()`
→ 200 `{message:"Logout berhasil"}`

#### POST /auth/forgot-password, POST /auth/reset-password
→ 501 `{message:"Not implemented"}`

---

### 3. Profile

Auth `auth:sanctum` + `user.active` + `maintenance`.

#### GET /me
→ 200 `UserResource`

#### PUT /me
Multipart.
| Field | Rule |
|-------|------|
| name | sometimes string max100 |
| phone | sometimes nullable string max30 |
| profile_photo | sometimes nullable image mimes jpg/jpeg/png/webp max2048 |

Hapus foto lama dari `storage/app/public`, simpan `profile-photos/{uuid}.ext`.
→ 200 `UserResource`

#### POST /me/fcm-token
Body `fcm_token` required string
→ 200 `{message:"FCM Token updated"}`

#### GET /me/summary
→ 200:
```json
{
  "avg_rating": 4.5,
  "total_reviews": 12,
  "total_sold": 3,
  "total_bought": 5,
  "active_products": 2,
  "active_deals": 1,
  "total_favorites": 7
}
```

---

### 4. Catalog

#### GET /categories
Publik. `Category::withCount('products')->get(['id','name','slug','icon','products_count'])`
→ 200 `[{id, name, slug, icon, products_count}]`

#### GET /cod-points
Auth. `CodPoint::where('is_active', true)->get()`
→ 200 `[{id, name, address, lat, lng, is_active}]`

---

### 5. Products

#### GET /products
Auth. Filter: `category_id`, `search` (LIKE name/description), `sort` (newest|price_asc|price_desc). Scope: `status=available` + `verification_status=verified` + `expires_at>now OR null` + `seller.status != suspended`. Paginate 15.
→ 200 `Paginated<ProductListResource>`

#### POST /products
Auth + multipart. Set `seller_id=me`, `status=available`, `verification_status=pending`, `expires_at=now+listing_active_days`, scan `ContentScanner` (banned_words), `lockForUpdate` tidak, tapi transaction untuk images.

| Field | Rule |
|-------|------|
| name | required max150 |
| description | nullable string |
| condition | required in `Baru (BNIB),Baru,Bekas Layak,Bekas` |
| specification | nullable string |
| completeness | nullable max255 |
| sell_reason | nullable string |
| price | required numeric min1 |
| stock | required int min1 |
| category_id | required exists categories.id |
| photos | required array min1 max `max_photos` (default 5) |
| photos.* | image mimes jpg/jpeg/png/webp max1024 |

→ 201 `ProductResource` + `{message:"Barang berhasil dikirim dan menunggu verifikasi"}`

#### GET /products/{product}
Auth. Owner lihat semua, lainnya hanya jika `available+verified+not expired`. 404 jika tidak visible.
→ 200 `ProductResource` · 404

#### PUT /products/{product}
Auth, owner only, `lockForUpdate`, 409 jika `deals.status=agreed` exists, jika `name|description|photos` berubah → `verification_status=pending`, foto lama dihapus storage lalu create baru.
Rules `sometimes` versi dari store.
→ 200 `ProductResource` · 403 · 409

#### DELETE /products/{product}
Auth, owner only, 409 jika ada deal agreed. `lockForUpdate`, hapus images storage, `images()->delete()`, `status=hidden`.
→ 200 `{message:"Produk berhasil dihapus."}`

#### POST /products/{product}/renew
Auth, owner only, hanya jika `status=archived` else 400. Set `available`, `pending`, `expires_at=now+days`.
→ 200 `{message, data: ProductResource}`

#### GET /me/products
Auth, `?status`, `?verification_status`, paginate 15, `ProductListResource`.

#### GET /me/favorites — lihat Favorites

#### POST /products/{product}/favorite — lihat Favorites

#### POST /products/{product}/report — lihat Reports

---

### 6. Conversations

#### GET /conversations
Auth, `where buyer_id=me OR seller_id=me`, eager `product.images, buyer, seller, lastMessage, latestDeal, latestOffer`, filter `?q` LIKE product.name / nama lawan bicara, order `updated_at desc`, paginate 20, `unread_count` dihitung via `withCount`.
`ConversationResource` sekarang menyertakan `role` (`buyer`/`seller`), `stage` (`chat|negotiation|deal|completed|cancelled`), `product.cover_image`, serta `last_message.{body,type,sender_id,created_at}`.
→ 200 `Paginated<ConversationResource>`

#### GET /conversations/unread-count
Auth. Total pesan `sender_id != me AND read_at IS NULL` dari semua percakapan.
→ 200 `{count}`

#### POST /conversations/read-all
Auth. Tandai semua pesan dari lawan bicara di seluruh percakapan sebagai dibaca.
→ 200 `{message, count}`

#### POST /products/{product}/conversations
Auth, `firstOrCreate(product_id, buyer_id=me, seller_id=product.seller_id)`. 403 jika milik sendiri atau `verification_status !== verified || status !== available`.
→ 200 `ConversationResource`

#### GET /conversations/{conversation}
Auth, participant only. Return:
```json
{
  "data": {
    "conversation": {ConversationResource},
    "last_offer": {"id","sender_id","offer_price","offer_status","parent_offer_id","message","created_at"}|null,
    "active_deal": {"id","status"}|null,
    "latest_deal": {"id","status","agreed_price","quantity","completed_at","cancelled_at","cancel_note","actions"}|null
  }
}
```
`last_offer` = `messages where type=offer latest`, `active_deal` = deal `agreed` terbaru, `latest_deal` = deal terbaru (agreed/completed/cancelled) + `actions` (`can_generate_qr`, `can_complete`, `can_cancel`, `can_review`).

---

### 7. Messages

#### GET /conversations/{conversation}/messages
Auth, participant only. Query:
- tanpa param → 30 pesan **terbaru**, dikembalikan urut lama → baru
- `?after_id` → pesan dengan id > after_id, urut lama → baru (polling)
- `?before_id` → 30 pesan sebelum id tersebut (riwayat lama)
- `?limit` default 30, maksimal 100
→ 200 `[MessageResource]`

#### POST /conversations/{conversation}/messages
Auth, participant only, `StoreMessageRequest`:

| Field | Rule |
|-------|------|
| type | required in text,location |
| message | required_if type=text max5000 |
| location_lat | required_if type=location numeric |
| location_lng | required_if type=location numeric |
| location_name | nullable max150 |
| location_address | nullable max255 |
| location_accuracy | nullable integer |

`DB::transaction` create + `touch` conversation, notify `pesanBaru` ke lawan.
→ 201 `MessageResource`

#### POST /conversations/{conversation}/read
Auth, participant only. `where sender_id != me AND read_at IS NULL → read_at=now()`
→ 200 `{message:"Messages marked as read"}`

---

### 8. Offers

Business: `OfferService`. Satu conversation hanya 1 offer `pending`. Counter via `parent_offer_id` → set lama `countered`.

#### POST /conversations/{conversation}/offers
Auth, participant only. Validasi `price >0 && <= product.price` else 400. Body `price`, `?note`, `?parent_offer_id exists messages.id` (harus dari conversation yang sama).
`note` disimpan di kolom `messages.message`.

→ 201 `MessageResource(type=offer, offer_price, offer_status=pending)` · 409 sudah ada pending tanpa parent

#### POST /offers/{offer}/accept
Auth, participant only, recipient only (sender ≠ me) else 403. Body `?quantity int min1 default1`. `OfferService::acceptOffer`:
- lock offer, cek pending, bukan sender
- lock conversation+product, cek `stock >= quantity` else 422
- cek tidak ada `deals where conversation_id & status=agreed` else 409
- `offer_status=accepted`, `stock -= quantity`, `product.status = sold if 0 else available`, create `MarketplaceDeal status=agreed`

→ 201 `DealResource` · 403/409/422

#### POST /offers/{offer}/reject
Auth, participant only, recipient only. `offer_status=rejected` + system message "Penawaran ditolak."
→ 200 `{message:"Offer rejected"}`

#### POST /offers/{offer}/withdraw
Auth, sender only, status harus `pending`. `offer_status=rejected` + system message "Penawaran ditarik."
→ 200 `{message:"Offer withdrawn"}` · 403/409

Catatan: setiap accept/reject/withdraw dan complete/cancel deal membuat `messages.type=system` agar perubahan status terlihat oleh polling mobile.

---

### 9. Deals

`MarketplaceDeal` fields: `conversation_id, accepted_message_id, product_id, buyer_id, seller_id, quantity, agreed_price, status(agreed|completed|cancelled), agreed_at, completion_token_hash/code_hash, completion_token_expires_at, completion_attempts, completion_locked_until, completed_at, completed_lat/lng, cancelled_at/cancelled_by/cancel_note`.

#### GET /deals
Auth, `where buyer_id=me OR seller_id=me`, `?status` filter, paginate 15.
→ 200 `Paginated<DealResource>`

#### GET /deals/{deal}
Auth, participant only. Return:
```json
{
  "data": {DealResource},
  "actions": {
    "can_generate_qr": "seller+agreed",
    "can_complete": "buyer+agreed",
    "can_cancel": "status=agreed",
    "can_review": "completed && !hasReviewed"
  }
}
```

#### POST /deals/{deal}/qr
Auth, seller only + `status=agreed` else 403/400. `DealService::generateQr`:
- `token = Str::random(64)`, `code = 6 digit`, `expires = now+10min`
- `completion_token_hash = sha256(token)`, `completion_code_hash = sha256(code)`, `completion_attempts=0`

→ 200 `{token, code, expires_at: ISO8601, qr_data: "silago://deal/{id}/complete/{token}"}`

#### POST /deals/{deal}/complete
Auth, buyer only. `CompleteRequest`: `token required_without:code` XOR `code size6`, `?lat`, `?lng`.

`DealService::complete` (lockForUpdate):
1. status must `agreed` else 409
2. jika `completion_locked_until` future → 423
3. `hash_equals(storedHash, sha256(input))` else `completion_attempts++`, jika >=5 → lock 15min, throw 422
4. jika `completion_token_expires_at` past → 422
5. set `completed`, `completed_at=now`, clear hashes

→ 200 `DealResource` · 422/423/409 · Notif `transaksiSelesai` ke kedua pihak

#### POST /deals/{deal}/cancel
Auth, buyer|seller, `status=agreed` only. Body `?note max1000`. `DealService::cancel`:
- lock deal+product, `stock += quantity`, `product.status=available`, `deal.status=cancelled`, clear QR

→ 200 `{message:"Deal cancelled"}` · 409

---

### 10. Reviews

#### POST /deals/{deal}/review
Auth, `StoreReviewRequest`: `rating 1-5`, `?comment max1000`. Rules: `status=completed`, participant, `!exists reviewer_id=me` else 409.
`reviewee = buyer? seller : buyer`. Notify `ulasan_baru`.
→ 201 `ReviewResource` · 400/403/409

---

### 11. Reports

`StoreReportRequest`: `reason in Penipuan,Barang terlarang,Sengketa transaksi,Perilaku pengguna,Foto tidak sesuai,Spam chat,Lainnya`, `?description max2000`. `status=pending`.

#### POST /products/{product}/report
Auth, bukan milik sendiri else 403. `product->reports()->create` → 201 `{message, data}`

#### POST /users/{user}/report
Auth, bukan diri sendiri else 403.

#### POST /deals/{deal}/report
Auth, participant only else 403.

---

### 12. Users

#### GET /users/{user}
Auth. Return `{id, name, email_verified_at, avg_rating, total_reviews, total_sold, total_bought}`

#### GET /users/{user}/reviews
Auth. `Review::where reviewee_id=user with reviewer → paginate 10`

#### GET /users/{user}/products
Auth. `Product where user_id=user AND status=available AND is_verified=true → paginate 15` (note: model kolom `user_id` vs `seller_id` di controller campur; sesuaikan migration)

---

### 13. Notifications

#### GET /notifications
Auth. `where user_id=me order created_at desc paginate 20` → `NotificationResource` (created_at format `D MMM YYYY, HH:mm` Asia/Jakarta)

#### GET /notifications/unread-count
→ 200 `{count}`

#### POST /notifications/{notification}/read
Owner only else 403. `is_read=true`

#### POST /notifications/read-all
`where is_read=false → is_read=true` → `{message:"All marked as read"}`

---

### 14. Favorites

#### GET /me/favorites
Auth. `user->favorites()->with(product.images, category, seller) latest paginate 15 → map to ProductListResource`

#### POST /products/{product}/favorite
Auth. Toggle: jika exists → delete `favorited=false` else create `favorited=true`
→ 200 `{favorited: bool}`

---

## Schemas

### UserResource
```json
{
  "id": 1,
  "name": "Budi",
  "nim": "3.34.25.1.14",
  "email": "budi@polines.ac.id",
  "phone": "0812...",
  "profile_photo_url": "/storage/profile-photos/uuid.jpg",
  "role": "user",
  "status": "active",
  "email_verified_at": "2026-10-08T10:00:00.000Z",
  "created_at": "2026-10-08T10:00:00.000Z"
}
```

### ProductResource
`id, name, description, condition, specification, completeness, sell_reason, price(float), stock, status, verification_status, rejection_note(only owner), scan_result/scan_note(only staff), expires_at, created_at, updated_at, cover_image(url|null), seller{id,name,profile_photo_url,is_verified}, category{id,name,slug,icon}, images[{id,url,sort_order}], is_favorited`

### ProductListResource
`id, name, price, stock, status, verification_status, condition, created_at, cover_image(url|null), seller{id,name,profile_photo_url}, category{id,name,slug}, is_favorited`

Catatan URL gambar produk: nilai `cover_image` dan `images[].url` dapat berupa URL absolut, path lama `/storage/...`, atau path `/media/product-images/{id}` untuk gambar yang tersimpan di database (PostgreSQL/Supabase). Klien menempelkan path relatif ke origin API.

Gambar baru disimpan di tabel `product_image_data`. Untuk memindahkan gambar lama yang masih ada di disk ke database sebelum redeploy, jalankan `php artisan images:backfill` di container lama.

### ConversationResource
`id, buyer_id, seller_id, role(buyer|seller), stage(chat|negotiation|deal|completed|cancelled), product{id,name,cover_image,price,status,verification_status}, other_party{id,name,profile_photo_url,avatar_url,is_verified}, last_message{body truncated 50, type, sender_id, created_at}|null, unread_count, updated_at`

### MessageResource
`id, conversation_id, sender_id, type(text|location|offer|system), message, offer_price, offer_status, parent_offer_id, location_lat/lng/name/address/accuracy, read_at, created_at`

### DealResource
`id, product{id,name,cover_image,price}, buyer{id,name}, seller{id,name}, quantity, agreed_price, total_price, status, agreed_at, completed_at, cancelled_at, cancel_note, created_at`

### ReviewResource
`id, reviewer{id,name,profile_photo_url}, rating, comment, created_at`

### NotificationResource
`id, title, body, type, reference_id, is_read, created_at("9 Okt 2026, 14:30")`

---

## cURL Examples

```bash
BASE=http://localhost:8000/api/v1

# Config
curl $BASE/app-config

# Register & verify
curl -X POST $BASE/auth/register -H "Content-Type: application/json" \
  -d '{"name":"Budi","nim":"3.34.25.1.14","email":"budi@polines.ac.id","password":"password123","password_confirmation":"password123"}'
curl -X POST $BASE/auth/verify-email -H "Content-Type: application/json" \
  -d '{"email":"budi@polines.ac.id","code":"123456"}'

# Login
curl -X POST $BASE/auth/login -H "Content-Type: application/json" \
  -d '{"login":"budi@polines.ac.id","password":"password123"}'
# → {"token":"1|...","user":{...}}
TOKEN=1|...

# Me
curl $BASE/me -H "Authorization: Bearer $TOKEN"
curl -X PUT $BASE/me -H "Authorization: Bearer $TOKEN" -F name="Budi S" -F profile_photo=@photo.jpg

# Products
curl "$BASE/products?search=laptop&sort=price_asc&category_id=1" -H "Authorization: Bearer $TOKEN"
curl -X POST $BASE/products -H "Authorization: Bearer $TOKEN" \
  -F name="Laptop ThinkPad" -F condition="Bekas Layak" -F price=3500000 -F stock=2 -F category_id=1 -F photos[]=@a.jpg -F photos[]=@b.jpg
curl $BASE/products/1 -H "Authorization: Bearer $TOKEN"

# Conversations & messages
curl -X POST $BASE/products/1/conversations -H "Authorization: Bearer $TOKEN"
curl $BASE/conversations -H "Authorization: Bearer $TOKEN"
curl "$BASE/conversations/1/messages?limit=30&after_id=10" -H "Authorization: Bearer $TOKEN"
curl -X POST $BASE/conversations/1/messages -H "Authorization: Bearer $TOKEN" -H "Content-Type: application/json" \
  -d '{"type":"text","message":"Halo, masih ada?"}'
curl -X POST $BASE/conversations/1/messages -H "Authorization: Bearer $TOKEN" -H "Content-Type: application/json" \
  -d '{"type":"location","location_lat":-6.97,"location_lng":110.42,"location_name":"Polines"}'

# Offers
curl -X POST $BASE/conversations/1/offers -H "Authorization: Bearer $TOKEN" -H "Content-Type: application/json" \
  -d '{"price":3000000}'
curl -X POST $BASE/offers/5/accept -H "Authorization: Bearer $TOKEN" -H "Content-Type: application/json" \
  -d '{"quantity":1}'
curl -X POST $BASE/offers/5/reject -H "Authorization: Bearer $TOKEN"

# Deals
curl $BASE/deals -H "Authorization: Bearer $TOKEN"
curl $BASE/deals/1 -H "Authorization: Bearer $TOKEN"
# Seller generate QR
curl -X POST $BASE/deals/1/qr -H "Authorization: Bearer $SELLER_TOKEN"
# Buyer complete
curl -X POST $BASE/deals/1/complete -H "Authorization: Bearer $BUYER_TOKEN" -H "Content-Type: application/json" \
  -d '{"code":"123456","lat":-6.97,"lng":110.42}'
# Cancel
curl -X POST $BASE/deals/1/cancel -H "Authorization: Bearer $TOKEN" -H "Content-Type: application/json" \
  -d '{"note":"Jadwal bentrok"}'

# Review & report
curl -X POST $BASE/deals/1/review -H "Authorization: Bearer $TOKEN" -H "Content-Type: application/json" \
  -d '{"rating":5,"comment":"Mantap"}'
curl -X POST $BASE/products/1/report -H "Authorization: Bearer $TOKEN" -H "Content-Type: application/json" \
  -d '{"reason":"Spam chat","description":"..."}'

# Notifications
curl $BASE/notifications -H "Authorization: Bearer $TOKEN"
curl $BASE/notifications/unread-count -H "Authorization: Bearer $TOKEN"
curl -X POST $BASE/notifications/1/read -H "Authorization: Bearer $TOKEN"
curl -X POST $BASE/notifications/read-all -H "Authorization: Bearer $TOKEN"

# Favorites
curl -X POST $BASE/products/1/favorite -H "Authorization: Bearer $TOKEN"
curl $BASE/me/favorites -H "Authorization: Bearer $TOKEN"
```

Axios:
```js
import axios from 'axios'
const api = axios.create({ baseURL: 'http://localhost:8000/api/v1' })
api.defaults.headers.common['Authorization'] = `Bearer ${token}`
const { data } = await api.get('/products', { params: { search: 'laptop', sort: 'price_asc' } })
```

---

## Changelog

- 2026-10-09: Initial full docs — OpenAPI 3.1 + Markdown + Postman.
```
