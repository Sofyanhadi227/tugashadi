-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 21 Jun 2026 pada 09.31
-- Versi server: 10.4.32-MariaDB
-- Versi PHP: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `peternakan_broiler`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `kandang`
--

CREATE TABLE `kandang` (
  `id` int(10) UNSIGNED NOT NULL,
  `nama_kandang` varchar(100) NOT NULL,
  `kapasitas` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `lokasi` varchar(180) DEFAULT NULL,
  `status` enum('aktif','nonaktif') NOT NULL DEFAULT 'aktif',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `kandang`
--

INSERT INTO `kandang` (`id`, `nama_kandang`, `kapasitas`, `lokasi`, `status`, `created_at`) VALUES
(1, '1', 1000, '1', 'aktif', '2026-06-18 12:13:45');

-- --------------------------------------------------------

--
-- Struktur dari tabel `keuangan`
--

CREATE TABLE `keuangan` (
  `id` int(10) UNSIGNED NOT NULL,
  `tanggal` date NOT NULL,
  `jenis` enum('pemasukan','pengeluaran') NOT NULL,
  `kategori` varchar(100) NOT NULL,
  `nominal` decimal(16,2) NOT NULL,
  `keterangan` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `obat`
--

CREATE TABLE `obat` (
  `id` int(10) UNSIGNED NOT NULL,
  `tanggal` date NOT NULL,
  `nama_obat` varchar(120) NOT NULL,
  `jenis` varchar(80) DEFAULT NULL,
  `stok_masuk` decimal(12,2) NOT NULL DEFAULT 0.00,
  `pemakaian` decimal(12,2) NOT NULL DEFAULT 0.00,
  `satuan` varchar(30) NOT NULL DEFAULT 'unit',
  `keterangan` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `pakan`
--

CREATE TABLE `pakan` (
  `id` int(10) UNSIGNED NOT NULL,
  `tanggal` date NOT NULL,
  `jenis_pakan` varchar(100) NOT NULL,
  `stok_masuk` decimal(12,2) NOT NULL DEFAULT 0.00,
  `pemakaian` decimal(12,2) NOT NULL DEFAULT 0.00,
  `satuan` varchar(30) NOT NULL DEFAULT 'kg',
  `keterangan` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `pencatatan`
--

CREATE TABLE `pencatatan` (
  `id` int(10) UNSIGNED NOT NULL,
  `populasi_id` int(10) UNSIGNED NOT NULL,
  `tanggal` date NOT NULL,
  `mati` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `sakit` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `suhu` decimal(5,2) DEFAULT NULL,
  `kelembaban` decimal(5,2) DEFAULT NULL,
  `pakan_kg` decimal(10,2) NOT NULL DEFAULT 0.00,
  `minum_liter` decimal(10,2) NOT NULL DEFAULT 0.00,
  `berat_rata` decimal(8,3) NOT NULL DEFAULT 0.000,
  `catatan` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `pencatatan`
--

INSERT INTO `pencatatan` (`id`, `populasi_id`, `tanggal`, `mati`, `sakit`, `suhu`, `kelembaban`, `pakan_kg`, `minum_liter`, `berat_rata`, `catatan`, `created_at`) VALUES
(1, 1, '2026-06-18', 10, 5, 30.00, 20.00, 50.00, 20.00, 0.500, 'sehat', '2026-06-18 12:15:39');

-- --------------------------------------------------------

--
-- Struktur dari tabel `penjualan`
--

CREATE TABLE `penjualan` (
  `id` int(10) UNSIGNED NOT NULL,
  `populasi_id` int(10) UNSIGNED DEFAULT NULL,
  `tanggal` date NOT NULL,
  `jumlah_ayam` int(10) UNSIGNED NOT NULL,
  `berat_total` decimal(12,2) NOT NULL,
  `harga_per_kg` decimal(14,2) NOT NULL,
  `total_harga` decimal(16,2) NOT NULL,
  `pembeli` varchar(150) DEFAULT NULL,
  `keterangan` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `populasi`
--

CREATE TABLE `populasi` (
  `id` int(10) UNSIGNED NOT NULL,
  `kandang_id` int(10) UNSIGNED NOT NULL,
  `tgl_masuk` date NOT NULL,
  `jumlah_awal` int(10) UNSIGNED NOT NULL,
  `jumlah_hidup` int(10) UNSIGNED NOT NULL,
  `berat_rata` decimal(8,3) NOT NULL DEFAULT 0.000,
  `status` enum('aktif','panen','selesai') NOT NULL DEFAULT 'aktif',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `populasi`
--

INSERT INTO `populasi` (`id`, `kandang_id`, `tgl_masuk`, `jumlah_awal`, `jumlah_hidup`, `berat_rata`, `status`, `created_at`) VALUES
(1, 1, '2026-06-18', 1000, 990, 0.500, 'aktif', '2026-06-18 12:14:25');

-- --------------------------------------------------------

--
-- Struktur dari tabel `profil_peternakan`
--

CREATE TABLE `profil_peternakan` (
  `id` tinyint(3) UNSIGNED NOT NULL,
  `nama_peternakan` varchar(150) NOT NULL DEFAULT 'Peternakan Ayam Broiler',
  `pemilik` varchar(120) DEFAULT NULL,
  `alamat` text DEFAULT NULL,
  `lama_usaha_tahun` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `frekuensi_panen_tahun` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `jumlah_pekerja` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `profil_peternakan`
--

INSERT INTO `profil_peternakan` (`id`, `nama_peternakan`, `pemilik`, `alamat`, `lama_usaha_tahun`, `frekuensi_panen_tahun`, `jumlah_pekerja`, `updated_at`) VALUES
(1, 'Peternakan Ayam Broiler', 'sofyan hadi', '', 2024, 2025, 100, '2026-06-19 04:45:25');

-- --------------------------------------------------------

--
-- Struktur dari tabel `users`
--

CREATE TABLE `users` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','operator') NOT NULL DEFAULT 'operator',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `created_at`) VALUES
(1, 'Administrator', 'admin@broilerfarm.local', '$2y$12$tCREtGfxJvDdvrNy/wooI.1.0ZOHAv/ud.b2k113KsQ.KUUd7UKka', 'admin', '2026-06-18 11:49:23');

-- --------------------------------------------------------

--
-- Struktur dari tabel `wawancara`
--

CREATE TABLE `wawancara` (
  `id` int(10) UNSIGNED NOT NULL,
  `bagian` varchar(100) NOT NULL,
  `kode` varchar(10) NOT NULL,
  `pertanyaan` text NOT NULL,
  `jawaban` text DEFAULT NULL,
  `urutan` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `wawancara`
--

INSERT INTO `wawancara` (`id`, `bagian`, `kode`, `pertanyaan`, `jawaban`, `urutan`, `updated_at`) VALUES
(1, 'A. Profil Peternakan', 'A1', 'Sudah berapa lama menjalankan peternakan ayam broiler?', NULL, 1, '2026-06-18 11:49:23'),
(2, 'A. Profil Peternakan', 'A2', 'Berapa kapasitas ayam dalam satu kandang?', NULL, 2, '2026-06-18 11:49:23'),
(3, 'A. Profil Peternakan', 'A3', 'Dalam setahun berapa kali panen dilakukan?', NULL, 3, '2026-06-18 11:49:23'),
(4, 'A. Profil Peternakan', 'A4', 'Berapa jumlah pekerja yang terlibat dalam operasional peternakan?', NULL, 4, '2026-06-18 11:49:23'),
(5, 'B. Proses Operasional', 'B1', 'Bagaimana proses pencatatan jumlah ayam saat DOC masuk?', NULL, 5, '2026-06-18 11:49:23'),
(6, 'B. Proses Operasional', 'B2', 'Bagaimana pencatatan pemberian pakan dilakukan?', NULL, 6, '2026-06-18 11:49:23'),
(7, 'B. Proses Operasional', 'B3', 'Bagaimana pencatatan penggunaan obat dan vitamin?', NULL, 7, '2026-06-18 11:49:23'),
(8, 'B. Proses Operasional', 'B4', 'Bagaimana pencatatan kematian ayam setiap hari?', NULL, 8, '2026-06-18 11:49:23'),
(9, 'B. Proses Operasional', 'B5', 'Bagaimana pencatatan berat badan ayam dilakukan?', NULL, 9, '2026-06-18 11:49:23'),
(10, 'B. Proses Operasional', 'B6', 'Bagaimana proses pencatatan hasil panen?', NULL, 10, '2026-06-18 11:49:23'),
(11, 'C. Sistem yang Digunakan Saat Ini', 'C1', 'Apakah saat ini menggunakan buku, Excel, atau aplikasi?', NULL, 11, '2026-06-18 11:49:23'),
(12, 'C. Sistem yang Digunakan Saat Ini', 'C2', 'Apa kesulitan yang sering dialami dalam pencatatan data?', NULL, 12, '2026-06-18 11:49:23'),
(13, 'C. Sistem yang Digunakan Saat Ini', 'C3', 'Apakah data sering hilang atau sulit dicari kembali?', NULL, 13, '2026-06-18 11:49:23'),
(14, 'C. Sistem yang Digunakan Saat Ini', 'C4', 'Berapa lama waktu yang dibutuhkan untuk membuat laporan?', NULL, 14, '2026-06-18 11:49:23');

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `kandang`
--
ALTER TABLE `kandang`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nama_kandang` (`nama_kandang`);

--
-- Indeks untuk tabel `keuangan`
--
ALTER TABLE `keuangan`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `obat`
--
ALTER TABLE `obat`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `pakan`
--
ALTER TABLE `pakan`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `pencatatan`
--
ALTER TABLE `pencatatan`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_catatan_harian` (`populasi_id`,`tanggal`);

--
-- Indeks untuk tabel `penjualan`
--
ALTER TABLE `penjualan`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_penjualan_populasi` (`populasi_id`);

--
-- Indeks untuk tabel `populasi`
--
ALTER TABLE `populasi`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_populasi_kandang` (`kandang_id`);

--
-- Indeks untuk tabel `profil_peternakan`
--
ALTER TABLE `profil_peternakan`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indeks untuk tabel `wawancara`
--
ALTER TABLE `wawancara`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `kode` (`kode`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `kandang`
--
ALTER TABLE `kandang`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT untuk tabel `keuangan`
--
ALTER TABLE `keuangan`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `obat`
--
ALTER TABLE `obat`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `pakan`
--
ALTER TABLE `pakan`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `pencatatan`
--
ALTER TABLE `pencatatan`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT untuk tabel `penjualan`
--
ALTER TABLE `penjualan`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `populasi`
--
ALTER TABLE `populasi`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT untuk tabel `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT untuk tabel `wawancara`
--
ALTER TABLE `wawancara`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=43;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `pencatatan`
--
ALTER TABLE `pencatatan`
  ADD CONSTRAINT `fk_catatan_populasi` FOREIGN KEY (`populasi_id`) REFERENCES `populasi` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Ketidakleluasaan untuk tabel `penjualan`
--
ALTER TABLE `penjualan`
  ADD CONSTRAINT `fk_penjualan_populasi` FOREIGN KEY (`populasi_id`) REFERENCES `populasi` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Ketidakleluasaan untuk tabel `populasi`
--
ALTER TABLE `populasi`
  ADD CONSTRAINT `fk_populasi_kandang` FOREIGN KEY (`kandang_id`) REFERENCES `kandang` (`id`) ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
