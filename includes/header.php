<?php
declare(strict_types=1);

$pageTitle = $pageTitle ?? 'Dashboard';
$user = current_user();
$flashMessage = get_flash();
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?> - <?= e(APP_NAME) ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="app-shell">
    <aside class="sidebar" id="sidebar">
        <div class="brand">
            <div class="brand-icon">🐔</div>
            <div>
                <strong>BroilerFarm</strong>
                <small>Digital Management</small>
            </div>
        </div>

        <nav class="nav-list">
            <a class="<?= active_nav('index.php') ?>" href="index.php"><span>⌂</span> Dashboard</a>
            <a class="<?= active_nav('kandang.php') ?>" href="kandang.php"><span>▦</span> Data Kandang</a>
            <a class="<?= active_nav('populasi.php') ?>" href="populasi.php"><span>🐣</span> Populasi Ayam</a>
            <a class="<?= active_nav('pencatatan.php') ?>" href="pencatatan.php"><span>✎</span> Pencatatan Harian</a>
            <a class="<?= active_nav('pakan.php') ?>" href="pakan.php"><span>▣</span> Pakan</a>
            <a class="<?= active_nav('obat.php') ?>" href="obat.php"><span>✚</span> Obat & Vitamin</a>
            <a class="<?= active_nav('keuangan.php') ?>" href="keuangan.php"><span>Rp</span> Keuangan</a>
            <a class="<?= active_nav('penjualan.php') ?>" href="penjualan.php"><span>↗</span> Penjualan</a>
            <a class="<?= active_nav('laporan.php') ?>" href="laporan.php"><span>▤</span> Laporan</a>
            <a class="<?= active_nav('pengaturan.php') ?>" href="pengaturan.php"><span>⚙</span> Pengaturan</a>
        </nav>

        <div class="sidebar-user">
            <div class="avatar"><?= e(strtoupper(substr($user['name'] ?? 'U', 0, 1))) ?></div>
            <div>
                <strong><?= e($user['name'] ?? '') ?></strong>
                <small><?= e(ucfirst($user['role'] ?? '')) ?></small>
            </div>
            <a class="logout-link" href="logout.php" title="Keluar">↪</a>
        </div>
    </aside>

    <div class="main-area">
        <header class="topbar">
            <button type="button" class="menu-button" id="menuButton" aria-label="Buka menu">☰</button>
            <div>
                <h1><?= e($pageTitle) ?></h1>
                <p><?= e(date('l, d F Y')) ?></p>
            </div>
            <a class="top-profile" href="pengaturan.php">
                <span><?= e($user['name'] ?? '') ?></span>
                <div class="avatar small"><?= e(strtoupper(substr($user['name'] ?? 'U', 0, 1))) ?></div>
            </a>
        </header>

        <main class="content">
            <?php if ($flashMessage): ?>
                <div class="alert <?= e($flashMessage['type']) ?>">
                    <?= e($flashMessage['message']) ?>
                </div>
            <?php endif; ?>
