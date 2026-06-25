# BroilerFarm Digital Management

Aplikasi manajemen peternakan ayam broiler berbasis PHP dan MySQL, dibuat berdasarkan sketsa yang memuat:

- Login admin/operator
- Dashboard
- Data kandang
- Populasi/DOC masuk
- Pencatatan harian: mati, sakit, suhu, kelembaban, pakan, minum, bobot
- Stok pakan
- Stok obat dan vitamin
- Keuangan
- Penjualan/panen
- Laporan cetak atau simpan PDF
- Profil peternakan dan manajemen akun

## Kebutuhan

- XAMPP atau Laragon
- PHP 8.1 atau lebih baru
- MySQL/MariaDB
- Ekstensi PHP PDO MySQL aktif

## Cara instalasi di XAMPP

1. Ekstrak folder `broilerfarm_php`.
2. Pindahkan folder ke:
   `C:\xampp\htdocs\broilerfarm_php`
3. Jalankan Apache dan MySQL.
4. Buka:
   `http://localhost/broilerfarm_php/install.php`
5. Klik **Buat Database dan Tabel**.
6. Buka halaman login:
   `http://localhost/broilerfarm_php/login.php`

## Akun awal

- Email: `admin@broilerfarm.local`
- Password: `admin123`

Segera ubah password melalui menu **Pengaturan**.

## Konfigurasi database

File: `config/app.php`

Konfigurasi bawaan XAMPP:

```php
const DB_HOST = '127.0.0.1';
const DB_PORT = '3306';
const DB_NAME = 'peternakan_broiler';
const DB_USER = 'root';
const DB_PASS = '';
```

## Catatan laporan PDF

Menu Laporan memakai fitur cetak browser agar tidak membutuhkan pustaka tambahan.

1. Buka menu **Laporan**.
2. Pilih jenis dan periode.
3. Klik **Cetak / Simpan PDF**.
4. Pada dialog printer pilih **Save as PDF** atau **Simpan sebagai PDF**.

## Catatan perhitungan

- Populasi hidup = jumlah awal - kematian - jumlah terjual.
- Estimasi FCR pada dashboard = total pakan harian ÷ biomassa aktif.
- Penjualan baru otomatis ditambahkan ke keuangan sebagai pemasukan.
- Menghapus penjualan tidak otomatis menghapus transaksi pemasukan yang pernah dibuat, agar riwayat keuangan tidak berubah diam-diam. Hapus manual pada menu Keuangan bila diperlukan.
