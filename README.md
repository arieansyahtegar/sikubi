# Sikubi — Sistem Analisis & Deteksi Anomali Transaksi Keuangan

## Ringkasan Eksekutif

**Sikubi** adalah sistem web berbasis Laravel 12 yang dirancang untuk mengelola, mengklasifikasi, dan menganalisis mutasi rekening bank secara otomatis. Sistem ini mendukung **import mutasi dari BCA dan BRI**, melakukan **klasifikasi transaksi otomatis**, serta **mendeteksi anomali keuangan** untuk membantu pengambilan keputusan manajemen dengan antarmuka pengguna premium yang sangat responsif.

---

## Peningkatan Antarmuka Premium (UI/UX Elite)

SIKUBI telah ditingkatkan dengan standar estetika visual dan kegunaan tingkat tinggi:

### 1. 🌸 Glassmorphism Light Theme (Bigenmi Sakura Theme)
* **Estetika Elegan:** Skema warna eksklusif perpaduan krem hangat (`#FDF6EF`), teks plum gelap, dan gradasi emas merah-muda (*rose gold gradients*).
* **Efek Kaca Transklusif:** Kartu informasi (`glass-card`) dirancang menggunakan latar transparan putih (`rgba(255, 255, 255, 0.82)`) yang dipadukan dengan blur latar belakang yang intens (`backdrop-filter: blur(16px)`) dan border tipis bercahaya.
* **Mikro-Animasi Hover:** Sentuhan interaksi dinamis saat kursor menyentuh kartu (`translateY(-4px)` dan `scale-[1.005]`) dengan bayangan mendalam secara real-time.
* **Sidebar Active Link:** Aksen navigasi aktif dengan garis batas gradasi berpendar emas bercahaya dan warna latar belakang ceri transklusif.

### 2. ⏳ Visual Stepper Progress UI (Import Halaman)
* **6-Stage Interactive Pipeline:** Mengganti spinner statis membosankan dengan alur stepper interaktif (Unggah -> Validasi Enkripsi -> Ekstraksi Mutasi -> Deduplikasi -> Klasifikasi Cerdas -> Selesai).
* **Integrasi Callback Backend:** Animasi visual stepper sinkron dengan respons backend Inertia, menahan pemrosesan pada status klasifikasi otomatis sebelum menyalakan tanda centang hijau bersinar.

### 3. 📱 Mobile Responsiveness Premium Polish
* **Grid Filter Responsif:** Filter pencarian di halaman *Daftar Transaksi* dan *Transaksi Tunai* secara otomatis beralih menjadi Grid seluler (`grid grid-cols-2`). Kolom pencarian deskripsi memanjang penuh, sedangkan filter dropdown terbagi 50:50 secara simetris dengan tombol cari membentang penuh di bawahnya.
* **Toast Notification Terpusat:** Box pesan sukses/gagal dipusatkan secara presisi di tengah bawah layar mobile dengan padding kiri-kanan merata (`w-[calc(100vw-32px)] left-4 right-4`), memberikan pengalaman native yang seimbang.
* **Tombol Header Fleksibel:** Tombol-tombol aksi pada menu kelola kategori dan otorisasi pimpinan menyesuaikan secara dinamis agar bebas dari tumpang tindih elemen visual di handphone.

---

## Fitur Utama

### 1. Dashboard Interaktif
- **KPI Keuangan**: Total pemasukan (debit), pengeluaran (kredit), arus kas bersih, jumlah transaksi
- **Grafik Arus Kas**: Visualisasi harian, bulanan, dan tahunan menggunakan ECharts
- **Breakdown Kategori**: Distribusi transaksi berdasarkan kategori (pie chart)
- **Transaksi Terbaru**: Ringkasan 8 transaksi terakhir dengan penyesuaian warna highlight sumber rekening sesuai jenis transaksi
- **Filter Multi-Rekening**: Pilih rekening bank spesifik untuk analisis

### 2. Import Mutasi Bank (CSV/PDF)
- **Auto-detect format**: Otomatis mendeteksi format BCA atau BRI
- **Parsing cerdas**: Menangani berbagai format tanggal, kolom, dan encoding
- **Deduplikasi**: Mencegah duplikasi data menggunakan hash SHA-256
- **Manajemen Batch**: Riwayat import, restore, dan hapus batch
- **Resolusi Duplikat**: Fitur batch resolve untuk transaksi duplikat

### 3. Klasifikasi Transaksi Otomatis (4 Tahap)
| Tahap | Metode | Confidence |
|-------|--------|------------|
| 1 | Rule-based (keyword matching) | 1.0 |
| 2 | Fuzzy pattern matching (Jaccard similarity) | 0.6–0.9 |
| 3 | Historical KNN (500 transaksi terakhir) | 0.5–0.8 |
| 4 | Auto-suggestion (buat kategori baru jika keyword muncul 3+ kali) | 0.6 |

**Kategori Bawaan**:
- **Pemasukan**: Penjualan Langsung, Online Shop, Penagihan Piutang, Bunga Bank, Transfer Masuk, Pendapatan Lainnya
- **Pengeluaran**: Gaji & THR, Admin Bank, Withdrawal, Pajak, Logistik, Pembelian Produk, Biaya Operasional, Online Shop, Reward, Transfer Keluar

### 4. Deteksi Anomali Keuangan
#### Anomali Pemasukan (Income)
- **Instant**: Transaksi masuk ≥ Rp 10 juta dalam satu transaksi
- **Accumulated**: Akumulasi pemasukan dari pengirim yang sama ≥ Rp 10 juta
- **Severity**: HIGH (≥ Rp 50 juta), MEDIUM (< Rp 50 juta)

#### Anomali Pengeluaran (Expense)
- **Mismatch**: Pengeluaran ke counterparty melebihi pemasukan dari akun tersebut
- **Threshold**: Minimum Rp 1 juta untuk memicu flag
- **Severity**: HIGH jika selisih ≥ Rp 50 juta atau tidak ada pemasukan sama sekali

#### Normalisasi Sender
Sistem mengekstrak nama pengirim/penerima dari deskripsi transaksi bank dengan pattern recognition untuk format TRSF E-BANKING, BI-FAST, SWITCHING, KR OTOMATIS, dan format lainnya.

### 5. Manajemen Rekening Bank
- CRUD multi-rekening (bank name, account number, alias)
- Filter analisis per rekening

### 6. Laporan & Export
- **CSV Recap**: Rekapitulasi transaksi dalam format CSV
- **Excel Export**: Export ke format Excel (.xlsx) menggunakan PHPSpreadsheet
- **Print Recap**: Tampilan cetak untuk laporan

### 7. Manajemen Pengguna & Role
| Role | Hak Akses |
|------|-----------|
| **Admin Keuangan** | Import, edit transaksi, deteksi anomali, review anomali, settings, export laporan |
| **Direktur** | Dashboard eksekutif, review anomali (leader action), manajemen pengguna |

---

## Teknologi yang Digunakan

### Backend
| Teknologi | Versi | Fungsi |
|-----------|-------|--------|
| PHP | 8.2+ | Runtime |
| Laravel Framework | 12.0 | Web framework |
| Laravel Breeze | 2.4 | Authentication scaffolding |
| Laravel Sanctum | 4.0 | API authentication |
| Inertia.js (Laravel) | 2.0 | Bridge backend-frontend |
| PHPSpreadsheet | 5.7 | Export Excel |
| PDFParser | 2.12 | Parsing file PDF |

### Frontend
| Teknologi | Versi | Fungsi |
|-----------|-------|--------|
| Vue.js | 3.4+ | UI framework |
| Inertia.js (Vue) | 2.0 | SPA without API |
| Tailwind CSS | 3.2+ / 4.0 | Styling |
| ECharts / Vue-ECharts | 6.0 / 8.0 | Visualisasi grafik |
| Vue Datepicker | 12.1 | Date picker component |
| PapaParse | 5.5 | CSV parsing (client-side) |
| VueDraggable | 4.1 | Drag & drop |

### Database & Tools
| Teknologi | Fungsi |
|-----------|--------|
| SQLite (default) / MySQL | Database |
| Vite | Build tool & dev server |
| PHPUnit | Testing framework |

---

## Struktur Database

| Tabel | Deskripsi |
|-------|-----------|
| `users` | Data pengguna (name, email, password, role) |
| `bank_accounts` | Rekening bank (bank_name, account_number, account_alias) |
| `categories` | Kategori transaksi (name, type, color, is_suggested) |
| `classification_rules` | Aturan klasifikasi (pattern, match_type, priority) |
| `transactions` | Data transaksi (date, description, amount, type, category_id, classification_method, confidence_score) |
| `import_batches` | Riwayat import (file_name, bank_format, total_rows, success/failed/duplicate rows) |
| `anomaly_flags` | Flag anomali (score, severity, reason, is_reviewed, is_dismissed, leader_action) |
| `duplicate_transactions` | Transaksi duplikat yang terdeteksi saat import |
| `cache_jobs_sessions` | Cache, queue jobs, dan sessions |

---

## Alur Kerja Sistem

```
1. Admin upload CSV mutasi bank (BCA/BRI)
        ↓
2. CsvImportService auto-detect format & parsing
        ↓
3. Deduplikasi (SHA-256 hash)
        ↓
4. ClassificationService klasifikasi otomatis (4 tahap)
        ↓
5. Data tersimpan di tabel transactions
        ↓
6. Admin menjalankan deteksi anomali
        ↓
7. AnomalyDetectionService scan pemasukan & pengeluaran
        ↓
8. Admin review anomali → jika kritis, escalate ke Direktur
        ↓
9. Direktur review & approve/reject anomali (leader action)
        ↓
10. Export laporan (CSV/Excel/Print)
```

---

## Instalasi & Konfigurasi

### Persyaratan
- PHP 8.2+
- Composer
- Node.js 18+
- SQLite atau MySQL

### Langkah Instalasi
```bash
# 1. Clone repository
git clone <repository-url>
cd Sikubi

# 2. Install dependencies
composer install
npm install

# 3. Setup environment
cp .env.example .env
php artisan key:generate

# 4. Setup database (SQLite default)
touch database/database.sqlite
php artisan migrate

# 5. Build assets
npm run build

# 6. Jalankan development server
composer run dev
```

### Quick Setup (Satu Command)
```bash
composer run setup
```

---

## Endpoint Utama

| Route | Method | Role | Deskripsi |
|-------|--------|------|-----------|
| `/dashboard` | GET | All | Dashboard utama |
| `/transactions` | GET | All | Daftar transaksi |
| `/import` | GET/POST | Admin | Import CSV |
| `/anomalies` | GET/POST | Admin | Deteksi & review anomali |
| `/anomalies/check` | GET | Direktur | Review anomali (pimpinan) |
| `/reports/recap` | GET | Admin | Export CSV |
| `/reports/recap/excel` | GET | Admin | Export Excel |
| `/accounts` | CRUD | Admin | Manajemen rekening |
| `/settings/categories` | CRUD | Admin | Manajemen kategori |
| `/users` | CRUD | Direktur | Manajemen pengguna |

---

## Keamanan

- **Authentication**: Laravel Breeze (session-based)
- **Authorization**: Role middleware (`ADMIN_KEUANGAN`, `DIREKTUR`)
- **Password**: Bcrypt dengan 12 rounds
- **CSRF Protection**: Built-in Laravel
- **Soft Deletes**: Data transaksi tidak terhapus permanen

---

## Pengembang & Lisensi

- **Framework**: Laravel (MIT License)
- **Sistem ini dikembangkan untuk**: Analisis dan monitoring transaksi keuangan internal

---

## Kontak & Dukungan

Untuk pertanyaan teknis atau pelaporan bug, hubungi tim pengembang.
