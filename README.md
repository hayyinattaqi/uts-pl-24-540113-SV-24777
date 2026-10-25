# Inventaris Stok Obat Klinik

Aplikasi web untuk petugas klinik: mencatat stok obat per batch, merekam obat masuk dan keluar, serta memantau batch yang hampir kedaluwarsa, sudah kedaluwarsa, dan obat dengan stok menipis.

Repository: https://github.com/hayyinattaqi/uts-pl-24-540113-SV-24777

## Fitur

- Login dan logout berbasis token (Laravel Sanctum). Semua endpoint selain login membutuhkan token.
- CRUD obat dengan pencarian kode atau nama, total stok, dan penanda stok menipis.
- Obat masuk: membuat batch baru (nomor batch unik per obat, tanggal kedaluwarsa setelah hari ini).
- Obat keluar: petugas memilih batch secara manual; batch kedaluwarsa dan jumlah melebihi stok ditolak.
- Riwayat transaksi masuk dan keluar dengan filter.
- Dashboard: batch hampir kedaluwarsa (≤ 90 hari), batch kedaluwarsa, dan obat stok menipis.
- Obat yang masih memiliki batch tidak dapat dihapus (409).

## Teknologi

| Bagian | Teknologi |
|---|---|
| Backend | Laravel 12, PHP 8.3, Laravel Sanctum, dilayani FrankenPHP 1.13.3 (Caddy) |
| Frontend | Vue 3, Vite, Vue Router, Axios, disajikan Nginx 1.27 |
| Basis data | MySQL 8.4 (named volume `db_data`) |
| CI/CD | GitHub Actions, GitHub Container Registry (ghcr.io) |

## Struktur Repository

```text
.github/workflows/ci.yml   CI (backend-test, frontend-build) dan deploy ke GHCR
backend/                   API Laravel, Dockerfile, docker/ (entrypoint, Caddyfile), postman/
frontend/                  aplikasi Vue, Dockerfile multi-stage, nginx.conf
docker-compose.yml         db, backend, frontend
.env.example               contoh variabel lingkungan untuk Docker Compose
CLAUDE.md                  spesifikasi proyek
```

## Menjalankan dengan Docker Compose

Prasyarat: Docker Desktop.

1. Salin contoh environment, lalu isi nilai yang kosong:

   ```powershell
   copy .env.example .env
   ```

   - `DB_PASSWORD`, `DB_ROOT_PASSWORD`, `ADMIN_PASSWORD`: gunakan huruf dan angka saja.
   - `APP_KEY`: hasil `php artisan key:generate --show` di folder `backend/` (lengkap dengan awalan `base64:`).
   - `ADMIN_EMAIL` dan `ADMIN_PASSWORD` menjadi akun login admin yang dibuat seeder.

2. Jalankan seluruh sistem:

   ```powershell
   docker compose up -d --build
   docker compose ps
   ```

3. Buka aplikasi:

   | Layanan | Alamat |
   |---|---|
   | Aplikasi Vue | http://localhost:8080 |
   | API (langsung ke backend, untuk Postman) | http://localhost:8000/api |

Frontend memanggil API lewat `/api` yang diteruskan Nginx ke `http://backend:8000`, dan backend terhubung ke MySQL lewat `DB_HOST=db`; semuanya memakai nama service di jaringan Compose. Data MySQL tersimpan di named volume `db_data`, sehingga tetap ada setelah `docker compose down` lalu `docker compose up -d`.

## API

Semua request dan respons berformat JSON (kirim `Accept: application/json`).

| Method | Path | Keterangan |
|---|---|---|
| POST | `/api/login` | Login, mengembalikan token |
| POST | `/api/logout` | Mencabut token aktif |
| GET, POST | `/api/medicines` | Daftar (`?search=`) dan tambah obat |
| GET, PUT, DELETE | `/api/medicines/{id}` | Detail (beserta batch), ubah, hapus obat |
| POST | `/api/stock-in` | Obat masuk (membuat batch) |
| POST | `/api/stock-out` | Obat keluar dari batch terpilih |
| GET | `/api/stock-movements` | Riwayat transaksi (`?type=in` atau `out`) |
| GET | `/api/dashboard` | Batch hampir kedaluwarsa, kedaluwarsa, dan obat stok menipis |

Kode status: 200 sukses, 201 data dibuat, 401 tanpa token, 404 data tidak ditemukan, 409 obat masih memiliki batch, 422 validasi gagal.

Postman Collection: [backend/postman/klinik-inventaris.postman_collection.json](backend/postman/klinik-inventaris.postman_collection.json). Impor ke Postman, isi variabel `admin_email` dan `admin_password`, lalu jalankan folder secara berurutan (Run collection).

## Pengujian

```powershell
cd backend
composer install
copy .env.example .env
php artisan key:generate
php artisan test

cd ..\frontend
npm ci
npm run build
```

## CI/CD

Workflow `.github/workflows/ci.yml` berjalan pada push ke `main` dan `dev` serta setiap Pull Request:

- `backend-test`: `composer install` lalu `php artisan test`.
- `frontend-build`: `npm ci` lalu `npm run build`.
- `deploy` (hanya push ke `main`): smoke test Docker Compose, lalu build dan push image backend dan frontend ke ghcr.io dengan tag SHA commit.

`APP_KEY` dan `DB_PASSWORD` disimpan di GitHub Secrets, tidak di repository. Perubahan masuk ke `dev` dan `main` hanya lewat Pull Request yang lolos status check.
