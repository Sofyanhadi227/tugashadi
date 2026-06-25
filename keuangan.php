<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_login();
$pdo = db();

$edit = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare('SELECT * FROM keuangan WHERE id=?');
    $stmt->execute([(int)$_GET['edit']]);
    $edit = $stmt->fetch();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    if (($_POST['action'] ?? '') === 'delete') {
        $stmt = $pdo->prepare('DELETE FROM keuangan WHERE id=?');
        $stmt->execute([(int)$_POST['id']]);
        flash('success', 'Transaksi keuangan dihapus.');
        redirect('keuangan.php');
    }

    $id = (int)($_POST['id'] ?? 0);
    $jenis = in_array($_POST['jenis'] ?? '', ['pemasukan','pengeluaran'], true) ? $_POST['jenis'] : 'pengeluaran';
    $data = [
        $_POST['tanggal'] ?? date('Y-m-d'),
        $jenis,
        trim($_POST['kategori'] ?? ''),
        max(0, (float)($_POST['nominal'] ?? 0)),
        trim($_POST['keterangan'] ?? ''),
    ];

    if ($data[2] === '' || $data[3] <= 0) {
        flash('error', 'Kategori dan nominal wajib diisi.');
        redirect('keuangan.php');
    }

    if ($id) {
        $stmt = $pdo->prepare('UPDATE keuangan SET tanggal=?, jenis=?, kategori=?, nominal=?, keterangan=? WHERE id=?');
        $stmt->execute([...$data, $id]);
    } else {
        $stmt = $pdo->prepare('INSERT INTO keuangan (tanggal, jenis, kategori, nominal, keterangan) VALUES (?, ?, ?, ?, ?)');
        $stmt->execute($data);
    }

    flash('success', 'Transaksi keuangan berhasil disimpan.');
    redirect('keuangan.php');
}

$month = $_GET['bulan'] ?? date('Y-m');
if (!preg_match('/^\d{4}-\d{2}$/', $month)) $month = date('Y-m');

$stmt = $pdo->prepare("SELECT * FROM keuangan WHERE DATE_FORMAT(tanggal,'%Y-%m')=? ORDER BY tanggal DESC, id DESC");
$stmt->execute([$month]);
$rows = $stmt->fetchAll();

$stmt = $pdo->prepare("SELECT jenis, COALESCE(SUM(nominal),0) total FROM keuangan WHERE DATE_FORMAT(tanggal,'%Y-%m')=? GROUP BY jenis");
$stmt->execute([$month]);
$summary = ['pemasukan'=>0, 'pengeluaran'=>0];
foreach ($stmt->fetchAll() as $s) $summary[$s['jenis']] = (float)$s['total'];
$profit = $summary['pemasukan'] - $summary['pengeluaran'];

$pageTitle = 'Keuangan';
require __DIR__ . '/includes/header.php';
?>
<div class="grid cols-3">
    <section class="card metric"><small>Total Pemasukan</small><strong><?= format_rupiah($summary['pemasukan']) ?></strong><em><?= e($month) ?></em><div class="metric-icon">↗</div></section>
    <section class="card metric"><small>Total Pengeluaran</small><strong><?= format_rupiah($summary['pengeluaran']) ?></strong><em><?= e($month) ?></em><div class="metric-icon">↘</div></section>
    <section class="card metric"><small>Laba / Rugi</small><strong><?= format_rupiah($profit) ?></strong><em><?= $profit >= 0 ? 'Surplus' : 'Defisit' ?></em><div class="metric-icon">Rp</div></section>
</div>

<div class="grid cols-3" style="margin-top:18px">
    <section class="card">
        <div class="card-header"><h3><?= $edit ? 'Edit Transaksi' : 'Tambah Transaksi' ?></h3></div>
        <div class="card-body">
            <form method="post">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="id" value="<?= (int)($edit['id'] ?? 0) ?>">
                <div class="form-group"><label>Tanggal</label><input type="date" name="tanggal" value="<?= e($edit['tanggal'] ?? date('Y-m-d')) ?>" required></div>
                <div class="form-group" style="margin-top:12px"><label>Jenis</label><select name="jenis"><option value="pemasukan" <?= (($edit['jenis'] ?? '')==='pemasukan')?'selected':'' ?>>Pemasukan</option><option value="pengeluaran" <?= (($edit['jenis'] ?? 'pengeluaran')==='pengeluaran')?'selected':'' ?>>Pengeluaran</option></select></div>
                <div class="form-group" style="margin-top:12px"><label>Kategori</label><input name="kategori" list="kategoriKeuangan" value="<?= e($edit['kategori'] ?? '') ?>" required><datalist id="kategoriKeuangan"><option>Penjualan Ayam</option><option>Pakan</option><option>Obat dan Vitamin</option><option>DOC</option><option>Gaji Pekerja</option><option>Listrik</option><option>Transportasi</option><option>Perawatan Kandang</option></datalist></div>
                <div class="form-group" style="margin-top:12px"><label>Nominal</label><input type="number" min="1" step="1" name="nominal" value="<?= e((string)($edit['nominal'] ?? '')) ?>" required></div>
                <div class="form-group" style="margin-top:12px"><label>Keterangan</label><textarea name="keterangan"><?= e($edit['keterangan'] ?? '') ?></textarea></div>
                <div class="inline-actions" style="margin-top:18px"><button class="btn btn-primary" type="submit">Simpan</button><?php if ($edit): ?><a class="btn btn-secondary" href="keuangan.php">Batal</a><?php endif; ?></div>
            </form>
        </div>
    </section>

    <section class="card" style="grid-column:span 2">
        <div class="card-header">
            <h3>Riwayat Keuangan</h3>
            <form method="get" class="filters"><input type="month" name="bulan" value="<?= e($month) ?>" onchange="this.form.submit()"></form>
        </div>
        <div class="card-body" style="padding:0">
            <?php if ($rows): ?><div class="table-wrap"><table><thead><tr><th>Tanggal</th><th>Jenis</th><th>Kategori</th><th>Nominal</th><th>Keterangan</th><th>Aksi</th></tr></thead><tbody>
            <?php foreach ($rows as $row): ?><tr>
                <td><?= e($row['tanggal']) ?></td><td><span class="badge <?= $row['jenis']==='pengeluaran'?'danger':'' ?>"><?= e(ucfirst($row['jenis'])) ?></span></td>
                <td><strong><?= e($row['kategori']) ?></strong></td><td><?= format_rupiah($row['nominal']) ?></td><td><?= e($row['keterangan']) ?></td>
                <td><div class="inline-actions"><a class="btn btn-secondary btn-sm" href="?edit=<?= (int)$row['id'] ?>">Edit</a><form method="post"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$row['id'] ?>"><button class="btn btn-danger btn-sm" data-confirm="Hapus transaksi ini?" type="submit">Hapus</button></form></div></td>
            </tr><?php endforeach; ?></tbody></table></div><?php else: ?><div class="empty"><strong>Belum ada transaksi bulan ini</strong>Keuangan yang tidak dicatat biasanya berubah menjadi cerita rakyat.</div><?php endif; ?>
        </div>
    </section>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
