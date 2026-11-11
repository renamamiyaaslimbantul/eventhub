# EventHub

**Event & Seminar Registration Hub**
Proyek mata kuliah **Pemrograman Web Dinamis 2026**

## Tentang Proyek

EventHub membantu mahasiswa dan panitia kampus mengelola pendaftaran event akademik
(webinar, workshop, seminar) dalam satu tempat:

- Mahasiswa (participant) bisa melihat daftar event, mencari & memfilter event,
  mendaftar, dan membatalkan pendaftarannya sendiri.
- Admin mengelola event (buat, ubah, hapus), melihat daftar peserta, dan memperbarui
  status kehadiran (`registered` → `attended` / `canceled`).
- Kuota kursi (`max_seats`) dijaga agar tidak ada yang mendaftar melebihi kapasitas,
  dan satu user tidak bisa mendaftar event yang sama dua kali.

## Anggota Kelompok

| Peran | Nama | NIM |
|---|---|---|
| Ketua / Backend Developer | Bagas Putra Ratmanta | 2400018225 |
| Anggota | Muhammad Abyan | 2400018198 |
| Anggota | Delwyn Putra Darrenakhira | 2400018233 |
| Anggota | Apriliansyah Mahardika | 2400018238 |

## Teknologi

- **Backend**: PHP 8.2+ (native MVC, Composer + PSR-4), PDO + prepared statements,
  MySQL/MariaDB, JWT (`firebase/php-jwt` v7)
- **Frontend**: *(rencana Milestone 3)*
- **Database**: MySQL 8.x / MariaDB 10.6+ (kompatibel PostgreSQL)

## Struktur Proyek

```
eventhub/
├── backend/                  # API server (Milestone 2 — SELESAI)
│   ├── public/               # Front controller & entry point
│   ├── src/
│   │   ├── Controllers/      # Auth, Event, Registration
│   │   ├── Models/           # PDO queries: User, Event, Registration
│   │   ├── Middlewares/      # JWT, Role, CORS
│   │   ├── Services/         # JWT service
│   │   ├── Helpers/          # Sanitizer, Validator, Response
│   │   ├── Core/             # Router
│   │   └── config/           # app, database, jwt, cors
│   ├── database/
│   │   ├── migrations/       # Skema 3 tabel + FK + index
│   │   └── seeders/          # Data awal (password bcrypt)
│   ├── postman/              # Collection 21 request (positif + negatif)
│   ├── tests/                # smoke_test.php, jwt_check.php
│   └── README.md             # Dokumentasi lengkap backend
├── frontend/                 # (rencana Milestone 3)
├── CHECKPOINT_MILESTONE_2.md # Laporan checkpoint Milestone 2
└── README.md
```

## Pencapaian per Milestone

### Milestone 1 — Perencanaan & Struktur Proyek
- Identitas kelompok, pembagian peran, dan struktur folder proyek.
- Spesifikasi teknis stack (backend PHP, database relasional, API RESTful).
- Analisis kebutuhan sistem: 3 entitas (`users`, `events`, `registrations`),
  dua peran (admin, participant), dan aturan bisnis kuota + anti-duplikasi.

### Milestone 2 — Backend API & Keamanan Sisi Server ✅
- **12 endpoint RESTful** penuh (auth, events CRUD, registrations) — semuanya
  teruji di runtime, status code 200/201/400/401/403/404/422 berfungsi.
- **Autentikasi JWT** (`firebase/php-jwt`): register, login, `GET /me`,
  middleware verifikasi token + pemisahan hak akses admin/participant.
- **Keamanan**:
  - 100% PDO prepared statements + parameter binding (anti SQL injection).
  - Sanitasi input `trim`/`strip_tags`/`htmlspecialchars` (anti XSS) — terverifikasi
    input `<script>` tidak masuk database.
  - Password bcrypt; hash password tidak pernah dikembalikan ke client.
  - Kuota & anti-duplikasi dijaga 3 lapis: cek aplikasi + `UNIQUE(user_id, event_id)`
    + catch `PDOException` untuk race condition.
  - CORS terkonfigurasi, header keamanan via `.htaccess`.
- **Standar response JSON** konsisten: `{success, message, data, errors}`.
- **Postman collection** 21 request (positif 2xx & negatif 4xx) siap impor.
- **Database**: skema 3 tabel berelasi + FK + index, seeder idempotent.

### Milestone 3 — Frontend & Integrasi (rencana)
- UI frontend yang mengonsumsi API backend.
- Upload poster event, pagination, refresh token.

## Quick Start (Backend)

Prasyarat: PHP 8.2+, Composer, MySQL/MariaDB.

```bash
cd backend
composer install
cp .env.example .env        # atur DB & generate JWT_SECRET

mysql -u root -p < database/migrations/001_init.sql
php database/seeders/seed.php

composer serve              # http://localhost:8080
```

**Akun seed:**

| Role | Email | Password |
|---|---|---|
| admin | admin@eventhub.ac.id | admin123 |
| participant | abyan@student.ac.id | participant123 |

Import `backend/postman/EventHub_Milestone2_Postman.json` ke Postman/Insomnia untuk
menjalankan seluruh skenario pengujian (login → ambil token → CRUD event → daftar
event → list peserta → update status → batalkan pendaftaran, plus semua kasus error).

Panduan lengkap: [backend/README.md](backend/README.md) dan
[CHECKPOINT_MILESTONE_2.md](CHECKPOINT_MILESTONE_2.md).
