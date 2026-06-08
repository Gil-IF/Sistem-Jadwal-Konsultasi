# 📅 Sistem Jadwal Konsultasi

Aplikasi web untuk manajemen jadwal konsultasi dokter berbasis PHP & MySQL. Dibangun sebagai tugas kuliah.

---

## ✨ Fitur

### 👨‍⚕️ Panel Admin
- **Dashboard** — statistik dokter aktif, total pasien, slot tersedia, dan janji aktif
- **Manajemen Dokter** — tambah, edit, dan aktifkan/nonaktifkan dokter
- **Manajemen Slot Waktu** — tambah slot satuan atau bulk (rentang tanggal & jam otomatis)
- **Manajemen Janji Temu** — lihat semua janji, tandai selesai, atau batalkan (slot otomatis dikembalikan)
- **Data Pasien** — lihat daftar pasien beserta total janji temu

### 🧑‍💼 Panel Pasien
- **Booking Janji** — pilih dokter, pilih slot waktu yang tersedia, tambahkan catatan keluhan
- **Riwayat Janji** — lihat status janji temu (aktif, selesai, dibatalkan)

---

## 🛠️ Tech Stack

| Layer | Teknologi |
|-------|-----------|
| Backend | PHP (Native) |
| Database | MySQL |
| Frontend | Bootstrap 5 + Bootstrap Icons |
| Server | Apache (Laragon / XAMPP) |

---

## ⚙️ Instalasi

### Prasyarat
- [Laragon](https://laragon.org/) atau XAMPP
- PHP >= 8.0
- MySQL >= 5.7

### Langkah-langkah

**1. Clone repositori**
```bash
git clone https://github.com/username/sistem-jadwal-konsultasi.git
```

**2. Pindahkan ke folder server**

Laragon:
```
C:\laragon\www\Sistem-Jadwal-Konsultasi
```
XAMPP:
```
C:\xampp\htdocs\Sistem-Jadwal-Konsultasi
```

**3. Import database**

Buka phpMyAdmin, buat database baru bernama `sistem_jadwal_konsultasi`, lalu import file:
```
database/sistem_jadwal_konsultasi.sql
```

**4. Konfigurasi koneksi**

Edit file `config/config.php` sesuaikan dengan environment kamu:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'sistem_jadwal_konsultasi');
define('DB_USER', 'root');
define('DB_PASS', '');
define('BASE_URL', 'http://localhost/Sistem-Jadwal-Konsultasi');
```

**5. Jalankan aplikasi**

Buka browser dan akses:
```
http://localhost/Sistem-Jadwal-Konsultasi
```

---

## 🔐 Akun Default

| Role | Email | Password |
|------|-------|----------|
| Admin | admin@admin.com | admin123 |
| Pasien | (daftar sendiri) | — |

> Ganti password default setelah pertama kali login.
