<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_login();
$pdo = db();

$edit = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare('SELECT * FROM populasi WHERE id = ?');
    $stmt->execute([(int)$_GET['edit']]);
    $edit = $stmt->fetch();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? 'save';

    if ($action === 'delete') {
        $id = (int)$_POST['id'];
        $stmt = $pdo->prepare('DELETE FROM populasi WHERE id = ?');
        $stmt->execute([$id]);
        flash('success', 'Data populasi dihapus.');
        redirect('populasi.php');
    }

    $id = (int)($_POST['id'] ?? 0);
    $kandangId = (int)($_POST['kandang_id'] ?? 0);
    $tglMasuk = $_POST['tgl_masuk'] ?? date('Y-m-d');
    $jumlahAwal = max(0, (int)($_POST['jumlah_awal'] ?? 0));
    $berat = max(0, (float)($_POST['berat_rata'] ?? 0));
    $status = in_array($_POST['status'] ?? '', ['aktif', 'panen', 'selesai'], true) ? $_POST['status'] : 'aktif';

    if ($kandangId <= 0 || $jumlahAwal <= 0) {
        flash('error', 'Kandang dan jumlah awal wajib diisi dengan benar.');
        redirect('populasi.php');
    }

    if ($id > 0) {
        $stmt = $pdo->prepare('UPDATE populasi SET kandang_id=?, tgl_masuk=?, jumlah_awal=?, berat_rata=?, status=? WHERE id=?');
        $stmt->execute([$kandangId, $tglMasuk, $jumlahAwal, $berat, $status, $id]);
        recalc_population($pdo, $id);
    } else {
        $stmt = $pdo->prepare('INSERT INTO populasi (kandang_id, tgl_masuk, jumlah_awal, jumlah_hidup, berat_rata, status) VALUES (?, ?, ?, ?, ?, ?)');
        $stmt->execute([$kandangId, $tglMasuk, $jumlahAwal, $jumlahAwal, $berat, $status]);
    }

    flash('success', 'Data populasi berhasil disimpan.');
    redirect('populasi.php');
}

$kandang = $pdo->query("SELECT id, nama_kandang, kapasitas FROM kandang WHERE status='aktif' ORDER BY nama_kandang")->fetchAll();
$rows = $pdo->query("
    SELECT p.*, k.nama_kandang, k.kapasitas,
           DATEDIFF(CURDATE(), p.tgl_masuk) AS umur_hari,
           (p.jumlah_awal - p.jumlah_hidup) AS berkurang
    FROM populasi p
    JOIN kandang k ON k.id = p.kandang_id
    ORDER BY FIELD(p.status, 'aktif', 'panen', 'selesai'), p.tgl_masuk DESC
")->fetchAll();

$pageTitle = 'Populasi Ayam';
require __DIR__ . '/includes/header.php';
?>
<div class="grid cols-3">
    <section class="card">
        <div class="card-header"><h3><?= $edit ? 'Edit Populasi' : 'DOC Masuk / Populasi Baru' ?></h3></div>
        <div class="card-body">
            <?php if (!$kandang): ?>
                <div class="alert error">Tambahkan data kandang aktif terlebih dahulu.</div>
            <?php endif; ?>
            <form method="post">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="id" value="<?= (int)($edit['id'] ?? 0) ?>">
                <div class="form-group">
                    <label>Kandang</label>
                    <select name="kandang_id" required>
                        <option value="">Pilih kandang</option>
                        <?php foreach ($kandang as $item): ?>
                            <option value="<?= (int)$item['id'] ?>" <?= ((int)($edit['kandang_id'] ?? 0) === (int)$item['id']) ? 'selected' : '' ?>>
                                <?= e($item['nama_kandang']) ?> (kap. <?= number_format((int)$item['kapasitas'], 0, ',', '.') ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group" style="margin-top:14px">
                    <label>Tanggal DOC Masuk</label>
                    <input type="date" name="tgl_masuk" required value="<?= e($edit['tgl_masuk'] ?? date('Y-m-d')) ?>">
                </div>
                <div class="form-group" style="margin-top:14px">
                    <label>Jumlah Awal</label>
                    <input type="number" min="1" name="jumlah_awal" required value="<?= e((string)($edit['jumlah_awal'] ?? '')) ?>">
                </div>
                <div class="form-group" style="margin-top:14px">
                    <label>Bobot Awal/Rata-rata (kg)</label>
                    <input type="number" min="0" step="0.001" name="berat_rata" value="<?= e((string)($edit['berat_rata'] ?? '0.040')) ?>">
                </div>
                <div class="form-group" style="margin-top:14px">
                    <label>Status Batch</label>
                    <select name="status">
                        <?php foreach (['aktif'=>'Aktif','panen'=>'Panen','selesai'=>'Selesai'] as $key=>$label): ?>
                            <option value="<?= e($key) ?>" <?= (($edit['status'] ?? 'aktif') === $key) ? 'selected' : '' ?>><?= e($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="inline-actions" style="margin-top:18px">
                    <button class="btn btn-primary" type="submit">Simpan</button>
                    <?php if ($edit): ?><a class="btn btn-secondary" href="populasi.php">Batal</a><?php endif; ?>
                </div>
            </form>
        </div>
    </section>

    <section class="card" style="grid-column:span 2">
        <div class="card-header"><h3>Daftar Populasi</h3><span class="badge"><?= count($rows) ?> batch</span></div>
        <div class="card-body" style="padding:0">
            <?php if ($rows): ?>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>Kandang</th><th>DOC Masuk</th><th>Umur</th><th>Awal</th><th>Hidup</th><th>Bobot</th><th>Status</th><th>Aksi</th></tr></thead>
                        <tbody>
                        <?php foreach ($rows as $row): ?>
                            <tr>
                                <td><strong><?= e($row['nama_kandang']) ?></strong></td>
                                <td><?= e($row['tgl_masuk']) ?></td>
                                <td>H<?= max(0, (int)$row['umur_hari']) ?></td>
                                <td><?= number_format((int)$row['jumlah_awal'], 0, ',', '.') ?></td>
                                <td><?= number_format((int)$row['jumlah_hidup'], 0, ',', '.') ?></td>
                                <td><?= number_format((float)$row['berat_rata'], 3, ',', '.') ?> kg</td>
                                <td><span class="badge <?= $row['status'] === 'aktif' ? '' : 'gray' ?>"><?= e(ucfirst($row['status'])) ?></span></td>
                                <td>
                                    <div class="inline-actions">
                                        <a class="btn btn-secondary btn-sm" href="?edit=<?= (int)$row['id'] ?>">Edit</a>
                                        <form method="post">
                                            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
                                            <button class="btn btn-danger btn-sm" data-confirm="Hapus populasi beserta pencatatan terkait?" type="submit">Hapus</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty"><strong>Belum ada populasi</strong>Catat DOC masuk untuk memulai satu periode pemeliharaan.</div>
            <?php endif; ?>
        </div>
    </section>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
