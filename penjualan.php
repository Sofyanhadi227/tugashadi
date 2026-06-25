<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_login();
$pdo = db();

$edit = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare('SELECT * FROM penjualan WHERE id=?');
    $stmt->execute([(int)$_GET['edit']]);
    $edit = $stmt->fetch();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    if (($_POST['action'] ?? '') === 'delete') {
        $id = (int)$_POST['id'];
        $stmt = $pdo->prepare('SELECT populasi_id FROM penjualan WHERE id=?');
        $stmt->execute([$id]);
        $populationId = (int)$stmt->fetchColumn();

        $stmt = $pdo->prepare('DELETE FROM penjualan WHERE id=?');
        $stmt->execute([$id]);
        if ($populationId) recalc_population($pdo, $populationId);

        flash('success', 'Data penjualan dihapus.');
        redirect('penjualan.php');
    }

    $id = (int)($_POST['id'] ?? 0);
    $populationId = (int)($_POST['populasi_id'] ?? 0);
    $tanggal = $_POST['tanggal'] ?? date('Y-m-d');
    $jumlahAyam = max(0, (int)($_POST['jumlah_ayam'] ?? 0));
    $beratTotal = max(0, (float)($_POST['berat_total'] ?? 0));
    $hargaPerKg = max(0, (float)($_POST['harga_per_kg'] ?? 0));
    $total = $beratTotal * $hargaPerKg;
    $pembeli = trim($_POST['pembeli'] ?? '');
    $keterangan = trim($_POST['keterangan'] ?? '');

    if ($jumlahAyam <= 0 || $beratTotal <= 0 || $hargaPerKg <= 0) {
        flash('error', 'Jumlah ayam, berat, dan harga harus lebih dari nol.');
        redirect('penjualan.php');
    }

    $pdo->beginTransaction();
    try {
        if ($id) {
            $stmt = $pdo->prepare('SELECT populasi_id FROM penjualan WHERE id=?');
            $stmt->execute([$id]);
            $oldPopulationId = (int)$stmt->fetchColumn();

            $stmt = $pdo->prepare('UPDATE penjualan SET populasi_id=?, tanggal=?, jumlah_ayam=?, berat_total=?, harga_per_kg=?, total_harga=?, pembeli=?, keterangan=? WHERE id=?');
            $stmt->execute([$populationId ?: null, $tanggal, $jumlahAyam, $beratTotal, $hargaPerKg, $total, $pembeli, $keterangan, $id]);
            if ($oldPopulationId) recalc_population($pdo, $oldPopulationId);
        } else {
            $stmt = $pdo->prepare('INSERT INTO penjualan (populasi_id,tanggal,jumlah_ayam,berat_total,harga_per_kg,total_harga,pembeli,keterangan) VALUES (?,?,?,?,?,?,?,?)');
            $stmt->execute([$populationId ?: null, $tanggal, $jumlahAyam, $beratTotal, $hargaPerKg, $total, $pembeli, $keterangan]);

            $stmt = $pdo->prepare('INSERT INTO keuangan (tanggal,jenis,kategori,nominal,keterangan) VALUES (?,?,?,?,?)');
            $stmt->execute([$tanggal, 'pemasukan', 'Penjualan Ayam', $total, 'Penjualan kepada ' . ($pembeli ?: 'pembeli')]);
        }

        if ($populationId) recalc_population($pdo, $populationId);
        $pdo->commit();
        flash('success', 'Penjualan berhasil disimpan. Penjualan baru otomatis dicatat sebagai pemasukan.');
    } catch (Throwable $e) {
        $pdo->rollBack();
        flash('error', 'Data penjualan gagal disimpan.');
    }
    redirect('penjualan.php');
}

$populations = $pdo->query("
    SELECT p.id,p.tgl_masuk,p.jumlah_hidup,k.nama_kandang
    FROM populasi p JOIN kandang k ON k.id=p.kandang_id
    WHERE p.status IN ('aktif','panen')
    ORDER BY k.nama_kandang
")->fetchAll();

$rows = $pdo->query("
    SELECT s.*, k.nama_kandang
    FROM penjualan s
    LEFT JOIN populasi p ON p.id=s.populasi_id
    LEFT JOIN kandang k ON k.id=p.kandang_id
    ORDER BY s.tanggal DESC, s.id DESC
    LIMIT 100
")->fetchAll();

$totalSales = (float)$pdo->query('SELECT COALESCE(SUM(total_harga),0) FROM penjualan')->fetchColumn();
$totalBirds = (int)$pdo->query('SELECT COALESCE(SUM(jumlah_ayam),0) FROM penjualan')->fetchColumn();

$pageTitle = 'Penjualan Ayam';
require __DIR__ . '/includes/header.php';
?>
<div class="grid cols-3">
    <section class="card metric"><small>Total Penjualan</small><strong><?= format_rupiah($totalSales) ?></strong><em>Seluruh periode</em><div class="metric-icon">Rp</div></section>
    <section class="card metric"><small>Ayam Terjual</small><strong><?= number_format($totalBirds,0,',','.') ?></strong><em>ekor</em><div class="metric-icon">🐔</div></section>
    <section class="card metric"><small>Rata-rata Nilai/Ekor</small><strong><?= $totalBirds ? format_rupiah($totalSales/$totalBirds) : format_rupiah(0) ?></strong><em>estimasi</em><div class="metric-icon">≈</div></section>
</div>

<div class="grid cols-3" style="margin-top:18px">
    <section class="card">
        <div class="card-header"><h3><?= $edit ? 'Edit Penjualan' : 'Tambah Penjualan' ?></h3></div>
        <div class="card-body">
            <form method="post" id="saleForm">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="id" value="<?= (int)($edit['id'] ?? 0) ?>">
                <div class="form-group"><label>Populasi</label><select name="populasi_id"><option value="0">Tanpa populasi tertentu</option><?php foreach($populations as $p): ?><option value="<?= (int)$p['id'] ?>" <?= ((int)($edit['populasi_id']??0)===(int)$p['id'])?'selected':'' ?>><?= e($p['nama_kandang']) ?> · hidup <?= number_format((int)$p['jumlah_hidup'],0,',','.') ?></option><?php endforeach; ?></select></div>
                <div class="form-group" style="margin-top:12px"><label>Tanggal</label><input type="date" name="tanggal" value="<?= e($edit['tanggal'] ?? date('Y-m-d')) ?>" required></div>
                <div class="form-group" style="margin-top:12px"><label>Jumlah Ayam (ekor)</label><input type="number" min="1" name="jumlah_ayam" value="<?= e((string)($edit['jumlah_ayam'] ?? '')) ?>" required></div>
                <div class="form-group" style="margin-top:12px"><label>Berat Total (kg)</label><input id="beratTotal" type="number" min="0.01" step="0.01" name="berat_total" value="<?= e((string)($edit['berat_total'] ?? '')) ?>" required></div>
                <div class="form-group" style="margin-top:12px"><label>Harga per Kg</label><input id="hargaKg" type="number" min="1" step="1" name="harga_per_kg" value="<?= e((string)($edit['harga_per_kg'] ?? '')) ?>" required></div>
                <div class="question-card" style="margin-top:12px"><small>Estimasi total</small><strong id="saleTotal" style="font-size:22px;margin:5px 0 0"><?= format_rupiah((float)($edit['total_harga'] ?? 0)) ?></strong></div>
                <div class="form-group" style="margin-top:12px"><label>Pembeli</label><input name="pembeli" value="<?= e($edit['pembeli'] ?? '') ?>"></div>
                <div class="form-group" style="margin-top:12px"><label>Keterangan</label><textarea name="keterangan"><?= e($edit['keterangan'] ?? '') ?></textarea></div>
                <div class="inline-actions" style="margin-top:18px"><button class="btn btn-primary" type="submit">Simpan</button><?php if ($edit): ?><a class="btn btn-secondary" href="penjualan.php">Batal</a><?php endif; ?></div>
            </form>
        </div>
    </section>
    <section class="card" style="grid-column:span 2">
        <div class="card-header"><h3>Riwayat Penjualan</h3></div>
        <div class="card-body" style="padding:0">
            <?php if($rows): ?><div class="table-wrap"><table><thead><tr><th>Tanggal</th><th>Kandang</th><th>Pembeli</th><th>Jumlah</th><th>Berat</th><th>Harga/Kg</th><th>Total</th><th>Aksi</th></tr></thead><tbody>
            <?php foreach($rows as $row): ?><tr>
                <td><?= e($row['tanggal']) ?></td><td><?= e($row['nama_kandang'] ?? '-') ?></td><td><strong><?= e($row['pembeli'] ?: '-') ?></strong></td>
                <td><?= number_format((int)$row['jumlah_ayam'],0,',','.') ?> ekor</td><td><?= number_format((float)$row['berat_total'],2,',','.') ?> kg</td>
                <td><?= format_rupiah($row['harga_per_kg']) ?></td><td><strong><?= format_rupiah($row['total_harga']) ?></strong></td>
                <td><div class="inline-actions"><a class="btn btn-secondary btn-sm" href="?edit=<?= (int)$row['id'] ?>">Edit</a><form method="post"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$row['id'] ?>"><button class="btn btn-danger btn-sm" data-confirm="Hapus data penjualan ini?" type="submit">Hapus</button></form></div></td>
            </tr><?php endforeach; ?></tbody></table></div><?php else: ?><div class="empty"><strong>Belum ada penjualan</strong>Catat hasil panen dan transaksi pembeli.</div><?php endif; ?>
        </div>
    </section>
</div>
<?php
$pageScript = '<script>
const berat = document.getElementById("beratTotal");
const harga = document.getElementById("hargaKg");
const total = document.getElementById("saleTotal");
function hitung(){ const n=(Number(berat.value)||0)*(Number(harga.value)||0); total.textContent=new Intl.NumberFormat("id-ID",{style:"currency",currency:"IDR",maximumFractionDigits:0}).format(n); }
berat?.addEventListener("input",hitung); harga?.addEventListener("input",hitung);
</script>';
require __DIR__ . '/includes/footer.php';
?>
