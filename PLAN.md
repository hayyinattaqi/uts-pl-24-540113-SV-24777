# PLAN: Inventaris Stok Obat Klinik (UTS KEPL)

Aplikasi web untuk petugas klinik: catat stok obat, pantau kedaluwarsa dan stok menipis, rekam obat masuk dan keluar.
Stack: Laravel (PHP 8.3) + Sanctum, Vue 3 + Vite + Vue Router + Axios (JavaScript, tanpa Pinia), MySQL 8.4, Docker Compose, GitHub Actions, GHCR.
Dokumen ini adalah sumber kebenaran. Jangan menambah fitur di luar dokumen ini.

## 1. Ruang lingkup

Wajib:
- Login/logout token Sanctum. Satu user saja (admin pengelola), dibuat lewat seeder dari env.
- CRUD obat.
- Obat masuk (membuat batch), obat keluar (mengurangi stok batch), riwayat transaksi.
- Dashboard: batch hampir kedaluwarsa, batch kedaluwarsa, obat stok menipis.

Tidak dikerjakan: role, kategori, supplier, pagination, ekspor, stock opname, notifikasi, registrasi, soft delete.

## 2. Halaman Vue

Login, Dashboard, Obat (CRUD, modal form), Obat Masuk, Obat Keluar (pilih obat, lalu pilih batch), Riwayat (filter masuk/keluar).

## 3. Database

users: bawaan Laravel (id, name, email unik, password).
medicines: id PK, code varchar(30) unik, name varchar(150), unit varchar(20), min_stock unsigned int default 0, timestamps.
batches: id PK, medicine_id FK medicines.id (RESTRICT), batch_number varchar(50), expiry_date date, initial_quantity unsigned int, current_quantity unsigned int, timestamps. Unik (medicine_id, batch_number).
stock_movements: id PK, batch_id FK batches.id (RESTRICT), user_id FK users.id (RESTRICT), type enum('in','out'), quantity unsigned int (positif), notes varchar(255) nullable, moved_at datetime, timestamps.

Relasi: medicine 1-N batch, batch 1-N movement, user 1-N movement.
Status batch (ok / hampir kedaluwarsa / kedaluwarsa) dihitung dari expiry_date, tidak disimpan.
total_stock obat = jumlah current_quantity batch yang belum kedaluwarsa (dihitung, tidak disimpan).

## 4. Aturan bisnis

- B1 Stok keluar tidak boleh melebihi current_quantity batch -> 422.
- B2 Batch kedaluwarsa (expiry_date < hari ini) tidak boleh dikeluarkan -> 422.
- B3 Obat masuk dengan expiry_date <= hari ini ditolak -> 422.
- B4 batch_number unik per obat; duplikat ditolak -> 422.
- B5 quantity integer >= 1.
- B6 Obat yang sudah punya batch tidak bisa dihapus -> 409.
- B7 Movement tidak bisa diubah atau dihapus (tidak ada endpoint).
- B8 Hampir kedaluwarsa = sisa <= 90 hari; nilai dari config/env (NEAR_EXPIRY_DAYS=90).
- B9 Stok masuk/keluar dalam DB::transaction dengan lockForUpdate pada batch.
- B10 Batch dipilih manual petugas saat obat keluar.
- B11 Stok menipis: obat dengan min_stock > 0 dan total_stock <= min_stock.
- B12 Dashboard hanya memuat batch dengan current_quantity > 0.

## 5. API

Prefix /api. Semua request dan respons JSON. Semua endpoint selain login butuh token (Bearer).
Sukses: { "data": ..., "message": "..." }. Gagal: { "message": "...", "errors": { field: [..] } }.
Hapus mengembalikan 200 + JSON message (bukan 204). Tanpa token harus 401 JSON, bukan redirect.

| Method | Path | Auth | Request | Sukses | Gagal |
|---|---|---|---|---|---|
| POST | /login | tidak | email, password | 200 {token, user} | 422, 401 |
| POST | /logout | ya | - | 200 (token saat ini dihapus) | 401 |
| GET | /medicines | ya | ?search= | 200 daftar + total_stock | 401 |
| GET | /medicines/{id} | ya | - | 200 obat + batch | 401, 404 |
| POST | /medicines | ya | code, name, unit, min_stock? | 201 | 401, 422 |
| PUT | /medicines/{id} | ya | code, name, unit, min_stock? | 200 | 401, 404, 422 |
| DELETE | /medicines/{id} | ya | - | 200 message | 401, 404, 409 |
| POST | /stock-in | ya | medicine_id, batch_number, expiry_date, quantity, notes? | 201 batch + movement | 401, 422 |
| POST | /stock-out | ya | batch_id, quantity, notes? | 201 movement | 401, 404, 422 |
| GET | /stock-movements | ya | ?type=in\|out | 200, terbaru dulu, maks 100 | 401 |
| GET | /dashboard | ya | - | 200 {near_expiry[], expired[], low_stock[]} | 401 |

## 6. Struktur folder

```
uts-pl-NIM/
  .github/{copilot-instructions.md, workflows/ci.yml}
  PLAN.md
  backend/  app/{Http/{Controllers,Requests,Resources},Models,Services/StockService.php}
            database/{migrations,seeders,factories}  routes/api.php  tests/Feature
            Dockerfile  .dockerignore  docker/entrypoint.sh  .env.example
            postman/klinik-inventaris.postman_collection.json
  frontend/ src/{api/http.js,router/index.js,views,components}
            nginx.conf  Dockerfile  .dockerignore  .env.example
  docker-compose.yml  .env.example  .gitignore
```

Di root repo hanya boleh ada folder backend/, frontend/, dan .github/ (soal 3a: repo berisi dua folder kode). Jangan membuat folder lain di root.
Controller tipis. Validasi di FormRequest. Bentuk JSON lewat API Resource. Logika stok hanya di StockService.

## 7. Test

Feature test (RefreshDatabase, SQLite in-memory):
- Auth: login sukses -> token; password salah -> 401; tanpa token -> 401.
- Medicine: tambah 201; kode duplikat 422; ubah 200; hapus obat berbatch 409.
- Stock: masuk menambah stok; keluar mengurangi; keluar melebihi stok 422; keluar dari batch kedaluwarsa 422.
- Dashboard: low_stock, near_expiry, expired terisi benar.

Postman: variabel {{base_url}}, {{token}} (diisi otomatis oleh test script Login). Skenario: login, tanpa token 401, CRUD obat (201 dan 422), stok masuk/keluar (sukses dan 422), riwayat, dashboard, logout lalu token lama 401.

## 8. GitHub dan CI/CD

- Repo publik pribadi bernama uts-pl-NIM. Branch: main, dev, feature/*. Perubahan hanya lewat Pull Request.
  - Satu-satunya commit langsung ke main adalah commit awal yang hanya berisi README, .gitignore, PLAN.md, dan .github/copilot-instructions.md.
  - Skeleton Laravel/Vue dan ci.yml masuk lewat PR feature/setup -> dev, bukan push langsung.
  - Urutan: commit awal -> buat dev -> PR pertama ke dev (workflow berjalan, nama check muncul) -> aktifkan protection main dan dev -> PR dev -> main.
- Branch protection main dan dev: wajib PR, wajib status check (job backend dan frontend) lulus, approval 0 (solo). Aktifkan juga opsi yang melarang bypass (Do not allow bypassing / include administrators) agar pemilik repo pun tidak bisa push langsung.
- .github/workflows/ci.yml, satu file:
  - Trigger: push ke main dan dev, serta pull_request.
  - Job backend-test (working-directory backend): setup PHP 8.3, composer install, salin .env.example ke .env, APP_KEY dari Secrets lewat env, php artisan test (SQLite in-memory).
  - Job frontend-build (working-directory frontend): env VITE_API_BASE_URL=/api, npm ci, npm run build.
  - Job deploy: needs kedua job, hanya jika github.ref == refs/heads/main dan event push. permissions packages: write. Langkah:
    1. Smoke test: buat .env dari Secrets APP_KEY dan DB_PASSWORD (ADMIN_EMAIL, ADMIN_PASSWORD, DB_ROOT_PASSWORD digenerate acak saat runtime dengan openssl rand, tidak ditulis di repo), docker compose up -d --build --wait, curl /api/medicines lewat port frontend harus 401 JSON, lalu docker compose down -v.
    2. Login ghcr.io dengan GITHUB_TOKEN.
    3. Build dan push image backend dan frontend hanya dengan tag ${{ github.sha }}. Tidak boleh ada tag latest; jika memakai docker/metadata-action, set flavor latest=false dan jangan pakai type=raw latest. Nama image huruf kecil.
    Fallback jika smoke test menyita waktu: hapus langkah 1. Secrets tetap dibuat dan APP_KEY tetap dipakai di job backend-test.
- Secrets (GitHub Secrets): APP_KEY, DB_PASSWORD. Tidak boleh ada di repo.
- Dockerfile diberi label org.opencontainers.image.source agar image tampil di Packages repo.

## 9. Docker

backend/Dockerfile:
- Base image berversi jelas (php:8.3-cli-bookworm), bukan latest. Composer dari image composer:2.x yang dipin.
- Salin composer.json dan composer.lock, jalankan composer install --no-dev --no-scripts --no-autoloader, baru salin kode, lalu composer dump-autoload --optimize (cache layer; flag ini mencegah error karena artisan belum ada saat install).
- Tidak ada secret di Dockerfile. entrypoint.sh: migrate --force, seeder idempoten, start server.
- Server di dalam container selalu mendengarkan di port 8000 (artisan serve --host=0.0.0.0 --port=8000; FrankenPHP nanti juga diset ke :8000).
- Awal: php artisan serve. Bonus (fase 6): FrankenPHP berversi jelas.

frontend/Dockerfile (multi-stage): node:22-alpine (npm ci, npm run build, ARG VITE_API_BASE_URL=/api) lalu nginx:1.27-alpine (hanya dist/, tanpa node_modules).
nginx.conf: SPA fallback (try_files) dan reverse proxy /api/ ke http://backend:8000.
Konfigurasi API: VITE_API_BASE_URL=/api di semua lingkungan; dev lokal memakai proxy Vite /api ke localhost:8000. Tidak ada CORS.
Variabel dibaca lewat import.meta.env.VITE_API_BASE_URL tanpa nilai cadangan di komponen. Sumber nilainya: frontend/.env (dev lokal, tidak di-commit, salin dari .env.example), ARG di frontend/Dockerfile, dan env job frontend-build di CI.

docker-compose.yml (di root), 3 service:
- db: mysql:8.4, named volume db_data, healthcheck.
- backend: build ./backend, env_file .env, DB_HOST=db, depends_on db (service_healthy), port dipublikasi untuk Postman.
- frontend: build ./frontend, port 8080:80, depends_on backend.

Env: .env di root (tidak di-commit) berisi DB_DATABASE, DB_USERNAME, DB_PASSWORD, DB_ROOT_PASSWORD, APP_KEY, ADMIN_EMAIL, ADMIN_PASSWORD. .env.example di-commit tanpa nilai rahasia.
Uji data persisten: docker compose down lalu up -d. Jangan pakai down -v.

## 10. Fase pengerjaan

0. Repo, README, .gitignore, berkas ini, commit awal ke main (satu-satunya commit langsung).
1. Buat dev. Lewat PR feature/setup -> dev: Laravel dan Vue kosong + ci.yml hijau. Lalu aktifkan branch protection main dan dev (screenshot). Mulai kerangka laporan.
2. Sanctum, login/logout, CRUD obat (termasuk min_stock), test awal, Vue login dan CRUD obat.
3. Dockerfile (2), .dockerignore, Compose, .env.example, uji down/up.
4. Job deploy ke GHCR, secrets, bukti run main vs dev/PR, halaman Packages.
5. Batch, stok masuk/keluar, riwayat, dashboard, test, Postman final.
6. Bonus FrankenPHP dan bukti log.
7. Finalisasi laporan PDF (kerangka dan screenshot sudah dikumpulkan sejak fase 1). Pekerjaan tanpa laporan tidak dinilai, jadi sisakan waktu cukup.

Setelah fase 4, semua syarat UTS sudah terpenuhi. Fase 5 dan 6 boleh dipotong jika waktu habis.

## 11. Checklist bukti untuk laporan (soal 8)

Laporan PDF berisi langkah pengerjaan dan screenshot tiap soal, tautan repo, Postman Collection, output docker compose ps, penjelasan alur data Vue -> Laravel -> database, serta kendala dan solusinya.

- Soal 1: Postman (login 200, tanpa token 401, tambah obat 201, validasi gagal 422), output php artisan test hijau, file Postman Collection ada di repo.
- Soal 2: halaman login, tabel obat, form tambah/ubah, hapus; potongan kode instance Axios (baseURL dari env) dan frontend/.env.example.
- Soal 3: tautan repo dan tab Actions; daftar branch; daftar PR; screenshot branch protection main dan dev (Settings -> Branches/Rules); run Actions hijau dengan dua job.
- Soal 4: halaman Secrets (hanya nama); run main dengan deploy hijau; run dev atau PR dengan deploy skipped; halaman Packages berisi dua image bertag SHA.
- Soal 5: isi kedua Dockerfile dan .dockerignore; bukti image frontend tanpa node_modules; bukti cache layer (build kedua menampilkan CACHED).
- Soal 6: docker-compose.yml; output docker compose ps; CRUD di browser (login, tampil, tambah, ubah, hapus); data bertahan setelah docker compose down lalu up -d (sebelum dan sesudah); .env.example.
- Soal 7 (bonus): log container backend yang menunjukkan FrankenPHP melayani request API.
- Soal 8: diagram alur data dan paragraf penjelasan; daftar kendala dan solusi (catat saat terjadi, jangan di akhir).
