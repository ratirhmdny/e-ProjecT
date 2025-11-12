# E-SPP (Electronic Sistem Pembayaran Pendidikan)

## Deskripsi Proyek
Sistem administrasi pembayaran internal untuk pencatatan tagihan dan pembayaran mahasiswa tanpa gateway pembayaran.

## Fitur Utama
- Autentikasi multi-role (Admin, Staff, Mahasiswa)
- Manajemen tagihan dan pembayaran
- Rekap pembayaran per mahasiswa/program
- Export data ke CSV
- Dashboard dengan chart laporan
- Pencarian dan pagination

## Teknologi yang Digunakan
- **Backend**: PHP 8.0+
- **Database**: MySQL 5.7+
- **Frontend**: HTML5, CSS3, JavaScript (Vanilla)
- **CSS Framework**: Tailwind CSS
- **Charts**: Chart.js
- **Icons**: Font Awesome

## Struktur Folder
```
├── config/              # Konfigurasi database dan aplikasi
├── includes/            # File-file pendukung
├── functions/           # Fungsi-fungsi CRUD dan helper
├── assets/              # File statis (CSS, JS, images)
├── pages/               # Halaman-halaman PHP
├── uploads/             # Folder untuk file upload
└── sql/                 # File SQL untuk setup database
```

## Instalasi
1. Clone atau download project
2. Import file SQL dari folder `sql/` ke database MySQL
3. Konfigurasi koneksi database di `config/database.php`
4. Jalankan aplikasi melalui web server

## Keamanan yang Diimplementasikan
- Prepared Statements untuk mencegah SQL Injection
- CSRF Protection pada form penting
- XSS Prevention dengan output escaping
- Session Management yang aman
- Password Hashing dengan bcrypt
- Validasi client-side dan server-side

## Role Pengguna
- **Admin**: Full akses ke semua fitur
- **Staff**: Manajemen pembayaran dan tagihan
- **Mahasiswa**: Lihat tagihan dan riwayat pembayaran

## Fitur CRUD
- Manajemen Users
- Manajemen Programs
- Manajemen Bills/Tagihan
- Manajemen Payments/Pembayaran

## Laporan dan Analisis
- Dashboard dengan statistik pembayaran
- Chart pembayaran per program
- Rekap pembayaran per mahasiswa
- Export data ke format CSV