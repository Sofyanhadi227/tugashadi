<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_login();
$pdo = db();

$edit = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare('SELECT * FROM pencatatan WHERE id = ?');
    $stmt->execute([(int)$_GET['edit']]);
    $edit = $stmt->fetch();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? 'save';

    if ($action === 'delete') {
        $id = (int)$_POST['id'];
        $stmt = $pdo->prepare('SELECT populasi_id FROM pencatatan WHERE id = ?');
        $stmt->execute([$id]);
        $populationId = (int)$stmt->fetchColumn();

        $stmt = $pdo->prepare('DELETE FROM pencatatan WHERE id = ?');
        $stmt->execute([$id]);
        if ($populationId) {
            recalc_population($pdo, $populationId);
        }
        flash('success', 'Pencatatan harian dihapus.');
        redirect('pencatatan.php');
    }

    $id = (int)($_POST['id'] ?? 0);
    $populationId = (int)($_POST['populasi_id'] ?? 0);
    $tanggal = $_POST['tanggal'] ?? date('Y-m-d');
    $mati = max(0, (int)($_POST['mati'] ?? 0));
    $sakit = max(0, (int)($_POST['sakit'] ?? 0));
    $suhu = ($_POST['suhu'] ?? '') !== '' ? (float)$_POST['suhu'] : null;
    $kelembaban = ($_POST['kelembaban'] ?? '') !== '' ? (float)$_POST['kelembaban'] : null;
    $pakanKg = max(0, (float)($_POST['pakan_kg'] ?? 0));
    $minumLiter = max(0, (float)($_POST['minum_liter'] ?? 0));
    $berat = max(0, (float)($_POST['berat_rata'] ?? 0));
    $catatan = trim($_POST['catatan'] ?? '');

    if ($populationId <= 0) {
        flash('error', 'Pilih populasi yang dicatat.');
        redirect('pencatatan.php');
    }

    try {
        if ($id > 0) {
            $stmt = $pdo->prepare('
                UPDATE pencatatan
                SET populasi_id=?, tanggal=?, mati=?, sakit=?, suhu=?, kelembaban=?, pakan_kg=?, minum_liter=?, berat_rata=?, catatan=?
                WHERE id=?
            ');
            $stmt->execute([$populationId, $tanggal, $mati, $sakit, $suhu, $kelembaban, $pakanKg, $minumLiter, $berat, $catatan, $id]);
        } else {
            $stmt = $pdo->prepare('
                INSERT INTO pencatatan (populasi_id, tanggal, mati, sakit, suhu, kelembaban, pakan_kg, minum_liter, berat_rata, catatan)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ');
            $stmt->execute([$populationId, $tanggal, $mati, $sakit, $suhu, $kelembaban, $pakanKg, $minumLiter, $berat, $catatan]);
        }
        recalc_population($pdo, $populationId);
        flash('success', 'Pencatatan harian berhasil disimpan.');
    } catch (Throwable $e) {
        flash('error', 'Satu populasi hanya dapat memiliki satu pencatatan per tanggal.');
    }
    redirect('pencatatan.php');
}

$populations = $pdo->query("
    SELECT p.id, p.tgl_masuk, p.jumlah_hidup, k.nama_kandang
    FROM populasi p JOIN kandang k ON k.id=p.kandang_id
    WHERE p.status='aktif'
    ORDER BY k.nama_kandang, p.tgl_masuk DESC
")->fetchAll();

$filterPopulation = (int)($_GET['populasi_id'] ?? 0);
$where = '';
$params = [];
if ($filterPopulation > 0) {
    $where = 'WHERE pc.populasi_id = ?';
    $params[] = $filterPopulation;
}
$stmt = $pdo->prepare("
    SELECT pc.*, k.nama_kandang, p.tgl_masuk, DATEDIFF(pc.tanggal, p.tgl_masuk) AS umur_hari
    FROM pencatatan pc
    JOIN populasi p ON p.id=pc.populasi_id
    JOIN kandang k ON k.id=p.kandang_id
    $where
    ORDER BY pc.tanggal DESC, pc.id DESC
    LIMIT 100
");
$stmt->execute($params);
$rows = $stmt->fetchAll();

$pageTitle = 'Pencatatan Harian';
require __DIR__ . '/includes/header.php';
?>
<div class="grid cols-3">
    <section class="card">
        <div class="card-header"><h3><?= $edit ? 'Edit Catatan' : 'Tambah Catatan Harian' ?></h3></div>
        <div class="card-body">
            <?php if (!$populations): ?><div class="alert error">Belum ada populasi aktif.</div><?php endif; ?>
            <form method="post">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="id" value="<?= (int)($edit['id'] ?? 0) ?>">
                <div class="form-group">
                    <label>Populasi / Kandang</label>
                    <select name="populasi_id" required>
                        <option value="">Pilih populasi</option>
                        <?php foreach ($populations as $population): ?>
                            <option value="<?= (int)$population['id'] ?>" <?= ((int)($edit['populasi_id'] ?? 0) === (int)$population['id']) ? 'selected' : '' ?>>
                                <?= e($population['nama_kandang']) ?> · masuk <?= e($population['tgl_masuk']) ?> · hidup <?= number_format((int)$population['jumlah_hidup'],0,',','.') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group" style="margin-top:12px">
                    <label>Tanggal</label>
                    <input type="date" name="tanggal" required value="<?= e($edit['tanggal'] ?? date('Y-m-d')) ?>">
                </div>
                <div class="form-grid" style="margin-top:12px">
                    <div class="form-group"><label>Mati (ekor)</label><input type="number" min="0" name="mati" value="<?= e((string)($edit['mati'] ?? 0)) ?>"></div>
                    <div class="form-group"><label>Sakit (ekor)</label><input type="number" min="0" name="sakit" value="<?= e((string)($edit['sakit'] ?? 0)) ?>"></div>
                    <div class="form-group"><label>Suhu (°C)</label><input type="number" step="0.1" name="suhu" value="<?= e((string)($edit['suhu'] ?? '')) ?>"></div>
                    <div class="form-group"><label>Kelembaban (%)</label><input type="number" step="0.1" name="kelembaban" value="<?= e((string)($edit['kelembaban'] ?? '')) ?>"></div>
                    <div class="form-group"><label>Pakan (kg)</label><input type="number" min="0" step="0.01" name="pakan_kg" value="<?= e((string)($edit['pakan_kg'] ?? 0)) ?>"></div>
                    <div class="form-group"><label>Minum (liter)</label><input type="number" min="0" step="0.01" name="minum_liter" value="<?= e((string)($edit['minum_liter'] ?? 0)) ?>"></div>
                    <div class="form-group full"><label>Bobot rata-rata (kg)</label><input type="number" min="0" step="0.001" name="berat_rata" value="<?= e((string)($edit['berat_rata'] ?? 0)) ?>"></div>
                    <div class="form-group full"><label>Catatan / Tindakan</label><textarea name="catatan" placeholder="Gejala, penyakit, tindakan, atau informasi lain"><?= e($edit['catatan'] ?? '') ?></textarea></div>
                </div>
                <div class="inline-actions" style="margin-top:18px">
                    <button class="btn btn-primary" type="submit">Simpan</button>
                    <?php if ($edit): ?><a class="btn btn-secondary" href="pencatatan.php">Batal</a><?php endif; ?>
                </div>
            </form>
        </div>
    </section>

    <section class="card" style="grid-column:span 2">
        <div class="card-header">
            <h3>Riwayat Pencatatan</h3>
            <form class="filters" method="get">
                <select name="populasi_id" onchange="this.form.submit()">
                    <option value="0">Semua populasi</option>
                    <?php foreach ($populations as $population): ?>
                        <option value="<?= (int)$population['id'] ?>" <?= $filterPopulation === (int)$population['id'] ? 'selected' : '' ?>><?= e($population['nama_kandang']) ?> · <?= e($population['tgl_masuk']) ?></option>
                    <?php endforeach; ?>
                </select>
            </form>
        </div>
        <div class="card-body" style="padding:0">
            <?php if ($rows): ?>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>Tanggal</th><th>Kandang</th><th>Umur</th><th>Mati</th><th>Sakit</th><th>Suhu/RH</th><th>Pakan</th><th>Bobot</th><th>Aksi</th></tr></thead>
                        <tbody>
                        <?php foreach ($rows as $row): ?>
                            <tr>
                                <td><?= e($row['tanggal']) ?></td>
                                <td><strong><?= e($row['nama_kandang']) ?></strong></td>
                                <td>H<?= max(0, (int)$row['umur_hari']) ?></td>
                                <td><span class="<?= (int)$row['mati'] > 0 ? 'badge danger' : '' ?>"><?= (int)$row['mati'] ?></span></td>
                                <td><span class="<?= (int)$row['sakit'] > 0 ? 'badge warning' : '' ?>"><?= (int)$row['sakit'] ?></span></td>
                                <td><?= e((string)$row['suhu']) ?>°C / <?= e((string)$row['kelembaban']) ?>%</td>
                                <td><?= number_format((float)$row['pakan_kg'], 2, ',', '.') ?> kg</td>
                                <td><?= number_format((float)$row['berat_rata'], 3, ',', '.') ?> kg</td>
                                <td>
                                    <div class="inline-actions">
                                        <a class="btn btn-secondary btn-sm" href="?edit=<?= (int)$row['id'] ?>">Edit</a>
                                        <form method="post">
                                            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
                                            <button class="btn btn-danger btn-sm" data-confirm="Hapus catatan ini?" type="submit">Hapus</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty"><strong>Belum ada data harian</strong>Catat kondisi ayam setiap hari agar analisis tidak berdasarkan firasat.</div>
            <?php endif; ?>
        </div>
    </section>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
