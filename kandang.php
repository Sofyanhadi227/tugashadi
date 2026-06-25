<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_login();
$pdo = db();

$edit = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare('SELECT * FROM kandang WHERE id = ?');
    $stmt->execute([(int)$_GET['edit']]);
    $edit = $stmt->fetch();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? 'save';

    if ($action === 'delete') {
        try {
            $stmt = $pdo->prepare('DELETE FROM kandang WHERE id = ?');
            $stmt->execute([(int)$_POST['id']]);
            flash('success', 'Data kandang dihapus.');
        } catch (Throwable $e) {
            flash('error', 'Kandang tidak dapat dihapus karena masih dipakai oleh data populasi.');
        }
        redirect('kandang.php');
    }

    $id = (int)($_POST['id'] ?? 0);
    $nama = trim($_POST['nama_kandang'] ?? '');
    $kapasitas = max(0, (int)($_POST['kapasitas'] ?? 0));
    $lokasi = trim($_POST['lokasi'] ?? '');
    $status = in_array($_POST['status'] ?? '', ['aktif', 'nonaktif'], true) ? $_POST['status'] : 'aktif';

    if ($nama === '') {
        flash('error', 'Nama kandang wajib diisi.');
        redirect('kandang.php');
    }

    try {
        if ($id > 0) {
            $stmt = $pdo->prepare('UPDATE kandang SET nama_kandang=?, kapasitas=?, lokasi=?, status=? WHERE id=?');
            $stmt->execute([$nama, $kapasitas, $lokasi, $status, $id]);
        } else {
            $stmt = $pdo->prepare('INSERT INTO kandang (nama_kandang, kapasitas, lokasi, status) VALUES (?, ?, ?, ?)');
            $stmt->execute([$nama, $kapasitas, $lokasi, $status]);
        }
        flash('success', 'Data kandang berhasil disimpan.');
    } catch (Throwable $e) {
        flash('error', 'Nama kandang sudah digunakan atau data tidak valid.');
    }
    redirect('kandang.php');
}

$rows = $pdo->query("
    SELECT k.*, COALESCE(SUM(CASE WHEN p.status='aktif' THEN p.jumlah_hidup ELSE 0 END), 0) AS jumlah_ayam
    FROM kandang k
    LEFT JOIN populasi p ON p.kandang_id = k.id
    GROUP BY k.id
    ORDER BY k.nama_kandang
")->fetchAll();

$pageTitle = 'Data Kandang';
require __DIR__ . '/includes/header.php';
?>
<div class="grid cols-3">
    <section class="card">
        <div class="card-header"><h3><?= $edit ? 'Edit Kandang' : 'Tambah Kandang' ?></h3></div>
        <div class="card-body">
            <form method="post">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="id" value="<?= (int)($edit['id'] ?? 0) ?>">
                <div class="form-group">
                    <label>Nama Kandang</label>
                    <input name="nama_kandang" required value="<?= e($edit['nama_kandang'] ?? '') ?>" placeholder="Contoh: Kandang 1">
                </div>
                <div class="form-group" style="margin-top:14px">
                    <label>Kapasitas Ayam</label>
                    <input type="number" min="0" name="kapasitas" required value="<?= e((string)($edit['kapasitas'] ?? 0)) ?>">
                </div>
                <div class="form-group" style="margin-top:14px">
                    <label>Lokasi</label>
                    <input name="lokasi" value="<?= e($edit['lokasi'] ?? '') ?>" placeholder="Blok atau alamat kandang">
                </div>
                <div class="form-group" style="margin-top:14px">
                    <label>Status</label>
                    <select name="status">
                        <option value="aktif" <?= (($edit['status'] ?? 'aktif') === 'aktif') ? 'selected' : '' ?>>Aktif</option>
                        <option value="nonaktif" <?= (($edit['status'] ?? '') === 'nonaktif') ? 'selected' : '' ?>>Nonaktif</option>
                    </select>
                </div>
                <div class="inline-actions" style="margin-top:18px">
                    <button class="btn btn-primary" type="submit">Simpan</button>
                    <?php if ($edit): ?><a class="btn btn-secondary" href="kandang.php">Batal</a><?php endif; ?>
                </div>
            </form>
        </div>
    </section>

    <section class="card" style="grid-column:span 2">
        <div class="card-header"><h3>Daftar Kandang</h3><span class="badge"><?= count($rows) ?> kandang</span></div>
        <div class="card-body" style="padding:0">
            <?php if ($rows): ?>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>Kandang</th><th>Kapasitas</th><th>Ayam Aktif</th><th>Terisi</th><th>Status</th><th>Aksi</th></tr></thead>
                        <tbody>
                        <?php foreach ($rows as $row): $percent = $row['kapasitas'] > 0 ? min(100, ($row['jumlah_ayam'] / $row['kapasitas']) * 100) : 0; ?>
                            <tr>
                                <td><strong><?= e($row['nama_kandang']) ?></strong><br><small><?= e($row['lokasi']) ?></small></td>
                                <td><?= number_format((int)$row['kapasitas'], 0, ',', '.') ?></td>
                                <td><?= number_format((int)$row['jumlah_ayam'], 0, ',', '.') ?></td>
                                <td style="min-width:130px">
                                    <div class="progress"><span style="width:<?= number_format($percent, 2, '.', '') ?>%"></span></div>
                                    <small><?= number_format($percent, 1, ',', '.') ?>%</small>
                                </td>
                                <td><span class="badge <?= $row['status'] === 'aktif' ? '' : 'gray' ?>"><?= e(ucfirst($row['status'])) ?></span></td>
                                <td>
                                    <div class="inline-actions">
                                        <a class="btn btn-secondary btn-sm" href="?edit=<?= (int)$row['id'] ?>">Edit</a>
                                        <form method="post">
                                            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
                                            <button class="btn btn-danger btn-sm" data-confirm="Hapus kandang ini?" type="submit">Hapus</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty"><strong>Belum ada kandang</strong>Tambahkan kandang pertama sebelum menaruh ribuan ayam ke dalam kehampaan database.</div>
            <?php endif; ?>
        </div>
    </section>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
