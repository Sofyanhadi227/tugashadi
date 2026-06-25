<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_login();
$pdo = db();

$type = $_GET['jenis'] ?? 'ringkasan';
$allowed = ['ringkasan','populasi','kesehatan','pakan_obat','keuangan','penjualan'];
if (!in_array($type, $allowed, true)) $type = 'ringkasan';

// Handle periode selection
$periodeType = $_GET['periode'] ?? 'bulan';
$periodeType = in_array($periodeType, ['bulan','quartal'], true) ? $periodeType : 'bulan';

$month = $_GET['bulan'] ?? date('Y-m');
if (!preg_match('/^\d{4}-\d{2}$/', $month)) $month = date('Y-m');

$year = $_GET['tahun'] ?? date('Y');
$year = (int)$year;
$quartal = $_GET['quartal'] ?? '1';
$quartal = in_array($quartal, ['1','2','3','4'], true) ? $quartal : '1';

// Function to get quartal date range
function getQuartalRange($year, $quartal) {
    $quarters = [
        '1' => ['01', '03'],
        '2' => ['04', '06'],
        '3' => ['07', '09'],
        '4' => ['10', '12']
    ];
    $months = $quarters[$quartal];
    return [
        'start' => "$year-{$months[0]}-01",
        'end' => "$year-{$months[1]}-" . (in_array($months[1], ['01','03','05','07','08','10','12']) ? '31' : (in_array($months[1], ['04','06','09','11']) ? '30' : '28')),
        'label' => "Q$quartal $year"
    ];
}

// Get period parameters
$periodParams = [];
$periodLabel = '';
if ($periodeType === 'quartal') {
    $qRange = getQuartalRange($year, $quartal);
    $periodParams = [$qRange['start'], $qRange['end']];
    $periodLabel = $qRange['label'];
    $dateFilter = "tanggal BETWEEN ? AND ?";
} else {
    $periodParams = [$month];
    $periodLabel = $month;
    $dateFilter = "DATE_FORMAT(tanggal,'%Y-%m')=?";
}

$profile = $pdo->query('SELECT * FROM profil_peternakan WHERE id=1')->fetch();

$totalKandang = (int)$pdo->query("SELECT COUNT(*) FROM kandang WHERE status='aktif'")->fetchColumn();
$totalAyam = (int)$pdo->query("SELECT COALESCE(SUM(jumlah_hidup),0) FROM populasi WHERE status='aktif'")->fetchColumn();

$stmt = $pdo->prepare("SELECT COALESCE(SUM(mati),0) FROM pencatatan WHERE $dateFilter");
$stmt->execute($periodParams);
$deaths = (int)$stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT jenis,COALESCE(SUM(nominal),0) total FROM keuangan WHERE $dateFilter GROUP BY jenis");
$stmt->execute($periodParams);
$finance = ['pemasukan'=>0,'pengeluaran'=>0];
foreach($stmt->fetchAll() as $f) $finance[$f['jenis']] = (float)$f['total'];

$data = [];
if ($type === 'populasi') {
    $data = $pdo->query("SELECT p.*,k.nama_kandang,DATEDIFF(CURDATE(),p.tgl_masuk) umur_hari FROM populasi p JOIN kandang k ON k.id=p.kandang_id ORDER BY p.tgl_masuk DESC")->fetchAll();
} elseif ($type === 'kesehatan') {
    $stmt = $pdo->prepare("SELECT pc.*,k.nama_kandang FROM pencatatan pc JOIN populasi p ON p.id=pc.populasi_id JOIN kandang k ON k.id=p.kandang_id WHERE $dateFilter ORDER BY pc.tanggal");
    $stmt->execute($periodParams); $data = $stmt->fetchAll();
} elseif ($type === 'pakan_obat') {
    $stmt = $pdo->prepare("SELECT 'Pakan' kategori,tanggal,jenis_pakan nama,stok_masuk,pemakaian,satuan,keterangan FROM pakan WHERE $dateFilter UNION ALL SELECT 'Obat' kategori,tanggal,nama_obat nama,stok_masuk,pemakaian,satuan,keterangan FROM obat WHERE $dateFilter ORDER BY tanggal");
    $stmt->execute(array_merge($periodParams, $periodParams)); $data = $stmt->fetchAll();
} elseif ($type === 'keuangan') {
    $stmt = $pdo->prepare("SELECT * FROM keuangan WHERE $dateFilter ORDER BY tanggal");
    $stmt->execute($periodParams); $data = $stmt->fetchAll();
} elseif ($type === 'penjualan') {
    $stmt = $pdo->prepare("SELECT s.*,k.nama_kandang FROM penjualan s LEFT JOIN populasi p ON p.id=s.populasi_id LEFT JOIN kandang k ON k.id=p.kandang_id WHERE $dateFilter ORDER BY s.tanggal");
    $stmt->execute($periodParams); $data = $stmt->fetchAll();
}

$pageTitle = 'Laporan';
require __DIR__ . '/includes/header.php';
?>
<div class="report-header">
    <h1><?= e($profile['nama_peternakan'] ?? 'Peternakan Ayam Broiler') ?></h1>
    <p>Laporan <?= e(ucwords(str_replace('_',' ',$type))) ?> · Periode <?= e($periodLabel) ?></p>
</div>

<div class="page-actions no-print">
    <div><h2>Laporan Peternakan</h2><p>Pilih jenis laporan, lalu gunakan tombol cetak untuk menyimpan sebagai PDF.</p></div>
    <button class="btn btn-primary" type="button" onclick="window.print()">Cetak / Simpan PDF</button>
</div>

<section class="card no-print" style="margin-bottom:18px">
    <div class="card-body">
        <form method="get" class="filters" id="filterForm">
            <input type="hidden" name="periode" value="<?= e($periodeType) ?>" id="periodeInput">
            <input type="hidden" name="bulan" value="<?= e($month) ?>" id="bulanInput">
            <input type="hidden" name="tahun" value="<?= e((string)$year) ?>" id="tahunInput">
            <input type="hidden" name="quartal" value="<?= e($quartal) ?>" id="quartalInput">
            
            <select name="jenis">
                <?php foreach(['ringkasan'=>'Ringkasan','populasi'=>'Populasi','kesehatan'=>'Kesehatan','pakan_obat'=>'Pakan & Obat','keuangan'=>'Keuangan','penjualan'=>'Penjualan'] as $key=>$label): ?>
                    <option value="<?= e($key) ?>" <?= $type===$key?'selected':'' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
            
            <select name="periode" onchange="updatePeriodeFields(this.value)">
                <option value="bulan" <?= $periodeType==='bulan'?'selected':'' ?>>Per Bulan</option>
                <option value="quartal" <?= $periodeType==='quartal'?'selected':'' ?>>Per Quartal</option>
            </select>
            
            <div id="periodeFields">
                <?php if($periodeType === 'bulan'): ?>
                    <input type="month" name="bulan" id="bulanField" value="<?= e($month) ?>">
                <?php else: ?>
                    <select name="tahun" id="tahunField">
                        <?php for($y = date('Y') - 5; $y <= date('Y') + 1; $y++): ?>
                            <option value="<?= $y ?>" <?= $year==$y?'selected':'' ?>><?= $y ?></option>
                        <?php endfor; ?>
                    </select>
                    <select name="quartal" id="quartalField">
                        <option value="1" <?= $quartal==='1'?'selected':'' ?>>Q1 (Jan-Mar)</option>
                        <option value="2" <?= $quartal==='2'?'selected':'' ?>>Q2 (Apr-Jun)</option>
                        <option value="3" <?= $quartal==='3'?'selected':'' ?>>Q3 (Jul-Sep)</option>
                        <option value="4" <?= $quartal==='4'?'selected':'' ?>>Q4 (Oct-Dec)</option>
                    </select>
                <?php endif; ?>
            </div>
            
            <button class="btn btn-secondary" type="submit">Tampilkan</button>
        </form>
        
        <script>
        function updatePeriodeFields(periodeValue) {
            const fieldsDiv = document.getElementById('periodeFields');
            const periodeInput = document.getElementById('periodeInput');
            periodeInput.value = periodeValue;
            
            if (periodeValue === 'bulan') {
                fieldsDiv.innerHTML = '<input type="month" name="bulan" id="bulanField" value="' + document.getElementById('bulanInput').value + '">';
                document.querySelector('form').removeChild(document.querySelector('select[name="tahun"]') || document.createElement('select'));
            } else {
                const tahun = document.getElementById('tahunInput').value;
                const quartal = document.getElementById('quartalInput').value;
                fieldsDiv.innerHTML = `
                    <select name="tahun" id="tahunField">
                        ${Array.from({length: 7}, (_, i) => {
                            const y = parseInt(tahun) - 5 + i;
                            return '<option value="' + y + '" ' + (y == tahun ? 'selected' : '') + '>' + y + '</option>';
                        }).join('')}
                    </select>
                    <select name="quartal" id="quartalField">
                        <option value="1" ${quartal === '1' ? 'selected' : ''}>Q1 (Jan-Mar)</option>
                        <option value="2" ${quartal === '2' ? 'selected' : ''}>Q2 (Apr-Jun)</option>
                        <option value="3" ${quartal === '3' ? 'selected' : ''}>Q3 (Jul-Sep)</option>
                        <option value="4" ${quartal === '4' ? 'selected' : ''}>Q4 (Oct-Dec)</option>
                    </select>
                `;
            }
        }
        </script>
    </div>
</section>

<?php if ($type === 'ringkasan'): ?>
<div class="grid cols-4">
    <section class="card metric"><small>Kandang Aktif</small><strong><?= number_format($totalKandang,0,',','.') ?></strong><em>unit</em></section>
    <section class="card metric"><small>Ayam Hidup</small><strong><?= number_format($totalAyam,0,',','.') ?></strong><em>ekor</em></section>
    <section class="card metric"><small>Mortalitas <?= $periodeType==='quartal'?'Quartal':'Bulan' ?> Ini</small><strong><?= number_format($deaths,0,',','.') ?></strong><em>ekor</em></section>
    <section class="card metric"><small>Laba/Rugi <?= $periodeType==='quartal'?'Quartal':'Bulan' ?> Ini</small><strong><?= format_rupiah($finance['pemasukan']-$finance['pengeluaran']) ?></strong><em><?= e($periodLabel) ?></em></section>
</div>
<section class="card" style="margin-top:18px">
    <div class="card-header"><h3>Profil Peternakan</h3></div>
    <div class="card-body">
        <div class="kpi-line"><span>Nama peternakan</span><strong><?= e($profile['nama_peternakan'] ?? '-') ?></strong></div>
        <div class="kpi-line"><span>Pemilik</span><strong><?= e($profile['pemilik'] ?? '-') ?></strong></div>
        <div class="kpi-line"><span>Lama usaha</span><strong><?= (int)($profile['lama_usaha_tahun'] ?? 0) ?> tahun</strong></div>
        <div class="kpi-line"><span>Frekuensi panen</span><strong><?= (int)($profile['frekuensi_panen_tahun'] ?? 0) ?> kali/tahun</strong></div>
        <div class="kpi-line"><span>Jumlah pekerja</span><strong><?= (int)($profile['jumlah_pekerja'] ?? 0) ?> orang</strong></div>
    </div>
</section>
<?php else: ?>
<section class="card">
    <div class="card-header"><h3>Laporan <?= e(ucwords(str_replace('_',' ',$type))) ?></h3><span class="badge"><?= count($data) ?> data</span></div>
    <div class="card-body" style="padding:0">
    <?php if(!$data): ?><div class="empty"><strong>Tidak ada data pada periode ini</strong>Database juga tidak dapat mencetak sesuatu yang belum pernah dimasukkan.</div>
    <?php else: ?><div class="table-wrap"><table>
        <?php if($type==='populasi'): ?>
            <thead><tr><th>Kandang</th><th>DOC Masuk</th><th>Umur</th><th>Awal</th><th>Hidup</th><th>Bobot</th><th>Status</th></tr></thead><tbody>
            <?php foreach($data as $r): ?><tr><td><?= e($r['nama_kandang']) ?></td><td><?= e($r['tgl_masuk']) ?></td><td>H<?= max(0,(int)$r['umur_hari']) ?></td><td><?= number_format((int)$r['jumlah_awal'],0,',','.') ?></td><td><?= number_format((int)$r['jumlah_hidup'],0,',','.') ?></td><td><?= number_format((float)$r['berat_rata'],3,',','.') ?> kg</td><td><?= e($r['status']) ?></td></tr><?php endforeach; ?>
        <?php elseif($type==='kesehatan'): ?>
            <thead><tr><th>Tanggal</th><th>Kandang</th><th>Mati</th><th>Sakit</th><th>Suhu</th><th>RH</th><th>Bobot</th><th>Catatan</th></tr></thead><tbody>
            <?php foreach($data as $r): ?><tr><td><?= e($r['tanggal']) ?></td><td><?= e($r['nama_kandang']) ?></td><td><?= (int)$r['mati'] ?></td><td><?= (int)$r['sakit'] ?></td><td><?= e((string)$r['suhu']) ?>°C</td><td><?= e((string)$r['kelembaban']) ?>%</td><td><?= number_format((float)$r['berat_rata'],3,',','.') ?> kg</td><td><?= e($r['catatan']) ?></td></tr><?php endforeach; ?>
        <?php elseif($type==='pakan_obat'): ?>
            <thead><tr><th>Tanggal</th><th>Kategori</th><th>Nama</th><th>Masuk</th><th>Pemakaian</th><th>Keterangan</th></tr></thead><tbody>
            <?php foreach($data as $r): ?><tr><td><?= e($r['tanggal']) ?></td><td><?= e($r['kategori']) ?></td><td><?= e($r['nama']) ?></td><td><?= number_format((float)$r['stok_masuk'],2,',','.') ?> <?= e($r['satuan']) ?></td><td><?= number_format((float)$r['pemakaian'],2,',','.') ?> <?= e($r['satuan']) ?></td><td><?= e($r['keterangan']) ?></td></tr><?php endforeach; ?>
        <?php elseif($type==='keuangan'): ?>
            <thead><tr><th>Tanggal</th><th>Jenis</th><th>Kategori</th><th>Nominal</th><th>Keterangan</th></tr></thead><tbody>
            <?php foreach($data as $r): ?><tr><td><?= e($r['tanggal']) ?></td><td><?= e($r['jenis']) ?></td><td><?= e($r['kategori']) ?></td><td><?= format_rupiah($r['nominal']) ?></td><td><?= e($r['keterangan']) ?></td></tr><?php endforeach; ?>
        <?php elseif($type==='penjualan'): ?>
            <thead><tr><th>Tanggal</th><th>Kandang</th><th>Pembeli</th><th>Jumlah</th><th>Berat</th><th>Harga/Kg</th><th>Total</th></tr></thead><tbody>
            <?php foreach($data as $r): ?><tr><td><?= e($r['tanggal']) ?></td><td><?= e($r['nama_kandang'] ?? '-') ?></td><td><?= e($r['pembeli']) ?></td><td><?= number_format((int)$r['jumlah_ayam'],0,',','.') ?></td><td><?= number_format((float)$r['berat_total'],2,',','.') ?> kg</td><td><?= format_rupiah($r['harga_per_kg']) ?></td><td><?= format_rupiah($r['total_harga']) ?></td></tr><?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table></div><?php endif; ?>
    </div>
</section>
<?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
