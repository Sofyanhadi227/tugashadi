-- =========================================================
-- DATABASE BROILERFARM DIGITAL MANAGEMENT
-- Database : peternakan_broiler
-- Dibuat untuk PHP 8+ dan MySQL/MariaDB
-- =========================================================

CREATE DATABASE IF NOT EXISTS peternakan_broiler
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE peternakan_broiler;

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS penjualan;
DROP TABLE IF EXISTS keuangan;
DROP TABLE IF EXISTS obat;
DROP TABLE IF EXISTS pakan;
DROP TABLE IF EXISTS pencatatan;
DROP TABLE IF EXISTS populasi;
DROP TABLE IF EXISTS kandang;
DROP TABLE IF EXISTS profil_peternakan;
DROP TABLE IF EXISTS users;

SET FOREIGN_KEY_CHECKS = 1;

-- 1. TABEL USERS
CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin','operator') NOT NULL DEFAULT 'operator',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 2. TABEL PROFIL PETERNAKAN
CREATE TABLE profil_peternakan (
    id TINYINT UNSIGNED PRIMARY KEY,
    nama_peternakan VARCHAR(150) NOT NULL DEFAULT 'Peternakan Ayam Broiler',
    pemilik VARCHAR(120) NULL,
    alamat TEXT NULL,
    lama_usaha_tahun INT UNSIGNED NOT NULL DEFAULT 0,
    frekuensi_panen_tahun INT UNSIGNED NOT NULL DEFAULT 0,
    jumlah_pekerja INT UNSIGNED NOT NULL DEFAULT 0,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 3. TABEL KANDANG
CREATE TABLE kandang (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nama_kandang VARCHAR(100) NOT NULL UNIQUE,
    kapasitas INT UNSIGNED NOT NULL DEFAULT 0,
    lokasi VARCHAR(180) NULL,
    status ENUM('aktif','nonaktif') NOT NULL DEFAULT 'aktif',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 4. TABEL POPULASI
CREATE TABLE populasi (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    kandang_id INT UNSIGNED NOT NULL,
    tgl_masuk DATE NOT NULL,
    jumlah_awal INT UNSIGNED NOT NULL,
    jumlah_hidup INT UNSIGNED NOT NULL,
    berat_rata DECIMAL(8,3) NOT NULL DEFAULT 0,
    status ENUM('aktif','panen','selesai') NOT NULL DEFAULT 'aktif',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_populasi_kandang
        FOREIGN KEY (kandang_id)
        REFERENCES kandang(id)
        ON DELETE RESTRICT
        ON UPDATE CASCADE
) ENGINE=InnoDB;

-- 5. TABEL PENCATATAN HARIAN
CREATE TABLE pencatatan (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    populasi_id INT UNSIGNED NOT NULL,
    tanggal DATE NOT NULL,
    mati INT UNSIGNED NOT NULL DEFAULT 0,
    sakit INT UNSIGNED NOT NULL DEFAULT 0,
    suhu DECIMAL(5,2) NULL,
    kelembaban DECIMAL(5,2) NULL,
    pakan_kg DECIMAL(10,2) NOT NULL DEFAULT 0,
    minum_liter DECIMAL(10,2) NOT NULL DEFAULT 0,
    berat_rata DECIMAL(8,3) NOT NULL DEFAULT 0,
    catatan TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_catatan_harian (populasi_id, tanggal),
    CONSTRAINT fk_catatan_populasi
        FOREIGN KEY (populasi_id)
        REFERENCES populasi(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB;

-- 6. TABEL PAKAN
CREATE TABLE pakan (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tanggal DATE NOT NULL,
    jenis_pakan VARCHAR(100) NOT NULL,
    stok_masuk DECIMAL(12,2) NOT NULL DEFAULT 0,
    pemakaian DECIMAL(12,2) NOT NULL DEFAULT 0,
    satuan VARCHAR(30) NOT NULL DEFAULT 'kg',
    keterangan VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 7. TABEL OBAT DAN VITAMIN
CREATE TABLE obat (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tanggal DATE NOT NULL,
    nama_obat VARCHAR(120) NOT NULL,
    jenis VARCHAR(80) NULL,
    stok_masuk DECIMAL(12,2) NOT NULL DEFAULT 0,
    pemakaian DECIMAL(12,2) NOT NULL DEFAULT 0,
    satuan VARCHAR(30) NOT NULL DEFAULT 'unit',
    keterangan VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 8. TABEL KEUANGAN
CREATE TABLE keuangan (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tanggal DATE NOT NULL,
    jenis ENUM('pemasukan','pengeluaran') NOT NULL,
    kategori VARCHAR(100) NOT NULL,
    nominal DECIMAL(16,2) NOT NULL,
    keterangan VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 9. TABEL PENJUALAN
CREATE TABLE penjualan (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    populasi_id INT UNSIGNED NULL,
    tanggal DATE NOT NULL,
    jumlah_ayam INT UNSIGNED NOT NULL,
    berat_total DECIMAL(12,2) NOT NULL,
    harga_per_kg DECIMAL(14,2) NOT NULL,
    total_harga DECIMAL(16,2) NOT NULL,
    pembeli VARCHAR(150) NULL,
    keterangan VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_penjualan_populasi
        FOREIGN KEY (populasi_id)
        REFERENCES populasi(id)
        ON DELETE SET NULL
        ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ===========================================================
-- DATA AWAL
-- =========================================================

INSERT INTO profil_peternakan (
    id,
    nama_peternakan,
    pemilik,
    alamat,
    lama_usaha_tahun,
    frekuensi_panen_tahun,
    jumlah_pekerja
) VALUES (
    1,
    'Peternakan Ayam Broiler',
    NULL,
    NULL,
    0,
    0,
    0
);

-- Akun awal:
-- Email    : admin@broilerfarm.local
-- Password : admin123
INSERT INTO users (name, email, password, role) VALUES (
    'Administrator',
    'admin@broilerfarm.local',
    '$2y$12$tCREtGfxJvDdvrNy/wooI.1.0ZOHAv/ud.b2k113KsQ.KUUd7UKka',
    'admin'
);

-- Contoh kandang dapat diaktifkan dengan menghapus tanda komentar:
-- INSERT INTO kandang (nama_kandang, kapasitas, lokasi, status)
-- VALUES ('Kandang 1', 2000, 'Blok A', 'aktif');
