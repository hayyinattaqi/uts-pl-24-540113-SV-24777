# Inventaris Stok Obat Klinik

Aplikasi web untuk membantu petugas klinik mencatat stok obat, memantau kedaluwarsa dan stok menipis, serta merekam obat masuk dan keluar.

## Teknologi

- Backend: Laravel dengan PHP 8.3 dan Laravel Sanctum
- Frontend: Vue 3, Vite, Vue Router, dan Axios
- Basis data: MySQL 8.4
- Pengembangan dan deployment: Docker Compose, GitHub Actions, dan GitHub Container Registry

## Ruang Lingkup

Aplikasi direncanakan menyediakan login/logout berbasis token, CRUD obat, pencatatan obat masuk dan keluar, riwayat transaksi, serta dashboard stok dan kedaluwarsa. Rincian kebutuhan dan aturan bisnis ada di [PLAN.md](PLAN.md).

## Struktur Repository

```text
.github/
backend/
frontend/
PLAN.md
README.md
```

## Status Pengerjaan

Fase 0 selesai. Fase 1 menyiapkan kerangka Laravel dan Vue serta workflow CI; fitur aplikasi akan ditambahkan bertahap sesuai PLAN.
