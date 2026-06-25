<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_login();
$pdo = db();

$edit = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare('SELECT * FROM obat WHERE id=?');
    $stmt->execute([(int)$_GET['edit']]);
    $edit = $stmt->fetch();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    if (($_POST['action'] ?? '') === 'delete') {
        $stmt = $pdo->prepare('DELETE FROM obat WHERE id=?');
        $stmt->execute([(int)$_POST['id']]);
        flash('success', 'Transaksi obat dihapus.');
        redirect('obat.php');
    }

    $id = (int)($_POST['id'] ?? 0);
    $data = [
        $_POST['tanggal'] ?? date('Y-m-d'),
        trim($_POST['nama_obat'] ?? ''),
        trim($_POST['jenis'] ?? ''),
        max(0, (float)($_POST['stok_masuk'] ?? 0)),
        max(0, (float)($_POST['pemakaian'] ?? 0)),
        trim($_POST['satuan'] ?? 'unit'),
        trim($_POST['keterangan'] ?? ''),
    ];

    if ($data[1] === '') {
        flash('error', 'Nama obat atau vitamin wajib diisi.');
        redirect('obat.php');
    }

    if ($id) {
        $stmt = $pdo->prepare('UPDATE obat SET tanggal=?, nama_obat=?, jenis=?, stok_masuk=?, pemakaian=?, satuan=?, keterangan=? WHERE id=?');
        $stmt->execute([...$data, $id]);
    } else {
        $stmt = $pdo->prepare('INSERT INTO obat (tanggal, nama_obat, jenis, stok_masuk, pemakaian, satuan, keterangan) VALUES (?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute($data);
    }
    flash('success', 'Data obat berhasil disimpan.');
    redirect('obat.php');
}

$stock = $pdo->query("
    SELECT nama_obat, satuan, SUM(stok_masuk) AS masuk, SUM(pemakaian) AS pakai, SUM(stok_masuk-pemakaian) AS sisa
    FROM obat GROUP BY nama_obat, satuan ORDER BY nama_obat
")->fetchAll();
$rows = $pdo->query('SELECT * FROM obat ORDER BY tanggal DESC, id DESC LIMIT 100')->fetchAll();

$pageTitle = 'Obat & Vitamin';
require __DIR__ . '/includes/header.php';
?>
<div class="grid cols-3">
    <section class="card">
        <div class="card-header"><h3><?= $edit ? 'Edit Transaksi' : 'Tambah Obat/Vitamin' ?></h3></div>
        <div class="card-body">
            <form method="post">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="id" value="<?= (int)($edit['id'] ?? 0) ?>">
                <div class="form-group"><label>Tanggal</label><input type="date" name="tanggal" value="<?= e($edit['tanggal'] ?? date('Y-m-d')) ?>" required></div>
                <div class="form-group" style="margin-top:12px"><label>Nama Obat/Vitamin</label><input name="nama_obat" value="<?= e($edit['nama_obat'] ?? '') ?>" required></div>
                <div class="form-group" style="margin-top:12px"><label>Jenis</label><input name="jenis" value="<?= e($edit['jenis'] ?? '') ?>" placeholder="Vitamin, antibiotik, vaksin, disinfektan"></div>
                <div class="form-grid" style="margin-top:12px">
                    <div class="form-group"><label>Stok Masuk</label><input type="number" min="0" step="0.01" name="stok_masuk" value="<?= e((string)($edit['stok_masuk'] ?? 0)) ?>"></div>
                    <div class="form-group"><label>Pemakaian</label><input type="number" min="0" step="0.01" name="pemakaian" value="<?= e((string)($edit['pemakaian'] ?? 0)) ?>"></div>
                </div>
                <div class="form-group" style="margin-top:12px"><label>Satuan</label><input name="satuan" value="<?= e($edit['satuan'] ?? 'unit') ?>" placeholder="botol, sachet, ml"></div>
                <div class="form-group" style="margin-top:12px"><label>Keterangan</label><textarea name="keterangan"><?= e($edit['keterangan'] ?? '') ?></textarea></div>
                <div class="inline-actions" style="margin-top:18px"><button class="btn btn-primary" type="submit">Simpan</button><?php if ($edit): ?><a class="btn btn-secondary" href="obat.php">Batal</a><?php endif; ?></div>
            </form>
        </div>
    </section>
    <section class="card" style="grid-column:span 2">
        <div class="card-header"><h3>Stok dan Riwayat Obat</h3></div>
        <div class="card-body">
            <div class="grid cols-3">
                <?php foreach (array_slice($stock, 0, 6) as $item): ?>
                    <div class="question-card"><small><?= e($item['nama_obat']) ?></small><strong style="font-size:24px;margin-top:6px"><?= number_format((float)$item['sisa'],2,',','.') ?> <?= e($item['satuan']) ?></strong></div>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="card-body" style="padding:0">
            <?php if ($rows): ?><div class="table-wrap"><table><thead><tr><th>Tanggal</th><th>Nama</th><th>Jenis</th><th>Masuk</th><th>Pakai</th><th>Keterangan</th><th>Aksi</th></tr></thead><tbody>
            <?php foreach ($rows as $row): ?><tr>
                <td><?= e($row['tanggal']) ?></td><td><strong><?= e($row['nama_obat']) ?></strong></td><td><?= e($row['jenis']) ?></td>
                <td><?= number_format((float)$row['stok_masuk'],2,',','.') ?> <?= e($row['satuan']) ?></td><td><?= number_format((float)$row['pemakaian'],2,',','.') ?> <?= e($row['satuan']) ?></td>
                <td><?= e($row['keterangan']) ?></td>
                <td><div class="inline-actions"><a class="btn btn-secondary btn-sm" href="?edit=<?= (int)$row['id'] ?>">Edit</a><form method="post"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$row['id'] ?>"><button class="btn btn-danger btn-sm" data-confirm="Hapus transaksi ini?" type="submit">Hapus</button></form></div></td>
            </tr><?php endforeach; ?></tbody></table></div><?php else: ?><div class="empty"><strong>Belum ada obat atau vitamin</strong>Tambahkan stok dan pemakaiannya.</div><?php endif; ?>
        </div>
    </section>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
