# 🌸 SIKUBI — Sistem Analisis & Deteksi Anomali Transaksi Keuangan

Selamat datang di **SIKUBI**! Platform analisis keuangan cerdas berbasis **Laravel 12 & Vue.js 3** yang dirancang khusus untuk mempermudah pencatatan, pengelompokan otomatis, hingga deteksi dini potensi anomali pada mutasi rekening bank perusahaan. 

SIKUBI memadukan performa backend yang tangguh dengan visual modern bertema **Bigenmi Sakura** yang sangat memanjakan mata serta responsif di semua perangkat.

---

## ✨ Estetika Visual & UI/UX Premium

SIKUBI dirancang dengan fokus tinggi pada kenyamanan mata dan kemudahan interaksi pengguna (*user-friendly*):

### 1. 🌸 Tema Light Glassmorphism (Bigenmi Sakura Theme)
* **Palet Warna Harmonis:** Kombinasi warna krem hangat (`#FDF6EF`), teks plum gelap (`#4E2844`), dan sentuhan gradasi emas merah-muda (*rose gold gradients*) memberikan kesan eksklusif dan mewah.
* **Efek Kaca Transparansi:** Kartu informasi (`glass-card`) menggunakan latar putih semi-transparan dengan efek blur intens (`backdrop-filter: blur(16px)`) dan border tipis mengkilap.
* **Animasi 3D Hover & Staggered Load:**
  * Card rekening bank memiliki efek **3D rotation shift** halus saat didekati kursor, merespons pergerakan mouse pengguna.
  * Elemen daftar/card dimuat secara bertahap (*staggered entrance delay*) saat halaman pertama kali dibuka, memberikan kesan aplikasi yang hidup dan dinamis.

### 2. ⏳ Visual Stepper Progress pada Import Data
* **6-Stage Interactive Pipeline:** Proses import file mutasi tidak lagi membosankan dengan visual progress stepper interaktif (Unggah -> Validasi Enkripsi -> Ekstraksi Mutasi -> Deduplikasi -> Klasifikasi Cerdas -> Selesai).
* **Real-time Callback Sync:** Animasi stepper menyala secara berkala menyelaraskan status pemrosesan riil di server backend.

### 3. 📱 Responsif di Semua Device (Mobile-First)
* **Auto-layout Adaptif:** Seluruh tabel data yang padat akan otomatis beralih menjadi tampilan **vertical card list** yang ringkas saat dibuka melalui smartphone.
* **Tabel Anti-Pecah:** Untuk data tabular, disematkan wadah khusus dengan scroll horizontal halus, menjaga agar layout halaman tidak melebar keluar layar.
* **Color Picker Ringkas:** Input warna kategori konvensional digantikan dengan 9 lingkaran preset warna estetik dan tombol kustom tersembunyi demi mencegah rusaknya tata letak layar handphone.

---

## 🚀 Fitur Unggulan

### 1. Dashboard Eksekutif & Operasional
* **Indikator KPI Keuangan:** Pantau total pemasukan (debit), pengeluaran (kredit), arus kas bersih, serta jumlah transaksi secara instan.
* **Visualisasi Grafik Interaktif:** Grafik arus kas interaktif (harian, bulanan, tahunan) dan diagram lingkaran komposisi kategori ditenagai oleh Apache ECharts.
* **Penyaringan Cepat:** Analisis data berdasarkan rekening bank tertentu secara instan.

### 2. Import Mutasi Bank Cerdas
* **Deteksi Otomatis:** Sistem mengenali format mutasi bank BCA atau BRI secara otomatis saat diunggah.
* **Deduplikasi SHA-256:** Menghindari pencatatan ganda dengan membandingkan hash unik transaksi yang sudah ada di database.
* **Batch Management:** Dilengkapi fitur hapus permanen, pemulihan (*restore*), dan hapus massal (*delete all history*) dalam sekali klik.

### 3. Klasifikasi Otomatis Multi-Tahap (4 Level)
Setiap transaksi yang masuk dikelompokkan secara cerdas melalui pipeline berikut:
1. **Rule-Based Matching:** Pencocokan kata kunci mutasi dengan aturan prioritas admin (Confidence: `1.0`).
2. **Fuzzy Pattern Matching:** Analisis kesamaan teks menggunakan Jaccard Similarity (Confidence: `0.6 - 0.9`).
3. **Historical KNN:** Membaca tren klasifikasi manual pada 500 transaksi terakhir (Confidence: `0.5 - 0.8`).
4. **Auto-Suggestion:** Menyarankan kategori baru secara mandiri jika suatu kata kunci muncul $\ge 3$ kali (Confidence: `0.6`).

### 4. Pusat Pengawasan & Deteksi Anomali
* **Deteksi Pemasukan Luar Biasa:** Mendeteksi dana masuk instan $\ge$ Rp 10 juta atau akumulasi dari pengirim yang sama dalam sebulan.
* **Deteksi Pengeluaran Tidak Seimbang:** Memantau dana keluar ke suatu pihak yang nilainya melebihi total dana masuk dari pihak tersebut.
* **Sistem Otorisasi Pimpinan:** Admin dapat memberikan catatan tinjauan dan mengajukan persetujuan langsung kepada Direktur (Pimpinan) untuk anomali beresiko tinggi.

### 5. Rekening Bank Terintegrasi
* CRUD akun rekening bank lengkap dengan kode alias 3-huruf dinamis (seperti `BCA`, `BRI`, `MAN`) pada logo card.
* **Navigasi Cepat:** Klik pada card rekening langsung membawa pengguna ke daftar transaksi terkait yang ter-filter otomatis sesuai periode terpilih.

---

## 🛠️ Spesifikasi Teknologi

### Backend Stack
* **PHP 8.2+** & **Laravel 12.0**
* **Laravel Breeze** (Autentikasi Sederhana & Aman)
* **PHPSpreadsheet** (Ekspor Laporan Excel)
* **Smalot PDFParser** (Ekstraksi teks file mutasi PDF)

### Frontend Stack
* **Vue.js 3.4** (Composition API) & **Inertia.js 2.0** (SPA lancar tanpa perlu REST API terpisah)
* **Tailwind CSS 3/4** (Styling responsif)
* **Apache ECharts** (Visualisasi chart)
* **Vue Datepicker** & **VueDraggable**

---

## 💻 Cara Instalasi & Menjalankan Project

### Persyaratan Sistem
Pastikan perangkat Anda sudah terinstal:
* PHP $\ge$ 8.2
* Composer
* Node.js $\ge$ 18 & npm

### Langkah-Langkah Setup
1. **Clone project dan masuk ke direktori:**
   ```bash
   git clone <repository-url>
   cd Sikubi
   ```

2. **Install dependensi PHP & Node.js:**
   ```bash
   composer install
   npm install
   ```

3. **Salin konfigurasi environment:**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Siapkan Database SQLite bawaan:**
   ```bash
   # Di Windows (PowerShell)
   New-Item -ItemType File -Path database\database.sqlite -Force
   
   # Jalankan migrasi dan seeder awal
   php artisan migrate --seed
   ```

5. **Build aset frontend dan jalankan server:**
   ```bash
   # Build aset sekali saja (produksi)
   npm run build
   
   # ATAU jalankan dev server untuk perubahan langsung
   npm run dev
   ```

6. **Jalankan aplikasi Laravel:**
   ```bash
   php artisan serve
   ```
   Buka browser Anda di `http://127.0.0.1:8000`.

---

## 👥 Manajemen Hak Akses (Role)

1. **Admin Keuangan**  
   Bertanggung jawab mengunggah mutasi, mencatat transaksi manual, melakukan deteksi anomali awal, dan mengekspor laporan bulanan.
2. **Direktur (Pimpinan)**  
   Mendapatkan dashboard ringkasan eksekutif, memberikan otorisasi akhir (*approve/reject*) terhadap anomali transaksi yang diajukan oleh admin, dan mengelola pengguna.

---

## 📄 Lisensi & Pengembang

* **Lisensi:** Sistem ini menggunakan lisensi internal PT Bigenmi Gemilang Indonesia.
* **Kerangka Kerja:** Dikembangkan dengan Laravel Framework (Lisensi MIT).

Untuk pertanyaan lebih lanjut atau melaporkan temuan bug, silakan hubungi tim IT PT Bigenmi. Selamat menggunakan SIKUBI! 🌸
