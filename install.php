<?php
declare(strict_types=1);

require_once __DIR__ . '/config/app.php';

$status = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $serverDsn = sprintf('mysql:host=%s;port=%s;charset=utf8mb4', DB_HOST, DB_PORT);
        $pdo = new PDO($serverDsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);

        $pdo->exec('CREATE DATABASE IF NOT EXISTS `' . DB_NAME . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $pdo->exec('USE `' . DB_NAME . '`');

        $schema = [
            "CREATE TABLE IF NOT EXISTS users (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100) NOT NULL,
                email VARCHAR(150) NOT NULL UNIQUE,
                password VARCHAR(255) NOT NULL,
                role ENUM('admin','operator') NOT NULL DEFAULT 'operator',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB",
            "CREATE TABLE IF NOT EXISTS profil_peternakan (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                user_id INT UNSIGNED NOT NULL UNIQUE,
                nama_peternakan VARCHAR(150) NOT NULL DEFAULT 'Peternakan Ayam Broiler',
                pemilik VARCHAR(120) NULL,
                alamat TEXT NULL,
                lama_usaha_tahun INT UNSIGNED NOT NULL DEFAULT 0,
                frekuensi_panen_tahun INT UNSIGNED NOT NULL DEFAULT 0,
                jumlah_pekerja INT UNSIGNED NOT NULL DEFAULT 0,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                CONSTRAINT fk_profil_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB",
            "CREATE TABLE IF NOT EXISTS kandang (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                user_id INT UNSIGNED NOT NULL,
                nama_kandang VARCHAR(100) NOT NULL,
                kapasitas INT UNSIGNED NOT NULL DEFAULT 0,
                lokasi VARCHAR(180) NULL,
                status ENUM('aktif','nonaktif') NOT NULL DEFAULT 'aktif',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY uniq_kandang_user (user_id, nama_kandang),
                CONSTRAINT fk_kandang_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB",
            "CREATE TABLE IF NOT EXISTS populasi (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                user_id INT UNSIGNED NOT NULL,
                kandang_id INT UNSIGNED NOT NULL,
                tgl_masuk DATE NOT NULL,
                jumlah_awal INT UNSIGNED NOT NULL,
                jumlah_hidup INT UNSIGNED NOT NULL,
                berat_rata DECIMAL(8,3) NOT NULL DEFAULT 0,
                status ENUM('aktif','panen','selesai') NOT NULL DEFAULT 'aktif',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                CONSTRAINT fk_populasi_kandang FOREIGN KEY (kandang_id) REFERENCES kandang(id) ON DELETE RESTRICT,
                CONSTRAINT fk_populasi_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB",
            "CREATE TABLE IF NOT EXISTS pencatatan (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                user_id INT UNSIGNED NOT NULL,
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
                CONSTRAINT fk_catatan_populasi FOREIGN KEY (populasi_id) REFERENCES populasi(id) ON DELETE CASCADE,
                CONSTRAINT fk_catatan_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB",
            "CREATE TABLE IF NOT EXISTS pakan (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                user_id INT UNSIGNED NOT NULL,
                tanggal DATE NOT NULL,
                jenis_pakan VARCHAR(100) NOT NULL,
                stok_masuk DECIMAL(12,2) NOT NULL DEFAULT 0,
                pemakaian DECIMAL(12,2) NOT NULL DEFAULT 0,
                satuan VARCHAR(30) NOT NULL DEFAULT 'kg',
                keterangan VARCHAR(255) NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                CONSTRAINT fk_pakan_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB",
            "CREATE TABLE IF NOT EXISTS obat (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                user_id INT UNSIGNED NOT NULL,
                tanggal DATE NOT NULL,
                nama_obat VARCHAR(120) NOT NULL,
                jenis VARCHAR(80) NULL,
                stok_masuk DECIMAL(12,2) NOT NULL DEFAULT 0,
                pemakaian DECIMAL(12,2) NOT NULL DEFAULT 0,
                satuan VARCHAR(30) NOT NULL DEFAULT 'unit',
                keterangan VARCHAR(255) NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                CONSTRAINT fk_obat_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB",
            "CREATE TABLE IF NOT EXISTS keuangan (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                user_id INT UNSIGNED NOT NULL,
                tanggal DATE NOT NULL,
                jenis ENUM('pemasukan','pengeluaran') NOT NULL,
                kategori VARCHAR(100) NOT NULL,
                nominal DECIMAL(16,2) NOT NULL,
                keterangan VARCHAR(255) NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                CONSTRAINT fk_keuangan_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB",
            "CREATE TABLE IF NOT EXISTS penjualan (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                user_id INT UNSIGNED NOT NULL,
                populasi_id INT UNSIGNED NULL,
                tanggal DATE NOT NULL,
                jumlah_ayam INT UNSIGNED NOT NULL,
                berat_total DECIMAL(12,2) NOT NULL,
                harga_per_kg DECIMAL(14,2) NOT NULL,
                total_harga DECIMAL(16,2) NOT NULL,
                pembeli VARCHAR(150) NULL,
                keterangan VARCHAR(255) NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                CONSTRAINT fk_penjualan_populasi FOREIGN KEY (populasi_id) REFERENCES populasi(id) ON DELETE SET NULL,
                CONSTRAINT fk_penjualan_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB",

        ];

        foreach ($schema as $sql) {
            $pdo->exec($sql);
        }

        $adminEmail = 'admin@broilerfarm.local';
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->execute([$adminEmail]);

        if (!$stmt->fetch()) {
            $stmt = $pdo->prepare('INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)');
            $stmt->execute([
                'Administrator',
                $adminEmail,
                password_hash('admin123', PASSWORD_DEFAULT),
                'admin'
            ]);
            $adminId = (int) $pdo->lastInsertId();
            
            // Buat profil peternakan untuk admin
            $stmt = $pdo->prepare('INSERT INTO profil_peternakan (user_id, nama_peternakan, pemilik) VALUES (?, ?, ?)');
            $stmt->execute([$adminId, 'Peternakan Ayam Broiler', 'Administrator']);
        }

        $status = 'Instalasi berhasil. Login: admin@broilerfarm.local / admin123';
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Instalasi BroilerFarm</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body style="padding:30px">
<div class="card" style="max-width:720px;margin:30px auto">
    <div class="card-header"><h3>Instalasi BroilerFarm</h3></div>
    <div class="card-body">
        <p>Pastikan Apache dan MySQL pada XAMPP sudah aktif. Konfigurasi database dapat diubah pada <code>config/app.php</code>.</p>

        <?php if ($status): ?>
            <div class="alert success"><?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8') ?></div>
            <a class="btn btn-primary" href="login.php">Buka Halaman Login</a>
        <?php elseif ($error): ?>
            <div class="alert error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <form method="post" style="margin-top:20px">
            <button class="btn btn-primary" type="submit">Buat Database dan Tabel</button>
        </form>
    </div>
</div>
</body>
</html>
