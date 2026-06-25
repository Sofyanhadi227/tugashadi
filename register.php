<?php
declare(strict_types=1);

session_start();

require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

if (!empty($_SESSION['user'])) {
    redirect('index.php');
}

$error = '';
$flashMessage = get_flash();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';

    // Validasi input
    if (empty($name)) {
        $error = 'Nama tidak boleh kosong.';
    } elseif (strlen($name) > 100) {
        $error = 'Nama terlalu panjang (maksimal 100 karakter).';
    } elseif (empty($email)) {
        $error = 'Email tidak boleh kosong.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Format email tidak valid.';
    } elseif (strlen($email) > 150) {
        $error = 'Email terlalu panjang (maksimal 150 karakter).';
    } elseif (strlen($password) < 6) {
        $error = 'Password minimal 6 karakter.';
    } elseif ($password !== $password_confirm) {
        $error = 'Password dan konfirmasi password tidak sesuai.';
    }

            if (empty($error)) {
        try {
            $pdo = db();
            
            // Cek email sudah terdaftar
            $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $error = 'Email ini sudah terdaftar. Gunakan email lain.';
            } else {
                // Insert user baru dengan role 'operator'
                $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
                $stmt = $pdo->prepare('
                    INSERT INTO users (name, email, password, role) 
                    VALUES (?, ?, ?, ?)
                ');
                $stmt->execute([$name, $email, $hashedPassword, 'operator']);
                $userId = (int) $pdo->lastInsertId();
                
                // Buat profil peternakan otomatis untuk user baru
                $stmt = $pdo->prepare('
                    INSERT INTO profil_peternakan (user_id, nama_peternakan, pemilik) 
                    VALUES (?, ?, ?)
                ');
                $stmt->execute([$userId, 'Peternakan Ayam Broiler', $name]);
                
                flash('success', 'Akun berhasil dibuat! Silakan login dengan email dan password Anda.');
                redirect('login.php');
            }
        } catch (Throwable $e) {
            $error = 'Database belum siap. Jalankan install.php terlebih dahulu.';
        }
    }
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Daftar - <?= e(APP_NAME) ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="login-page">
    <section class="login-panel">
        <div class="login-logo">🐔</div>
        <h2>Buat Akun BroilerFarm</h2>
        <p>Daftar untuk mengelola peternakan broiler Anda.</p>

        <?php if ($flashMessage): ?>
            <div class="alert <?= e($flashMessage['type']) ?>"><?= e($flashMessage['message']) ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert error"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="post">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            
            <div class="form-group">
                <label for="name">Nama Lengkap</label>
                <input id="name" type="text" name="name" required placeholder="Masukkan nama lengkap" value="<?= isset($_POST['name']) ? e($_POST['name']) : '' ?>">
            </div>

            <div class="form-group">
                <label for="email">Email</label>
                <input id="email" type="email" name="email" required placeholder="Masukkan email" value="<?= isset($_POST['email']) ? e($_POST['email']) : '' ?>">
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input id="password" type="password" name="password" required placeholder="Minimal 6 karakter" autocomplete="new-password">
            </div>

            <div class="form-group">
                <label for="password_confirm">Konfirmasi Password</label>
                <input id="password_confirm" type="password" name="password_confirm" required placeholder="Ketik ulang password" autocomplete="new-password">
            </div>

            <button class="btn btn-primary" type="submit">Daftar</button>
        </form>

        <p style="margin-top:20px;font-size:13px">
            Sudah punya akun? <a href="login.php" style="color:#246b45;font-weight:800">Login di sini</a>.
        </p>
    </section>
</div>
</body>
</html>
