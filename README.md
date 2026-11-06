# 🛍️ Goddess Club Store

[![Build Status](https://img.shields.io/badge/build-passing-brightgreen)](https://github.com)
[![License](https://img.shields.io/badge/license-MIT-blue)](LICENSE)
[![Version](https://img.shields.io/badge/version-1.0.0-orange)](https://github.com)
[![Docker](https://img.shields.io/badge/Docker-ready-2496ED)](https://www.docker.com)

---

## 📖 Deskripsi Singkat

**Goddess Club Store** adalah toko merch e-commerce resmi komunitas Goddess Club yang menjual produk berlimittahunan: apparel, musik & vinyl, serta aksesoris. Aplikasi ini dibangun dengan **Laravel 13 + Docker** menyediakan pengalaman pembelian yang modern, gelap (dark theme), dan responsif — sejalan dengan desain Figma yang telah ditetapkan.

Proyek ini menyelesaikan masalah kebutuhan toko merch yang memiliki katalog produk, keranjang belanja, pemilihan ukuran, dan simulasi checkout DOKU tanpa perlu backend pembayaran nyata.

---

## ✨ Fitur Utama

- 🌙 **Dark Theme** — Tampilan hitam/cream yang nyaman mata, sesuai desain Figma
- 📦 **Database-Backed Products** — 4 produk (Apparel, Music, Accessories) dari MySQL
- 🗂️ **Kategori Filter** — Filter produk berdasarkan Apparel, Music & vinyl, Accessories
- 👕 **Pemilihan Ukuran** — Tombol S, M, L, XL, XXL dengan state `.selected`
- 📏 **Size Guide Modal** — Tabel lengkap ukuran S–XXL (chest, length, shoulder)
- 🖼️ **Produk Detail** — Panel full-screen dengan gambar gallery, thumbnails, dan info produk
- 🛒 **Cart Drawer** — Keranjang slide-out dengan promo code (CLUB10), subtotal, dan checkout simulasi
- 💳 **Checkout Modal** — Form email + alamat + metode pembayaran DOKU (sandbox) simulasi
- 📱 **Responsive Design** — Mobile-first, breakpoint di 760px, navigasi drawer di mobile
- ⚡ **Interaksi JavaScript** — Quick add, size selection, quantity control, dismiss modal via Escape key
- 🎨 **UX Polish** — Hover effect, focus-visible, reduced-motion support, smooth scroll

---

## 🛠️ Tech Stack

| Kategori | Teknologi |
|----------|-----------|
| **Framework** | Laravel 13.37 |
| **Backend** | PHP 8.4 CLI |
| **Database** | MySQL 8.4 |
| **Cache/Queue** | Redis 7 Alpine |
| **Mail** | Mailpit (development) |
| **Container** | Docker + Docker Compose |
| **Frontend** | Blade HTML, Plain JavaScript, Layered CSS |
| **Fonts** | Google Fonts: DM Mono, Space Grotesk, Georgia |
| **Theme** | Dark mode (#11100f / #f4eee2 / #e62927) |

---

## 📋 Prasyarat (Prerequisites)

Sebelum menjalankan proyek, pastikan telah terinstall:

- [Docker](https://www.docker.com/get-started) (versi terbaru)
- [Docker Compose](https://docs.docker.com/compose/install/)
- [Git](https://git-scm.com/downloads)

---

## 🚀 Instalasi & Setup

Ikuti langkah-langkah berikut untuk menjalankan proyek di lokal:

```bash
# 1. Clone repository
git clone https://github.com/Psr354/Goddes-club-store.git
cd Goddes-club-store

# 2. Salin file environment
cp .env.example .env

# 3. Generate APP_KEY
php artisan key:generate

# 4. Jalankan Docker Compose (MySQL, Redis, Mailpit, App)
docker compose up -d

# 5. Tunggu layanan siap, lalu jalankan migrasi & seed
docker compose exec app php artisan migrate:refresh --seed

# 6. Build assets (jika ada) dan jalankan server
docker compose exec app php artisan serve --host=0.0.0.0 --port=8000
```

Akses aplikasi di: **http://localhost:8000**

> 📝 **Catatan:** Port 8000 adalah port utama aplikasi. Jangan gunakan port 8001, 8010, atau 8011 yang digunakan oleh layanan lama.

---

## 📖 Cara Penggunaan

### Halaman Utama

- Browse produk melalui kategori **Apparel**, **Music & vinyl**, atau **Accessories** di navbar
- Klik produk untuk melihat detail di panel full-screen
- Pilih ukuran dengan menekan tombol size (S/M/L/XL/XXL), tombol akan berwarna aktif
- Klik **Quick Add** untuk menambah ke keranjang (dengan ukuran yang dipilih)
- Buka keranjang dengan tombol **Bag** di header

### Size Guide

- Di halaman detail produk, klik tombol **Size guide ↗**
- Modal akan muncul dengan tabel ukuran lengkap (S–XXL)
- Tutup modal dengan menekan tombol **×** atau tombol **Escape**

### Checkout Simulasi

- Masuk ke keranjang, klik **Proceed to checkout**
- Isi form email dan alamat pengiriman
- Klik **Place order** untuk melihat toast "Order received. DOKU payment link is ready."

---

## 📁 Struktur Folder (Opsional)

```
goddess-club-store/
├── app/                  # Laravel App Models & Http
├── bootstrap/            # Application bootstrap files
├── config/               # Konfigurasi Laravel
├── database/             # Migrasi & Seeder
├── docs/                 # Dokumentasi diagram, arsitektur, dan prototipe
│   ├── architecture/     # Dokumen spesifikasi arsitektur
│   ├── design/           # Berkas desain aset
│   ├── diagrams/         # Flowchart dan ERD
│   └── prototype/        # Prototipe statis awal (HTML, CSS, JS)
├── public/               # Asset publik (CSS, JS, images)
│   ├── store.css         # Base styles
│   ├── figma.css         # Figma visual layer
│   ├── dark-theme.css    # Dark theme conversion
│   ├── hero-brand.css    # Reality Club emblem typography
│   ├── detail-features.css # Product detail & size guide
│   ├── ux-polish.css     # Hover, focus, transition
│   └── store.js          # Core interactivity
├── routes/               # web.php (routing)
├── resources/views/      # Blade templates
├── docker-compose.yml    # Layanan Docker
├── Dockerfile            # Konfigurasi PHP container
└── README.md             # Dokumentasi ini
```

---

## 🔐 Akun Demo & Autentikasi Role

Aplikasi menerapkan sistem **"Satu Login, Dua Dashboard"** dengan verifikasi role aman di server Laravel:

| Role | Akun Demo | Password | Redirect Dashboard |
| :--- | :--- | :--- | :--- |
| **Customer** | `demo@example.com` | `Goddess123` | `/account` (User Dashboard, Orders, Wishlist, Address) |
| **Admin** | `admin@example.com` | `Admin123` | `/admin` (Backstage Admin, Revenue KPI, Stock Alert) |

*Tersedia tombol 1-Click Auto-Fill pada halaman login untuk pengujian cepat.*

---

## 🧪 Menjalankan Automated Tests

Seluruh pengujian unit dan fitur telah lulus (18 tests, 57 assertions):

```bash
docker compose exec app php artisan test
```

---

## 🤝 Cara Berkontribusi

1. **Fork** repository ini
2. Buat **branch** baru (`git checkout -b fitur-baru`)
3. **Commit** perubahan (`git commit -m "Menambah fitur X"`)
4. **Push** ke branch (`git push origin fitur-baru`)
5. Buat **Pull Request** ke branch `main`

> ⚠️ Pastikan kode Anda sesuai standar PSR-12, menambahkan test case jika relevant, dan memperbarui dokumentasi jika ada perubahan fitur.

---

## 📜 Lisensi & Kontak

| Informasi | Detail |
|-----------|--------|
| **Lisensi** | MIT |
| **Maintainer** | [Psr354](https://github.com/Psr354) |
| **Email** | contact@goddessclubstore.id |
| **GitHub** | [@Psr354](https://github.com/Psr354) |

---

*⚡ Dibuat dengan ❤️ untuk Goddess Club — "THE MUSIC STAYS. SO DO WE."*

For a host-only preview, `php artisan serve` is also supported after `composer install`.

## 💳 Konfigurasi Midtrans Sandbox

Aplikasi telah terintegrasi dengan **Midtrans Snap**:
1. Buat akun di [Midtrans Sandbox](https://dashboard.sandbox.midtrans.com/).
2. Buka menu **Settings > Access Keys**, lalu salin:
   - `Client Key` ke `MIDTRANS_CLIENT_KEY` di file `.env`
   - `Server Key` ke `MIDTRANS_SERVER_KEY` di file `.env`
3. Pastikan `MIDTRANS_IS_PRODUCTION=false`.
4. Untuk menguji webhook secara lokal, gunakan webhook simulator di dashboard Midtrans atau endpoint simulasi bawaan: `POST /api/payment/simulate` atau `POST /api/payment/webhook`.
