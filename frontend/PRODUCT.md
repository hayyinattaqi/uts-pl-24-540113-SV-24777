# Product

<!-- impeccable:product-schema 1 -->

## Platform

web

## Users

Petugas klinik (satu akun admin dari seeder) yang mencatat stok obat di meja apotek/gudang obat klinik. Pekerjaannya: mencatat obat masuk per batch, mengeluarkan obat dari batch yang dipilih manual, dan memeriksa batch yang hampir/sudah kedaluwarsa serta obat yang stoknya menipis. Pembaca kedua: dosen penguji UTS yang menilai aplikasi dari screenshot dan dari menjalankan `docker compose up`.

## Product Purpose

Aplikasi web inventaris stok obat klinik. Berhasil jika petugas bisa melihat dalam sekali pandang batch mana yang perlu ditindaklanjuti, dan mencatat transaksi stok tanpa salah batch atau salah jumlah.

## Positioning

Stok dihitung per batch dengan tanggal kedaluwarsa; status batch (aman, hampir kedaluwarsa ≤ 90 hari, kedaluwarsa) dan total stok dihitung, bukan diketik. Petugas memilih batch secara manual saat obat keluar.

## Operating Context

Dipakai di layar desktop/laptop klinik, sesekali di ponsel. Rute: `/login`, `/` Dashboard, `/medicines` Obat (CRUD, form modal), `/stock-in` Obat Masuk, `/stock-out` Obat Keluar, `/history` Riwayat (filter semua/masuk/keluar).

## Capabilities and Constraints

- Vue 3 + Vue Router + Axios, JavaScript (bukan TypeScript). Dilarang: library UI, Pinia, library ikon, fitur di luar lingkup (role, kategori, supplier, pagination, ekspor, notifikasi, registrasi).
- Seluruh teks antarmuka dalam Bahasa Indonesia.
- Alamat API dari `import.meta.env.VITE_API_BASE_URL`; tidak boleh ada `localhost` tertulis di `src/`.
- Di-build di Docker (`node:22-alpine` lalu `nginx:1.27-alpine`); aset harus berada di dalam proyek (tanpa ketergantungan CDN saat runtime).

## Brand Commitments

- Nama tampilan: "Inventaris Klinik".
- Warna utama hijau (permintaan pengguna, mengikat).
- Desain bersih (clean), tidak terlihat seperti template generik buatan AI.
- Aset yang diminta: logo/wordmark klinik (SVG) untuk navbar, halaman login, dan favicon.

## Evidence on Hand

Belum ada logo, foto, atau ilustrasi resmi. Data obat berasal dari basis data saat runtime; jangan mengarang nama klinik, angka, atau klaim.

## Product Principles

1. Status stok harus terbaca lebih dulu daripada dekorasi.
2. Angka (stok, jumlah, sisa hari) rata kanan dan sejajar agar mudah dibandingkan.
3. Satu tindakan utama per layar; tindakan berisiko (hapus, keluar stok) jelas tetapi tidak mencolok.
4. Kesalahan validasi dijelaskan di kolom yang bersangkutan dengan bahasa petugas.

## Accessibility & Inclusion

Target WCAG 2.1 AA: kontras teks, fokus keyboard terlihat, label pada setiap kolom, status tidak hanya dibedakan dengan warna.
