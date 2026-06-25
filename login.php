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

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    try {
        $pdo = db();
        $stmt = $pdo->prepare('SELECT id, name, email, password, role FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            unset($user['password']);
            session_regenerate_id(true);
            $_SESSION['user'] = $user;
            redirect('index.php');
        }

        $error = 'Email atau password salah.';
    } catch (Throwable $e) {
        $error = 'Database belum siap. Jalankan install.php terlebih dahulu.';
    }
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login - <?= e(APP_NAME) ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="login-page">


    <section class="login-panel">
        <div class="login-logo">🐔</div>
        <h2>Masuk ke BroilerFarm</h2>
        <p>Gunakan akun admin atau operator yang sudah dibuat.</p>

        <?php if ($flashMessage): ?>
            <div class="alert <?= e($flashMessage['type']) ?>"><?= e($flashMessage['message']) ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert error"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="post">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <div class="form-group">
                <label for="email">Email</label>
                <input id="email" type="email" name="email" required autocomplete="username" placeholder="admin@broilerfarm.local">
            </div>
            <div class="form-group" style="margin-top:14px">
                <label for="password">Password</label>
                <input id="password" type="password" name="password" required autocomplete="current-password" placeholder="Masukkan password">
            </div>
            <button class="btn btn-primary" type="submit">Login</button>
        </form>

        <p style="margin-top:20px;font-size:13px">
            Belum punya akun? <a href="register.php" style="color:#246b45;font-weight:800">Buat akun di sini</a>.
        </p>
    </section>
</div>
</body>
</html>
