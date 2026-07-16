# 🎓 SDS - Sistem Digital Sekolah

> Modern, Modular, and Easy-to-Install School Management System built with PHP.

SDS (Sistem Digital Sekolah) adalah aplikasi manajemen sekolah berbasis **PHP Native** yang dikembangkan dengan arsitektur modular untuk memudahkan pengembangan, instalasi, dan pemeliharaan sistem. SDS dirancang sebagai platform yang dapat dikembangkan sesuai kebutuhan sekolah, mulai dari administrasi, akademik, perpustakaan, hingga modul-modul tambahan.

---

## ✨ Highlights

- 🚀 Web Installer (Tanpa konfigurasi manual)
- 📦 Modular Package System
- 🔄 Database Migration & Seeder
- 🛠 Runtime Configuration
- 👥 Multi User & Role Management
- 📚 Library Management
- 🎓 Academic Management
- 📊 Dashboard & Reports
- 📄 PDF Export
- 📊 Excel Import & Export
- 📱 Responsive Interface
- 🔒 Secure Authentication
- ⚡ Lightweight & Fast

---

## 📸 Screenshots

> _Coming Soon_

Tambahkan screenshot dashboard, halaman login, dan modul lainnya di sini.

---

# 🏗️ Architecture

```
SDS
│
├── app/
│   ├── Core/
│   ├── Helpers/
│   ├── Modules/
│   └── Controllers/
│
├── assets/
├── config/
├── install/
├── modules/
├── storage/
├── uploads/
├── vendor/
└── public/
```

SDS menggunakan pendekatan **modular architecture**, sehingga fitur baru dapat dikembangkan sebagai package tanpa mengubah inti aplikasi.

---

# ⚙️ Requirements

## Server

- PHP **8.1** atau lebih baru
- MySQL / MariaDB
- Apache / Nginx
- Composer
- mod_rewrite Enabled

## PHP Extensions

- PDO
- PDO MySQL
- OpenSSL
- mbstring
- GD
- ZIP
- FileInfo
- JSON
- cURL

---

# 🚀 Installation

## 1. Clone Repository

```bash
git clone https://github.com/globalwebID/sds.git
```

atau download repository dalam bentuk ZIP.

---

## 2. Install Dependency

```bash
composer install
```

---

## 3. Buat Database

Contoh:

```
sds
```

Tidak perlu mengimport SQL secara manual.

---

## 4. Jalankan Installer

Buka browser:

```
http://localhost/sds/install
```

atau

```
https://domainanda.com/install
```

Installer akan membantu melakukan:

- ✅ Server Requirement Check
- ✅ Permission Check
- ✅ Database Configuration
- ✅ Import Core Database
- ✅ Migration
- ✅ Seeder
- ✅ Generate Configuration
- ✅ Runtime Configuration
- ✅ Administrator Setup

Setelah selesai, aplikasi siap digunakan.

---

# 📦 Module System

SDS mendukung instalasi modul secara independen.

Keunggulan sistem modul:

- Install Package
- Enable / Disable Module
- Module Registry
- Dependency Checker
- Auto Migration
- Auto Seeder

Hal ini memungkinkan aplikasi berkembang tanpa mengubah source utama.

---

# 📁 Directory Structure

```
app/
assets/
config/
install/
modules/
storage/
uploads/
vendor/
```

---

# 🔄 Database

Installer secara otomatis menjalankan:

- Core Schema
- Migration
- Seeder

Tidak diperlukan proses import SQL secara manual.

---

# ⚡ Features

## Academic

- Academic Year
- Class Management
- Student Management
- Teacher Management

## Administration

- User Management
- Role & Permission
- Dashboard
- Activity Log

## Library

- Book Management
- Borrowing
- Returning

## Utilities

- QR Code
- PDF Generator
- Excel Import
- Excel Export

## System

- Module Installer
- Runtime Configuration
- Migration
- Seeder
- Package Registry

---

# 🛠 Development

Install dependency

```bash
composer install
```

Update dependency

```bash
composer update
```

---

# 📄 Configuration

Konfigurasi aplikasi tersimpan pada folder:

```
config/
```

Beberapa konfigurasi yang tersedia antara lain:

- Application
- Database
- Runtime
- Academic Year
- Modules

---

# 🔐 Security

Demi keamanan aplikasi:

- Jangan mengubah file konfigurasi secara langsung saat aplikasi sudah berjalan.
- Hapus atau nonaktifkan folder `install/` setelah proses instalasi selesai.
- Gunakan HTTPS pada server produksi.

---

# 🗺 Roadmap

- [x] Web Installer
- [x] Migration System
- [x] Seeder
- [x] Package Installer
- [x] Module Registry
- [x] Academic Management
- [x] Library Management
- [ ] Attendance Module
- [ ] E-Learning
- [ ] Mobile Application
- [ ] REST API
- [ ] Notification System

---

# 🤝 Contributing

Kontribusi sangat terbuka untuk perbaikan bug maupun penambahan fitur.

Langkah kontribusi:

1. Fork repository
2. Buat branch baru

```
feature/nama-fitur
```

3. Commit perubahan
4. Push ke repository
5. Buat Pull Request

---

# 📄 License

Copyright © Global Web ID.

Repository ini menggunakan lisensi sesuai ketentuan pemilik repository.

---

# 👨‍💻 Developer

**Global Web ID**

Website: https://globalweb.id

Email: support@globalweb.id

---

## ⭐ Support

Apabila project ini bermanfaat, jangan lupa memberikan ⭐ pada repository ini.

Setiap dukungan Anda akan membantu pengembangan SDS menjadi platform manajemen sekolah yang lebih baik.
