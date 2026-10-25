# CLAUDE.md: Inventaris Stok Obat Klinik (UTS KEPL SVPL261501)

Berkas ini adalah **sumber kebenaran tunggal** proyek dan dibaca otomatis di setiap sesi.
Urutan prioritas jika ada pertentangan: (1) teks soal UTS di Lampiran A, (2) berkas ini, (3) berkas lama (`PLAN.md`, `.github/copilot-instructions.md`). Jika berkas ini bertentangan dengan soal, ikuti soal dan beri tahu pengguna.
`PLAN.md` dan `.github/copilot-instructions.md` adalah peninggalan agen sebelumnya (GitHub Copilot). Keduanya dihapus di PR pertama supaya tidak ada dua sumber kebenaran.

## 1. Konteks

- Pemilik: Hayyin (Hayyina Attaqiyya), mahasiswa D4 Teknologi Rekayasa Perangkat Lunak, UGM.
- Ujian take-home mata kuliah Konstruksi dan Evolusi Perangkat Lunak. Hasil akhir: laporan PDF di Google Classroom.
- **Batas akhir: Minggu, 25 Oktober 2026, pukul 23:59 WIB.** Gunakan `Get-Date` untuk mengetahui jam sekarang. Zona waktu pengguna WIB (UTC+07:00).
- Pengguna ingin **memahami** pekerjaannya, bukan sekadar menerima kode. Untuk setiap perubahan penting, jelaskan singkat apa dan mengapa. Jangan berpanjang-panjang pada hal sepele.
- Bahasa: komunikasi dengan pengguna dalam Bahasa Indonesia. Identifier, nama berkas, dan pesan commit dalam bahasa Inggris. Teks antarmuka dan pesan error API dalam Bahasa Indonesia.
- Repository: https://github.com/hayyinattaqi/uts-pl-24-540113-SV-24777 (publik, akun pribadi `hayyinattaqi`). Branch: `main`, `dev`, `feature/*`.
- Lingkungan pengguna: Windows, PowerShell, Docker Desktop, VS Code, `gh` CLI. Perintah yang kamu jalankan harus cocok untuk PowerShell. Skrip yang berjalan di dalam container (`sh`) harus berakhiran baris LF, bukan CRLF.
- Pengguna sudah mengerjakan fase 0 sampai 2 (skeleton, CI, auth, CRUD obat, login dan CRUD Vue) dibantu agen lain. **Kondisi sebenarnya repo belum pasti** (misalnya `Dockerfile` ditemukan kosong atau tidak ada). Jangan mengasumsikan; audit dulu.

## 2. Peta kepatuhan terhadap soal (ini ukuran keberhasilan)

| Butir | Syarat inti | Artefak | Cara membuktikan |
|---|---|---|---|
| 1a | Login/logout token; semua endpoint selain login butuh token | `AuthController`, Sanctum, grup `auth:sanctum` | Postman 200 dan 401; feature test |
| 1b | Satu resource CRUD lengkap di basis data | resource `medicines` | Postman, feature test |
| 1c | JSON dengan 201, 422, 401 | FormRequest, handler 401 JSON | Postman |
| 1d | Postman Collection diekspor dan ada di repo | `backend/postman/klinik-inventaris.postman_collection.json` | berkas ada di `main` |
| 1e | Minimal 2 feature test lulus | `backend/tests/Feature/*` | `php artisan test` hijau |
| 2a | Halaman login, simpan token, kirim di tiap request | `frontend/src/api/http.js`, `LoginView.vue` | DevTools: header `Authorization` |
| 2b | Tampil, tambah, ubah, hapus via Axios | `MedicinesView.vue` | screenshot browser |
| 2c | Alamat API tidak ditulis mati | `import.meta.env.VITE_API_BASE_URL` | `Select-String "localhost" frontend/src` kosong |
| 3a | Repo publik `uts-pl-NIM`, folder `backend/` dan `frontend/`, Actions aktif | repo | tautan repo dan tab Actions |
| 3b | Branch `main`, `dev`, min. 1 `feature/*`; semua lewat PR; branch protection di `main` dan `dev` | riwayat PR | daftar PR, screenshot Settings (**manual oleh pengguna**) |
| 3c | `ci.yml` min. 2 job (backend test, frontend build), hijau | `.github/workflows/ci.yml` | run hijau di Actions |
| 4a | Job deploy hanya untuk push ke `main` | kondisi `if` pada job `deploy` | run PR/dev: deploy `skipped` |
| 4b | `APP_KEY` dan `DB_PASSWORD` di GitHub Secrets, bukan di repo | `secrets.*` | halaman Secrets (**manual oleh pengguna**) |
| 4c | Build image backend dan frontend, push ke ghcr.io, tag = SHA commit, bukan `latest` | job `deploy` | halaman Packages |
| 4d | Bukti: run `main` hijau dengan deploy, run `dev`/PR tanpa deploy, halaman Packages dengan kedua image | Actions, Packages | screenshot |
| 5a | `backend/Dockerfile`, base image berversi, `composer.json` + `composer.lock` dipasang sebelum kode disalin | Dockerfile | build kedua menampilkan `CACHED` |
| 5b | `frontend/Dockerfile` multi-stage Node lalu Nginx; tanpa `node_modules` di image akhir | Dockerfile | `find / -name node_modules` di image kosong |
| 5c | `.dockerignore` di kedua folder | berkas | ada |
| 5d | Tidak ada rahasia di Dockerfile | Dockerfile | `Select-String` kosong |
| 6a | `docker-compose.yml` di akar, min. 3 service (DB, backend, frontend), satu perintah `docker compose up -d --build` | compose | `docker compose ps` |
| 6b | Antar-service lewat nama service, bukan IP | `DB_HOST=db`, `proxy_pass http://backend:8000` | konfigurasi |
| 6c | Login, tampil, tambah, ubah, hapus dari Vue di browser berjalan | seluruh sistem | screenshot (**manual oleh pengguna**) |
| 6d | Named volume; data bertahan setelah `down` lalu `up -d` | volume `db_data` | data sama sebelum dan sesudah |
| 6e | Kredensial DB dari `.env` yang tidak ter-commit; ada `.env.example` | `.env`, `.env.example` | `git ls-files` tidak memuat `.env` |
| 7 (bonus +10) | Backend memakai FrankenPHP, base image berversi, tetap di compose, ada bukti log | Dockerfile + Caddyfile | log container |
| 8 | Laporan PDF: langkah, screenshot, tautan repo, Postman Collection, output `docker compose ps`, alur data Vue ke Laravel ke DB, kendala dan solusi | di luar repo | disusun pengguna dari bukti |

## 3. Ruang lingkup produk

Aplikasi web untuk petugas klinik: catat stok obat, pantau kedaluwarsa dan stok menipis, rekam obat masuk dan keluar.

**Wajib:** login/logout (satu user admin dari seeder), CRUD obat, obat masuk (membuat batch), obat keluar (mengurangi stok batch), riwayat transaksi, dashboard (batch hampir kedaluwarsa, batch kedaluwarsa, obat stok menipis).

**Dilarang ditambahkan:** role, kategori, supplier, pagination, ekspor, stock opname, notifikasi, registrasi, soft delete, library UI, Pinia, TypeScript, Redis, Octane.

### 3.1 Basis data

- `users`: bawaan Laravel (`id`, `name`, `email` unik, `password`).
- `medicines`: `id` PK; `code` varchar(30) unik; `name` varchar(150); `unit` varchar(20); `min_stock` unsigned int default 0; timestamps.
- `batches`: `id` PK; `medicine_id` FK `medicines.id` (RESTRICT); `batch_number` varchar(50); `expiry_date` date; `initial_quantity` unsigned int; `current_quantity` unsigned int; timestamps. Unik pada (`medicine_id`, `batch_number`).
- `stock_movements`: `id` PK; `batch_id` FK `batches.id` (RESTRICT); `user_id` FK `users.id` (RESTRICT); `type` enum(`in`,`out`); `quantity` unsigned int (selalu positif); `notes` varchar(255) nullable; `moved_at` datetime; timestamps.
- Relasi: medicine 1-N batch, batch 1-N movement, user 1-N movement.
- Tidak disimpan, dihitung: status batch (`ok`, `near_expiry`, `expired`) dari `expiry_date`; `total_stock` obat.
- `total_stock` = jumlah `current_quantity` dari batch obat itu yang belum kedaluwarsa (`expiry_date` >= hari ini).

### 3.2 Aturan bisnis

- B1: stok keluar tidak boleh melebihi `current_quantity` batch. Respons 422 pada `quantity`, pesan "Jumlah melebihi stok batch (sisa N)".
- B2: batch kedaluwarsa (`expiry_date` < hari ini) tidak boleh dikeluarkan. 422 pada `batch_id`.
- B3: stok masuk dengan `expiry_date` <= hari ini ditolak. 422 pada `expiry_date`.
- B4: `batch_number` unik per obat; duplikat ditolak. 422 pada `batch_number`.
- B5: `quantity` integer >= 1.
- B6: obat yang sudah punya batch tidak bisa dihapus. 409 dengan pesan "Obat tidak dapat dihapus karena masih memiliki batch."
- B7: movement tidak bisa diubah atau dihapus (tidak ada endpoint).
- B8: hampir kedaluwarsa = sisa <= 90 hari (`expiry_date` antara hari ini dan hari ini + 90). Nilai dari `config/inventory.php` yang membaca env `NEAR_EXPIRY_DAYS` (default 90).
- B9: stok masuk dan keluar dijalankan dalam `DB::transaction`; batch dikunci dengan `lockForUpdate`.
- B10: pada obat keluar, petugas memilih batch secara manual (bukan FEFO otomatis).
- B11: stok menipis = obat dengan `min_stock` > 0 dan `total_stock` <= `min_stock`.
- B12: dashboard hanya memuat batch dengan `current_quantity` > 0.
- `stock-out` dengan `batch_id` yang tidak ada: **404** (bukan 422).

### 3.3 API

Prefix `/api`. Semua request dan respons JSON; selalu kirim `Accept: application/json`.
Bentuk sukses: `{ "data": ..., "message": "..." }`. Bentuk gagal: `{ "message": "...", "errors": { "field": ["..."] } }`.
Tanpa token: 401 `{ "message": "Unauthenticated." }` sebagai JSON, **bukan redirect**. Tidak ditemukan: 404 JSON `{ "message": "Data tidak ditemukan." }`.
Hapus mengembalikan **200 + JSON message** (bukan 204).

| Method | Path | Auth | Request | Sukses | Gagal |
|---|---|---|---|---|---|
| POST | /login | tidak | email, password | 200 `{token, user}` | 422, 401 |
| POST | /logout | ya | - | 200, token saat ini dihapus | 401 |
| GET | /medicines | ya | `?search=` pada `name` atau `code` | 200 daftar (+`total_stock`, `is_low_stock` setelah Fase C) | 401 |
| GET | /medicines/{id} | ya | - | 200 obat + `batches` (+`status` per batch) | 401, 404 |
| POST | /medicines | ya | code, name, unit, min_stock? | 201 | 401, 422 |
| PUT | /medicines/{id} | ya | code, name, unit, min_stock? | 200 | 401, 404, 422 |
| DELETE | /medicines/{id} | ya | - | 200 message | 401, 404, 409 (setelah Fase C) |
| POST | /stock-in | ya | medicine_id, batch_number, expiry_date (Y-m-d), quantity, notes? | 201 `{batch, movement}` | 401, 422 |
| POST | /stock-out | ya | batch_id, quantity, notes? | 201 movement (memuat batch) | 401, 404, 422 |
| GET | /stock-movements | ya | `?type=in\|out` | 200, terbaru dulu, maks 100 | 401, 422 (type tidak valid) |
| GET | /dashboard | ya | - | 200 `{near_expiry[], expired[], low_stock[]}` | 401 |

Validasi `medicines`: `code` wajib, maks 30, unik (saat ubah abaikan record sendiri); `name` wajib, maks 150; `unit` wajib, maks 20; `min_stock` opsional, integer >= 0.
Validasi `stock-in`: `medicine_id` wajib dan ada; `batch_number` wajib, maks 50, unik per obat; `expiry_date` wajib, tanggal, setelah hari ini; `quantity` integer >= 1; `notes` opsional, maks 255.
Validasi `stock-out`: `batch_id` wajib integer (jika tidak ada di basis data: 404); `quantity` integer >= 1; `notes` opsional, maks 255.
Movement dibuat dengan `user_id` = pengguna yang login dan `moved_at` = sekarang.
Boleh menentukan nama field respons yang persis selama konsisten, tidak mengubah status code di tabel, dan tercermin di Postman Collection.

### 3.4 Halaman Vue

Rute: `/login`, `/` Dashboard, `/medicines` Obat (CRUD, form modal), `/stock-in` Obat Masuk, `/stock-out` Obat Keluar (pilih obat, lalu pilih batch yang belum kedaluwarsa dan stoknya > 0), `/history` Riwayat (filter semua/masuk/keluar). Guard: tanpa token menuju `/login`.
Token disimpan di `localStorage`. Satu instance Axios di `src/api/http.js` dengan `baseURL = import.meta.env.VITE_API_BASE_URL` (tanpa nilai cadangan tertulis di komponen), interceptor menambah `Authorization: Bearer <token>`, dan 401 (selain pada `/login`) menghapus token lalu menuju `/login`.

## 4. Struktur repository

Di akar hanya boleh ada folder `backend/`, `frontend/`, `.github/` dan berkas `CLAUDE.md`, `README.md`, `.gitignore`, `docker-compose.yml`, `.env.example`. **Jangan membuat folder lain di akar.**

```
.github/workflows/ci.yml
backend/   app/{Http/{Controllers,Requests,Resources},Models,Services/StockService.php}
           config/inventory.php  database/{migrations,seeders,factories}  routes/api.php  tests/Feature
           Dockerfile  .dockerignore  docker/entrypoint.sh  .env.example
           postman/klinik-inventaris.postman_collection.json
frontend/  src/{api/http.js,router/index.js,views,components}
           nginx.conf  Dockerfile  .dockerignore  .env.example
docker-compose.yml  .env.example  .gitignore  README.md  CLAUDE.md
```

Backend: controller tipis; validasi di FormRequest; bentuk JSON lewat API Resource; seluruh logika stok hanya di `app/Services/StockService.php`.

## 5. Aturan teknis

### 5.1 Backend
- Laravel (PHP 8.3), Sanctum. Feature test memakai `RefreshDatabase` dengan SQLite in-memory. `php artisan test` harus hijau sebelum fase dinyatakan selesai.
- Seeder admin idempoten dari env `ADMIN_EMAIL` dan `ADMIN_PASSWORD`; dilewati dengan peringatan jika kosong.
- Laravel 12 secara bawaan memakai SQLite dan driver `database` untuk session/cache. Di container, nilai ini ditimpa lewat environment Compose (lihat 5.4).

### 5.2 Docker
- `backend/Dockerfile`: base `php:8.3-cli-bookworm` (versi jelas, bukan `latest`); Composer dari `composer:2.8`; pasang `git`, `unzip`, `libzip-dev`, ekstensi `pdo_mysql` dan `zip`. Urutan layer: `COPY composer.json composer.lock ./`, `composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction`, **baru** `COPY . .`, lalu `composer dump-autoload --optimize --no-dev`. Buat dan beri izin tulis `storage/` serta `bootstrap/cache`. Hapus CR dari `docker/entrypoint.sh` dengan `sed -i 's/\r$//'` lalu `chmod +x`. `LABEL org.opencontainers.image.source="https://github.com/hayyinattaqi/uts-pl-24-540113-SV-24777"`. `EXPOSE 8000`. Tidak ada rahasia di dalamnya.
- `backend/docker/entrypoint.sh` (`sh`, `set -e`): `php artisan migrate --force` dengan percobaan ulang maks 30 kali jeda 2 detik; `php artisan db:seed --class=AdminSeeder --force`; lalu `exec php artisan serve --host=0.0.0.0 --port=8000`. Server di dalam container selalu di port 8000.
- `frontend/Dockerfile`: tahap build `node:22-alpine` (`npm ci`, `ARG VITE_API_BASE_URL=/api`, `npm run build`); tahap akhir `nginx:1.27-alpine` hanya berisi `dist/` dan `nginx.conf`. Tanpa `node_modules` di image akhir. `LABEL org.opencontainers.image.source` sama seperti backend.
- `frontend/nginx.conf`: `listen 80`; `location /api/` melakukan `proxy_pass http://backend:8000` dengan header `Host`, `X-Real-IP`, `X-Forwarded-For`, `X-Forwarded-Proto`; `location /` memakai `try_files $uri $uri/ /index.html`.
- `.dockerignore` backend: `.git`, `.env`, `.env.*`, `vendor`, `node_modules`, `tests`, `postman`, isi `storage/logs`, `storage/framework/{cache,sessions,views}`. Frontend: `node_modules`, `dist`, `.git`, `.env`, `.env.*`. **Jika `.env` ikut masuk image, `php artisan serve` mengabaikan environment Compose**, jadi pengecualian ini penting.
- Dilarang: tag `latest` di mana pun (base image maupun image yang di-push), rahasia di Dockerfile atau Compose.

### 5.3 Docker Compose (`docker-compose.yml` di akar, tiga service, satu named volume)
- `db`: `mysql:8.4`; environment `MYSQL_DATABASE=${DB_DATABASE}`, `MYSQL_USER=${DB_USERNAME}`, `MYSQL_PASSWORD=${DB_PASSWORD}`, `MYSQL_ROOT_PASSWORD=${DB_ROOT_PASSWORD}`; volume `db_data:/var/lib/mysql`; healthcheck `CMD-SHELL` `mysqladmin ping -h 127.0.0.1 -u root -p"$$MYSQL_ROOT_PASSWORD"` (interval 5s, timeout 5s, retries 20, start_period 20s). Port MySQL tidak dipublikasikan.
- `backend`: `build: ./backend`; `env_file: .env`; environment tambahan `DB_CONNECTION=mysql`, `DB_HOST=db`, `DB_PORT=3306`, `APP_ENV=production`, `APP_DEBUG=false`, `CACHE_STORE=file`, `SESSION_DRIVER=file`, `QUEUE_CONNECTION=sync`; `depends_on` `db` dengan `service_healthy`; port `8000:8000` (untuk Postman). Boleh menambah healthcheck ke `/up` (rute kesehatan bawaan Laravel 12) selama `curl` tersedia di image; verifikasi.
- `frontend`: `build` context `./frontend` dengan arg `VITE_API_BASE_URL=/api`; port `8080:80`; `depends_on` `backend` (lebih baik `service_healthy` jika healthcheck backend ada).
- `volumes: db_data`.
- `.env` di akar (tidak di-commit) memuat `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`, `DB_ROOT_PASSWORD`, `APP_KEY`, `ADMIN_EMAIL`, `ADMIN_PASSWORD`. Nilai sandi hanya huruf dan angka (tanda `$` dirusak oleh interpolasi Compose). `APP_KEY` berformat `base64:...`.
- `.env.example` di akar: kunci yang sama, nilai rahasia **dikosongkan**, nilai non-rahasia berupa contoh jelas (misalnya `DB_DATABASE=clinic_inventory`, `DB_USERNAME=clinic`), plus komentar cara mengisi.
- Uji persistensi dengan `docker compose down` lalu `docker compose up -d`. **Jangan memakai `down -v`** kecuali untuk uji bersih akhir yang disengaja (lihat master prompt).

### 5.4 GitHub Actions (`.github/workflows/ci.yml`, satu berkas)
- Trigger: `push` ke `main` dan `dev`, serta `pull_request`. Izin default `contents: read`.
- Job `backend-test` (working-directory `backend`): `shivammathur/setup-php@v2` PHP 8.3 dengan ekstensi `mbstring, sqlite3, pdo_sqlite`; `composer install --prefer-dist --no-interaction`; `cp .env.example .env`; satu step test: `env: APP_KEY: ${{ secrets.APP_KEY }}`, jika kosong buat key sementara dengan `php artisan key:generate --show`, lalu `php artisan test`. **Hanya boleh ada satu step test.** Key tidak boleh dicetak ke log.
- Job `frontend-build` (working-directory `frontend`): `actions/setup-node@v4` Node 22 dengan cache npm; env `VITE_API_BASE_URL=/api`; `npm ci`; `npm run build`.
- Job `deploy`: `needs: [backend-test, frontend-build]`; `if: github.event_name == 'push' && github.ref == 'refs/heads/main'`; `permissions: contents: read, packages: write`. Langkah:
  1. Smoke test (boleh dihapus jika terbukti menyita waktu; jika dihapus, tetap pakai `secrets.APP_KEY` di `backend-test`): buat `.env` untuk Compose dari `secrets.APP_KEY` dan `secrets.DB_PASSWORD`, sedangkan `DB_ROOT_PASSWORD`, `ADMIN_EMAIL`, `ADMIN_PASSWORD` dibangkitkan acak saat runtime (`openssl rand`) dan tidak pernah ditulis di repo; `docker compose up -d --build --wait`; `GET http://localhost:8080/api/medicines` dengan `Accept: application/json` harus 401; `docker compose down -v` (selalu dijalankan, `if: always()`).
  2. `docker/login-action` ke `ghcr.io` dengan `github.actor` dan `secrets.GITHUB_TOKEN`.
  3. Nama image **huruf kecil semua**. Nama repo mengandung huruf besar (`SV`), jadi turunkan dengan `${GITHUB_REPOSITORY,,}`. Contoh: `ghcr.io/hayyinattaqi/uts-pl-24-540113-sv-24777-backend:${{ github.sha }}` dan `...-frontend:${{ github.sha }}`.
  4. `docker/build-push-action` untuk backend (context `./backend`) dan frontend (context `./frontend`, build-arg `VITE_API_BASE_URL=/api`), `push: true`, `provenance: false` agar halaman Packages bersih. **Tag hanya `${{ github.sha }}`**; jika memakai `docker/metadata-action`, set `flavor: latest=false`.
- Pin versi semua action (misalnya `@v3`, `@v5`, `@v6`), bukan `@main`.
- `APP_KEY` dan `DB_PASSWORD` adalah **GitHub Secrets yang dibuat pengguna secara manual**. Kamu tidak boleh membuat, membaca, atau menampilkan nilainya.

### 5.5 Bonus FrankenPHP (soal 7)
- Ganti base image backend menjadi `dunglas/frankenphp:<tag>-php8.3-bookworm`. Pilih tag stabil terbaru yang berformat versi eksplisit (bukan `latest`), verifikasi keberadaannya dengan `docker manifest inspect` atau `docker pull`, dan catat tag-nya untuk laporan.
- Mode klasik (tanpa Octane). Ekstensi lewat `install-php-extensions pdo_mysql zip`. `backend/docker/Caddyfile`: blok global `frankenphp`, `auto_https off`; situs `:8000` dengan `root * /app/public`, `encode zstd gzip`, `log`, dan `php_server`. Sesuaikan sintaksis dengan dokumentasi versi yang dipakai.
- `entrypoint.sh` tetap menjalankan migrasi dan seeder, lalu `exec frankenphp run --config /etc/caddy/Caddyfile` (path sesuai tempat Caddyfile disalin). Tetap di port 8000 sehingga `nginx.conf` dan Compose tidak berubah.
- Bukti: `docker compose logs backend` memuat baris startup FrankenPHP/Caddy dan log akses untuk `/api/...`; `curl -i http://localhost:8000/up` menampilkan `Server: Caddy`; `docker compose exec backend frankenphp version`.
- Jika bonus gagal diverifikasi, jangan di-merge; backend tetap memakai `php artisan serve`.

## 6. Alur Git (wajib)

- **Dilarang** `git push` langsung ke `main` atau `dev`, `git push --force`, `gh pr merge`, mengubah atau menonaktifkan branch protection, serta mengubah pengaturan repo lewat `gh api`. **Pengguna yang menekan tombol merge.** Gunakan "Create a merge commit", bukan squash.
- Satu pekerjaan = satu branch `feature/<deskripsi>` yang dibuat dari `dev` terbaru. Push branch tersebut, lalu `gh pr create --base dev`. PR dari `dev` ke `main` dibuat hanya pada gerbang yang menyebutkannya.
- Pesan commit: `type(scope): ringkasan` dalam bahasa Inggris, huruf kecil, tanpa titik (`feat`, `fix`, `chore`, `docs`, `test`, `ci`). Satu commit untuk satu hal.
- Judul PR dalam Bahasa Indonesia dengan awalan `type:`. Isi PR: Ringkasan, Perubahan, Verifikasi (perintah yang dijalankan beserta hasilnya).
- Sebelum setiap commit: `git status` dan pastikan `.env`, `vendor/`, `node_modules/`, `dist/` tidak ikut. Cek `git ls-files` untuk `.env`; hanya `.env.example` yang boleh ada.
- Saat PR terbuka, tunggu check `backend-test` dan `frontend-build` hijau (`gh pr checks`). Beri tahu pengguna agar merge.

## 7. Perilaku agen

1. **Verifikasi sebelum mengklaim.** Jangan menyatakan "selesai" tanpa menjalankan perintah verifikasinya (`php artisan test`, `npm run build`, `docker compose ...`, `gh pr checks`) dan menunjukkan hasilnya. Berkas berukuran 0 atau 2 byte bukan "sudah dibuat".
2. **Tanya, jangan menebak.** Jika ada keputusan desain yang tidak tertulis di sini atau bertentangan dengan soal, berhenti dan tanyakan. Jangan mengubah spesifikasi diam-diam.
3. **Patuhi lingkup.** Kerjakan hanya fase yang sedang berjalan. Jangan menambah fitur, library, tabel, endpoint, atau folder di akar.
4. **Rahasia.** Jangan menampilkan isi `.env`, `APP_KEY`, kata sandi, atau token di keluaran, log, atau commit. Jangan `cat .env`. Boleh membuat `.env` lokal dengan nilai acak tanpa menampilkannya, atau meminta pengguna mengisinya.
5. **Jujur soal kegagalan.** Laporkan error apa adanya beserta penyebab dan langkah perbaikan. Jangan menutupi dengan solusi sementara tanpa memberi tahu.
6. **`main` harus selalu hijau.** Jangan menyarankan merge pekerjaan setengah jadi ke `dev` atau `main`.
7. **Catat bukti.** Simpan keluaran perintah penting sebagai berkas teks di folder `..\bukti-uts\` (di luar repo, sejajar dengan folder repository), supaya pengguna dapat menempelkannya ke laporan.
8. **Hormati batas waktu.** Ikuti jadwal dan aturan pemangkasan di master prompt.

## 8. Yang hanya bisa dilakukan pengguna (beri tahu kapan, jangan coba lakukan sendiri)

- Mengisi GitHub Secrets `APP_KEY` dan `DB_PASSWORD` (Settings, Secrets and variables, Actions).
- Memeriksa dan men-screenshot pengaturan branch protection `main` dan `dev`.
- Menekan tombol merge PR.
- Mengambil screenshot browser (login, CRUD, halaman Actions, halaman Packages).
- Mengatur visibilitas package di GHCR jika dosen perlu mengaksesnya.
- Menyusun dan mengekspor laporan PDF, lalu mengunggah ke Google Classroom.

---

## Lampiran A: Teks soal UTS (asli, tidak diubah)

### FORMULIR SOAL UJIAN TENGAH SEMESTER, TAHUN AKADEMIK GASAL 2026/2027

MATA UJIAN: Konstruksi dan Evolusi Perangkat Lunak KODE MK: SVPL261501 SKS: 2 PRODI: D4 Teknologi Rekayasa Perangkat Lunak TANGGAL UJIAN: Kamis, 15-10-2026 WAKTU: 08:00-09:30 (Dikerjakan sampai dengan 10 hari) SIFAT UJIAN: Take Home DOSEN PENGAMPU: Galih Malela Damaraji, S.Pd., M.Eng

**Prosedur Ujian**
- Ujian dilaksanakan secara take-home dan dikerjakan secara mandiri. Waktu pengerjaan adalah 10 hari terhitung sejak tanggal pelaksanaan ujian.
- Jawaban ujian disusun dalam bentuk laporan dan dikumpulkan dalam format PDF melalui Google Classroom.

**1. Materi Uji CPMK1 (Nilai 15)**
Buatlah backend berbasis Laravel dengan tema bebas (tidak boleh sama dengan tugas sebelumnya) yang menyediakan RESTful API, dengan ketentuan sebagai berikut:
a. Login dan logout memakai token (misalnya Laravel Sanctum). Seluruh endpoint selain login hanya dapat diakses dengan token.
b. Minimal satu resource dengan CRUD lengkap (tampil semua, tampil satu, tambah, ubah, hapus) yang datanya tersimpan di basis data.
c. Seluruh permintaan dan balasan berformat JSON, dengan kode status HTTP yang sesuai: 201 saat data dibuat, 422 saat validasi gagal, dan 401 saat token tidak ada.
d. Seluruh endpoint dapat diuji dengan Postman. Ekspor Postman Collection-nya dan simpan di dalam repository.
e. Minimal dua feature test untuk endpoint API yang lulus saat php artisan test dijalankan.

**2. Materi Uji CPMK1 (Nilai 10)**
Buatlah frontend Vue.js yang memakai API dari soal nomor 1:
a. Halaman login yang menyimpan token, lalu mengirimkannya pada setiap permintaan berikutnya.
b. Halaman untuk menampilkan, menambah, mengubah, dan menghapus data melalui Axios.
c. Alamat API tidak ditulis mati di dalam kode komponen, melainkan diatur lewat konfigurasi atau variabel lingkungan.

**3. Materi Uji CPMK2 (Nilai 15)**
Kelola seluruh kode sistem di GitHub dengan ketentuan sebagai berikut:
a. Satu repository publik bernama uts-pl-NIM, boleh di organization KEPL2026 maupun di akun GitHub pribadi, berisi dua folder: backend/ untuk Laravel dan frontend/ untuk Vue. GitHub Actions harus aktif dan bisa berjalan di repository tersebut, dan tautannya dicantumkan di laporan.
b. Buat branch main, dev, dan minimal satu branch fitur. Perubahan masuk ke dev dan main melalui Pull Request, tidak boleh push langsung. Pasang branch protection pada main dan dev yang mewajibkan Pull Request dan status check lulus sebelum merge, lalu sertakan screenshot halaman pengaturannya (Settings → Branches atau Settings → Rules) di laporan.
c. Buat berkas .github/workflows/ci.yml dengan minimal dua job: job pengujian backend yang menjalankan php artisan test, dan job build frontend yang menjalankan npm ci dan npm run build. Workflow harus berjalan sampai hijau di tab Actions.

**4. Materi Uji CPMK3 (Nilai 15)**
Terapkan continuous deployment dengan menambahkan job deploy pada workflow GitHub Actions:
a. Job deploy hanya berjalan untuk push ke branch main. Workflow pada Pull Request, branch dev, dan branch fitur berhenti setelah job pengujian dan build.
b. Nilai rahasia seperti APP_KEY dan DB_PASSWORD disimpan di GitHub Secrets (Settings → Secrets and variables → Actions) dan tidak boleh ada di dalam repository.
c. Job deploy membangun image Docker backend dan frontend dari soal nomor 5, lalu mengunggahnya ke GitHub Container Registry (ghcr.io). Tag image memakai nomor commit, bukan latest.
d. Sertakan bukti: run workflow di main dengan job deploy yang hijau, run workflow dari dev atau Pull Request yang tidak menjalankan deploy, dan halaman Packages yang berisi kedua image.

**5. Materi Uji CPMK4 dan CPMK5 (Nilai 20)**
Buatlah Dockerfile untuk backend dan frontend:
a. backend/Dockerfile untuk Laravel. Image dasar memakai versi yang jelas (bukan latest). composer.json dan composer.lock disalin dan dipasang sebelum kode aplikasi disalin, supaya cache lapisan bekerja.
b. frontend/Dockerfile untuk Vue memakai multi-stage build: tahap pertama membangun aplikasi dengan Node.js, tahap kedua menyajikan hasil build dengan Nginx. Image akhir tidak boleh berisi node_modules.
c. Berkas .dockerignore di masing-masing folder.
d. Tidak ada nilai rahasia (password, key, token) yang ditulis di dalam Dockerfile.

**6. Materi Uji CPMK6 dan CPMK7 (Nilai 25)**
Buatlah berkas docker-compose.yml di akar repository yang menjalankan seluruh sistem dengan satu perintah docker compose up -d --build:
a. Minimal tiga service: basis data (MySQL atau PostgreSQL), backend Laravel, dan frontend Vue.
b. Backend terhubung ke basis data, dan frontend terhubung ke backend, melalui nama service di jaringan Docker Compose, bukan melalui alamat IP laptop.
c. Login, tampil, tambah, ubah, dan hapus data dari halaman Vue di browser benar-benar berjalan: data yang tampil berasal dari Laravel di container lain dan tersimpan di basis data.
d. Data basis data disimpan di named volume. Setelah docker compose down lalu docker compose up -d, data yang sudah dibuat tidak hilang.
e. Kredensial basis data dibaca dari berkas .env yang tidak ikut di-commit. Sertakan .env.example sebagai contoh.

**7. Nilai Tambahan (+10, di luar nilai 100)**
Jalankan backend Laravel memakai FrankenPHP sebagai server aplikasinya, menggantikan php artisan serve atau PHP-FPM dengan Nginx. Image dasar tetap memakai versi yang jelas, bukan latest, dan backend tetap berjalan bersama service lain di docker-compose.yml. Sertakan bukti di laporan bahwa permintaan ke API benar-benar dilayani oleh FrankenPHP, misalnya dari log container backend.

**8. Laporan**
Buatlah laporan dalam format PDF dan unggah ke Google Classroom. Laporan berisi langkah pengerjaan dan screenshot tiap soal, tautan repository GitHub, Postman Collection, output docker compose ps, serta penjelasan singkat bagaimana data mengalir dari Vue ke Laravel lalu ke basis data. Ceritakan juga kendala yang kalian temui dan cara mengatasinya. Pekerjaan tanpa laporan tidak dinilai.
