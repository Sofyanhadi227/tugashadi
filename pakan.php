<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_login();
$pdo = db();

$edit = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare('SELECT * FROM pakan WHERE id=?');
    $stmt->execute([(int)$_GET['edit']]);
    $edit = $stmt->fetch();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    if (($_POST['action'] ?? '') === 'delete') {
        $stmt = $pdo->prepare('DELETE FROM pakan WHERE id=?');
        $stmt->execute([(int)$_POST['id']]);
        flash('success', 'Transaksi pakan dihapus.');
        redirect('pakan.php');
    }

    $id = (int)($_POST['id'] ?? 0);
    $data = [
        $_POST['tanggal'] ?? date('Y-m-d'),
        trim($_POST['jenis_pakan'] ?? ''),
        max(0, (float)($_POST['stok_masuk'] ?? 0)),
        max(0, (float)($_POST['pemakaian'] ?? 0)),
        trim($_POST['satuan'] ?? 'kg'),
        trim($_POST['keterangan'] ?? ''),
    ];

    if ($data[1] === '') {
        flash('error', 'Jenis pakan wajib diisi.');
        redirect('pakan.php');
    }

    if ($id) {
        $stmt = $pdo->prepare('UPDATE pakan SET tanggal=?, jenis_pakan=?, stok_masuk=?, pemakaian=?, satuan=?, keterangan=? WHERE id=?');
        $stmt->execute([...$data, $id]);
    } else {
        $stmt = $pdo->prepare('INSERT INTO pakan (tanggal, jenis_pakan, stok_masuk, pemakaian, satuan, keterangan) VALUES (?, ?, ?, ?, ?, ?)');
        $stmt->execute($data);
    }
    flash('success', 'Data pakan berhasil disimpan.');
    redirect('pakan.php');
}

$stock = $pdo->query("
    SELECT jenis_pakan, satuan, SUM(stok_masuk) AS masuk, SUM(pemakaian) AS pakai, SUM(stok_masuk-pemakaian) AS sisa
    FROM pakan GROUP BY jenis_pakan, satuan ORDER BY jenis_pakan
")->fetchAll();
$rows = $pdo->query('SELECT * FROM pakan ORDER BY tanggal DESC, id DESC LIMIT 100')->fetchAll();
$todayUsage = (float)$pdo->query("SELECT COALESCE(SUM(pemakaian),0) FROM pakan WHERE tanggal=CURDATE()")->fetchColumn();

$pageTitle = 'Pakan';
require __DIR__ . '/includes/header.php';
?>
<div class="grid cols-4">
    <section class="card metric"><small>Pemakaian Hari Ini</small><strong><?= number_format($todayUsage, 2, ',', '.') ?></strong><em>kg/unit</em><div class="metric-icon">▣</div></section>
    <?php foreach (array_slice($stock, 0, 3) as $item): ?>
        <section class="card metric">
            <small>Stok <?= e($item['jenis_pakan']) ?></small>
            <strong><?= number_format((float)$item['sisa'], 2, ',', '.') ?></strong>
            <em><?= e($item['satuan']) ?></em>
            <div class="metric-icon">◫</div>
        </section>
    <?php endforeach; ?>
</div>

<div class="grid cols-3" style="margin-top:18px">
    <section class="card">
        <div class="card-header"><h3><?= $edit ? 'Edit Transaksi' : 'Tambah Transaksi Pakan' ?></h3></div>
        <div class="card-body">
            <form method="post">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="id" value="<?= (int)($edit['id'] ?? 0) ?>">
                <div class="form-group"><label>Tanggal</label><input type="date" name="tanggal" value="<?= e($edit['tanggal'] ?? date('Y-m-d')) ?>" required></div>
                <div class="form-group" style="margin-top:12px"><label>Jenis Pakan</label><input name="jenis_pakan" list="jenisPakan" value="<?= e($edit['jenis_pakan'] ?? '') ?>" placeholder="Starter / Grower / Finisher" required><datalist id="jenisPakan"><option>Starter</option><option>Grower</option><option>Finisher</option></datalist></div>
                <div class="form-grid" style="margin-top:12px">
                    <div class="form-group"><label>Stok Masuk</label><input type="number" min="0" step="0.01" name="stok_masuk" value="<?= e((string)($edit['stok_masuk'] ?? 0)) ?>"></div>
                    <div class="form-group"><label>Pemakaian</label><input type="number" min="0" step="0.01" name="pemakaian" value="<?= e((string)($edit['pemakaian'] ?? 0)) ?>"></div>
                </div>
                <div class="form-group" style="margin-top:12px"><label>Satuan</label><input name="satuan" value="<?= e($edit['satuan'] ?? 'kg') ?>"></div>
                <div class="form-group" style="margin-top:12px"><label>Keterangan</label><textarea name="keterangan"><?= e($edit['keterangan'] ?? '') ?></textarea></div>
                <div class="inline-actions" style="margin-top:18px"><button class="btn btn-primary" type="submit">Simpan</button><?php if ($edit): ?><a class="btn btn-secondary" href="pakan.php">Batal</a><?php endif; ?></div>
            </form>
        </div>
    </section>
    <section class="card" style="grid-column:span 2">
        <div class="card-header"><h3>Riwayat Pakan</h3></div>
        <div class="card-body" style="padding:0">
            <?php if ($rows): ?><div class="table-wrap"><table><thead><tr><th>Tanggal</th><th>Jenis</th><th>Masuk</th><th>Pemakaian</th><th>Keterangan</th><th>Aksi</th></tr></thead><tbody>
            <?php foreach ($rows as $row): ?><tr>
                <td><?= e($row['tanggal']) ?></td><td><strong><?= e($row['jenis_pakan']) ?></strong></td>
                <td><?= number_format((float)$row['stok_masuk'],2,',','.') ?> <?= e($row['satuan']) ?></td>
                <td><?= number_format((float)$row['pemakaian'],2,',','.') ?> <?= e($row['satuan']) ?></td>
                <td><?= e($row['keterangan']) ?></td>
                <td><div class="inline-actions"><a class="btn btn-secondary btn-sm" href="?edit=<?= (int)$row['id'] ?>">Edit</a><form method="post"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$row['id'] ?>"><button class="btn btn-danger btn-sm" data-confirm="Hapus transaksi ini?" type="submit">Hapus</button></form></div></td>
            </tr><?php endforeach; ?></tbody></table></div><?php else: ?><div class="empty"><strong>Belum ada transaksi pakan</strong>Masukkan stok atau pemakaian pakan.</div><?php endif; ?>
        </div>
    </section>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
