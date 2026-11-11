# EventHub — Backend API

Backend untuk **EventHub: Sistem Pendaftaran Event & Seminar Kampus**.
Mata kuliah Pemrograman Web Dinamis 2026 — Milestone 2 (Backend API & Keamanan Sisi Server).

## Stack

- **PHP 8.2+** (native, tanpa framework)
- **MySQL 8.x / MariaDB 10.6+** (bisa dialih ke PostgreSQL)
- **PDO** + Prepared Statements (anti SQL injection)
- **JWT** via `firebase/php-jwt` (anti-tamper autentikasi)
- **vlucas/phpdotenv** untuk konfigurasi env

## Struktur Folder

```
backend/
├── composer.json                 # Autoload PSR-4 EventHub\ → src/
├── .env.example                  # Template konfigurasi
├── public/
│   ├── index.php                 # Front controller (entry point)
│   ├── router.php                # Router untuk php -S (built-in server)
│   └── .htaccess                 # Rewrite untuk Apache
├── routes/
│   └── api.php                   # Definisi semua endpoint
├── src/
│   ├── Database.php              # Singleton koneksi PDO
│   ├── config/
│   │   ├── app.php               # Nama app, timezone, debug
│   │   ├── database.php          # DSN + opsi PDO
│   │   ├── jwt.php               # Secret, algorithm, expiry
│   │   └── cors.php              # Allowed origins/methods/headers
│   ├── Core/
│   │   └── Router.php            # Router path-param native PHP
│   ├── Controllers/
│   │   ├── AuthController.php        # register, login, me
│   │   ├── EventController.php       # CRUD events
│   │   └── RegistrationController.php# register, participants, status, cancel
│   ├── Middlewares/
│   │   ├── JwtMiddleware.php     # Verifikasi Bearer token
│   │   ├── RoleMiddleware.php    # Proteksi berdasarkan role
│   │   └── CorsMiddleware.php    # Header CORS
│   ├── Services/
│   │   └── JwtService.php        # Encode/decode JWT (firebase/php-jwt)
│   ├── Models/
│   │   ├── UserModel.php         # PDO queries tabel users
│   │   ├── EventModel.php        # PDO queries tabel events
│   │   └── RegistrationModel.php # PDO queries tabel registrations
│   └── Helpers/
│       ├── InputSanitizer.php    # trim/strip_tags/htmlspecialchars
│       ├── Validator.php         # Validasi rule-based
│       └── ResponseHelper.php    # Format JSON konsisten
├── database/
│   ├── migrations/
│   │   └── 001_init.sql          # Skema 3 tabel + FK + index
│   └── seeders/
│       └── seed.php              # Data awal (password di-hash bcrypt)
├── tests/
│   └── smoke_test.php            # Cek konfigurasi + koneksi + JWT
└── postman/
    └── EventHub_Milestone2_Postman.json  # Collection siap impor
```

## Setup Lokal

### 1. Prasyarat

- PHP 8.2+ (aktifkan ekstensi `pdo_mysql`, `mbstring`, `openssl`)
- Composer
- MySQL 8.x atau MariaDB 10.6+
- (Opsional) Postman / Insomnia untuk testing

### 2. Install dependensi

```bash
cd backend
composer install
```

### 3. Konfigurasi environment

```bash
cp .env.example .env
```

Edit `.env` sesuaikan database:

```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=eventhub
DB_USERNAME=root
DB_PASSWORD=

JWT_SECRET=<generate dengan: php -r "echo bin2hex(random_bytes(32));">
JWT_ALGORITHM=HS256
JWT_EXPIRY=86400
```

### 4. Buat database & tabel

```bash
mysql -u root -p < database/migrations/001_init.sql
```

### 5. Seed data awal

```bash
php database/seeders/seed.php
```

Data awal:

| Role        | Email                  | Password        |
|-------------|------------------------|-----------------|
| admin       | admin@eventhub.ac.id   | admin123         |
| participant | abyan@student.ac.id    | participant123  |
| participant | delwyn@student.ac.id   | participant123  |
| participant | april@student.ac.id    | participant123  |

### 6. Jalankan server lokal

```bash
# Opsi 1: PHP built-in server (paling cepat untuk demo)
composer serve
# → http://localhost:8080

# Opsi 2: Apache/nginx dengan document root ke folder public/
```

### 7. Smoke test

```bash
php tests/smoke_test.php
```

Harus mencetak `[OK]` untuk config, koneksi DB, 3 tabel, dan JWT.

## Testing API

Import `postman/EventHub_Milestone2_Postman.json` ke Postman/Insomnia.
Collection sudah berisi:

- Test positif (200/201) untuk semua endpoint
- Test negatif (400, 401, 403, 404, 422)
- Script auto-simpan token ke variable `admin_token` & `participant_token`

Urutan testing yang disarankan:

1. Login admin → simpan `admin_token`
2. Login participant → simpan `participant_token`
3. List events (publik)
4. Create event (admin)
5. Register event (participant)
6. List participants (admin)
7. Update status (admin)
8. Cancel registration (participant)
9. Jalankan test negatif

## Standar Response

```json
{
  "success": true,
  "message": "OK",
  "data": {},
  "errors": null
}
```

Error selalu memakai format yang sama dengan `success: false`.

## Keamanan

- **Anti SQL Injection**: 100% PDO prepared statements + parameter binding, tidak ada string concatenation di query.
- **Anti XSS**: `InputSanitizer` (trim, strip_tags, htmlspecialchars) di setiap input sebelum disimpan/dikembalikan.
- **Password**: bcrypt (`password_hash`/`password_verify`), tidak pernah disimpan plaintext.
- **JWT**: HS256, secret dari env, expiry bisa dikonfigurasi.
- **Authorization**: `JwtMiddleware` (verifikasi token) + `RoleMiddleware` (admin/participant).
- **Kuota & duplikasi**: cek di aplikasi + `UNIQUE (user_id, event_id)` di database sebagai backup.
