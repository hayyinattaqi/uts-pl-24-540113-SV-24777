# Inventaris Stok Obat Klinik

Aplikasi web untuk membantu petugas klinik mencatat stok obat, memantau kedaluwarsa dan stok menipis, serta merekam obat masuk dan keluar.

## Teknologi

- Backend: Laravel dengan PHP 8.3 dan Laravel Sanctum
- Frontend: Vue 3, Vite, Vue Router, dan Axios
- Basis data: MySQL 8.4
- Pengembangan dan deployment: Docker Compose, GitHub Actions, dan GitHub Container Registry

## Ruang Lingkup

Aplikasi menyediakan login/logout berbasis token, CRUD obat, pencatatan obat masuk dan keluar, riwayat transaksi, serta dashboard stok dan kedaluwarsa. Rincian kebutuhan, aturan bisnis, dan aturan teknis ada di [CLAUDE.md](CLAUDE.md).

## Struktur Repository

```text
.github/workflows/   workflow CI/CD
backend/             API Laravel
frontend/            aplikasi Vue
CLAUDE.md            spesifikasi proyek
README.md
```

## Status Pengerjaan

Backend (auth dan CRUD obat), frontend (login dan CRUD obat), CI, dan Dockerfile sudah tersedia. Docker Compose, deployment ke GHCR, dan fitur stok sedang dikerjakan.
