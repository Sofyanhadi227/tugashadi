<?php
declare(strict_types=1);

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function redirect(string $url): never
{
    header('Location: ' . $url);
    exit;
}

function flash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function get_flash(): ?array
{
    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $flash;
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf(): void
{
    $token = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(419);
        exit('Token keamanan tidak valid. Muat ulang halaman lalu coba lagi.');
    }
}

function format_rupiah(float|int|string $value): string
{
    return 'Rp ' . number_format((float) $value, 0, ',', '.');
}

function active_nav(string $file): string
{
    return basename($_SERVER['PHP_SELF']) === $file ? 'active' : '';
}

function recalc_population(PDO $pdo, int $populationId): void
{
    $stmt = $pdo->prepare('SELECT jumlah_awal FROM populasi WHERE id = ?');
    $stmt->execute([$populationId]);
    $population = $stmt->fetch();

    if (!$population) {
        return;
    }

    $stmt = $pdo->prepare('SELECT COALESCE(SUM(mati), 0) FROM pencatatan WHERE populasi_id = ?');
    $stmt->execute([$populationId]);
    $deaths = (int) $stmt->fetchColumn();

    $stmt = $pdo->prepare('SELECT COALESCE(SUM(jumlah_ayam), 0) FROM penjualan WHERE populasi_id = ?');
    $stmt->execute([$populationId]);
    $sold = (int) $stmt->fetchColumn();

    $alive = max(0, (int) $population['jumlah_awal'] - $deaths - $sold);

    $stmt = $pdo->prepare('
        SELECT berat_rata
        FROM pencatatan
        WHERE populasi_id = ? AND berat_rata > 0
        ORDER BY tanggal DESC, id DESC
        LIMIT 1
    ');
    $stmt->execute([$populationId]);
    $latestWeight = $stmt->fetchColumn();

    if ($latestWeight !== false) {
        $stmt = $pdo->prepare('UPDATE populasi SET jumlah_hidup = ?, berat_rata = ? WHERE id = ?');
        $stmt->execute([$alive, $latestWeight, $populationId]);
    } else {
        $stmt = $pdo->prepare('UPDATE populasi SET jumlah_hidup = ? WHERE id = ?');
        $stmt->execute([$alive, $populationId]);
    }
}
