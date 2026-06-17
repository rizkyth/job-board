# 💼 Job Board System

Sistem manajemen lowongan kerja berbasis web dengan PHP, HTML, dan Tailwind CSS.

## ✨ Fitur Utama

### Untuk Kandidat (Pencari Kerja)
- ✅ Login & Registrasi
- ✅ Setup Profil lengkap (nama, telepon, alamat, pendidikan, pengalaman, CV, foto)
- ✅ Lihat daftar lowongan pekerjaan
- ✅ Lihat daftar perusahaan
- ✅ Melamar pekerjaan dengan lamaran
- ✅ Kelola lamaran (lihat, edit)
- ✅ Track status lamaran

### Untuk HRD (Perusahaan)
- ✅ Login & Registrasi
- ✅ Setup Profil Perusahaan (nama, industri, alamat, kota, website, logo, deskripsi)
- ✅ Setup Profil HRD (nama, jabatan, nomor ext)
- ✅ Kelola lowongan (tambah, edit, hapus, lihat detail)
- ✅ Lihat daftar kandidat yang melamar
- ✅ Update status lamaran (menunggu, proses, diterima, ditolak)
- ✅ Lihat riwayat perubahan status lamaran

## 🛠️ Teknologi
- **Backend**: PHP 7.4+
- **Database**: MySQL
- **Frontend**: HTML5 + Tailwind CSS
- **Server**: XAMPP (Apache + MySQL)

## 📋 Requirement
- XAMPP (atau MySQL + Apache)
- PHP 7.4 atau lebih tinggi
- MySQL 5.7 atau lebih tinggi
- Browser modern (Chrome, Firefox, Safari, Edge)

## 🚀 Instalasi

### 1. Setup Database
```bash
# Import file SQL ke MySQL
mysql -u root -p < job_board.sql
```

### 2. Clone Repository
```bash
cd C:\xampp\htdocs
git clone https://github.com/Rizky-Th/new.git job-board
cd job-board
```

### 3. Konfigurasi Database
Edit file `config/database.php`:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', ''); // Sesuaikan password MySQL Anda
define('DB_NAME', 'job_board');
```

### 4. Buat Folder Uploads
```bash
mkdir uploads
mkdir uploads/cv
mkdir uploads/foto
mkdir uploads/logo
```

### 5. Akses Website
```
http://localhost/job-board/auth/login.php
```

## 📁 Struktur Folder
```
job-board/
├── config/              # Konfigurasi database & session
├── auth/                # Login & Register
├── profil/              # Setup profil user
├── lowongan/            # Kelola lowongan
├── lamaran/             # Kelola lamaran
├── kandidat/            # Lihat kandidat (HRD only)
├── perusahaan/          # Lihat perusahaan (Kandidat only)
├── includes/            # Komponen reusable
├── uploads/             # CV, Foto, Logo
├── dashboard.php        # Dashboard utama
└── README.md
```

## 👤 Akun Demo

### Kandidat
- Email: `kandidat@test.com`
- Password: `123456`

### HRD
- Email: `hrd@test.com`
- Password: `123456`

*Catatan: Buat akun baru melalui halaman registrasi untuk data paling akurat*

## 📚 Database Schema

### Tabel Utama
- `users` - Autentikasi user
- `kandidat` - Profil pencari kerja
- `hrd` - Profil HR
- `perusahaan` - Data perusahaan
- `lowongan` - Lowongan kerja
- `lamaran` - Aplikasi lowongan
- `kategori_pekerjaan` - Kategori job
- `riwayat_status` - Audit trail status lamaran

## 🔐 Keamanan
- Password di-hash menggunakan bcrypt
- Input di-sanitasi dengan prepared statements
- Session management yang aman
- Role-based access control

## 🐛 Troubleshooting

### Error: "Connection failed"
- Pastikan MySQL running
- Cek DB_HOST, DB_USER, DB_PASS di `config/database.php`

### Error: "File upload failed"
- Pastikan folder uploads ada dan writable
- Cek permission folder: `chmod 755 uploads`

### Error: "Session not working"
- Pastikan PHP sessions enabled
- Cek tmp folder permissions

## 📝 License
MIT License

## 👨‍💻 Author
Rizky-Th

## 🤝 Kontribusi
Silakan buat issue atau pull request untuk perbaikan!