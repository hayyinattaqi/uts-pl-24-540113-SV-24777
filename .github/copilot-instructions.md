# Instruksi untuk Copilot

Proyek: aplikasi inventaris stok obat klinik untuk UTS mata kuliah Konstruksi dan Evolusi Perangkat Lunak.
Spesifikasi lengkap dan final ada di [PLAN.md](../PLAN.md). Baca dokumen itu sebelum mengerjakan apa pun.

## Aturan kerja
- Kerjakan hanya fase atau tugas yang diminta. Jangan menambah fitur, tabel, endpoint, atau library di luar PLAN.md.
- Jika ada hal yang tidak jelas atau bertentangan dengan PLAN.md, tanyakan dulu, jangan menebak.
- Ubah sesedikit mungkin file. Jangan menyentuh folder lain di luar lingkup tugas.
- Di root repo hanya boleh ada folder backend/, frontend/, dan .github/. Jangan membuat folder lain di root.
- Setelah selesai, tampilkan daftar file yang berubah, jelaskan alasan tiap perubahan singkat, dan sebutkan perintah untuk memverifikasi (misalnya php artisan test atau npm run build).
- Jangan menjalankan git push, merge, atau mengubah branch main dan dev. Commit dan Pull Request dilakukan manual oleh pengguna.

## Stack dan gaya kode
- Backend: Laravel, PHP 8.3, Sanctum. Frontend: Vue 3 (script setup), Vite, Vue Router, Axios, JavaScript (bukan TypeScript), tanpa Pinia, tanpa nilai cadangan yang ditulis di komponen; nilainya diisi oleh frontend/.env (lokal), ARG di Dockerfile, dan env job CI.
- Identifier dan nama file dalam bahasa Inggris. Pesan error dan teks antarmuka dalam bahasa Indonesia.
- Backend: controller tipis, validasi di FormRequest, bentuk JSON lewat API Resource, logika stok hanya di app/Services/StockService.php (DB::transaction + lockForUpdate).
- Semua request dan respons API berformat JSON dengan status yang sesuai: 201 saat dibuat, 422 validasi gagal, 401 tanpa token, 404 tidak ditemukan, 409 konflik. Endpoint hapus mengembalikan 200 + JSON message. Request tanpa token ke /api/* harus 401 JSON, bukan redirect.
- Frontend: alamat API tidak boleh ditulis mati di komponen. Gunakan import.meta.env.VITE_API_BASE_URL (nilainya /api).

## Keamanan dan persyaratan UTS (jangan dilanggar)
- Jangan pernah menulis password, APP_KEY, token, atau kredensial di kode, Dockerfile, docker-compose.yml, atau workflow. Baca dari environment atau GitHub Secrets.
- Jangan commit file .env. Pastikan .gitignore dan .dockerignore memuatnya.
- Jangan memakai tag latest untuk image Docker (base image maupun image yang dipublikasikan). Pakai versi yang jelas atau SHA commit. Jika memakai docker/metadata-action, set flavor latest=false; image yang di-push hanya bertag ${{ github.sha }}.
- Dockerfile backend: salin composer.json dan composer.lock lalu jalankan composer install --no-dev --no-scripts --no-autoloader sebelum menyalin kode aplikasi, kemudian composer dump-autoload --optimize setelah kode disalin.
- Dockerfile frontend: multi-stage (Node untuk build, Nginx untuk menyajikan); image akhir tidak berisi node_modules.
- Job deploy di GitHub Actions hanya berjalan untuk push ke branch main.
- Backend dan frontend saling terhubung lewat nama service di jaringan Docker Compose (db, backend), bukan IP. Backend mendengarkan di port 8000 di dalam jaringan; Nginx frontend meneruskan /api/ ke http://backend:8000.

## Pengujian
- Feature test memakai RefreshDatabase dengan SQLite in-memory. Semua test harus lulus dengan php artisan test sebelum tugas dianggap selesai.
