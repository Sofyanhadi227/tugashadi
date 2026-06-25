<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_login();

$pdo = db();

$totalKandang = (int) $pdo->query("SELECT COUNT(*) FROM kandang WHERE status = 'aktif'")->fetchColumn();
$totalAyam = (int) $pdo->query("SELECT COALESCE(SUM(jumlah_hidup), 0) FROM populasi WHERE status = 'aktif'")->fetchColumn();
$deathsToday = (int) $pdo->query("SELECT COALESCE(SUM(mati), 0) FROM pencatatan WHERE tanggal = CURDATE()")->fetchColumn();
$sickToday = (int) $pdo->query("SELECT COALESCE(SUM(sakit), 0) FROM pencatatan WHERE tanggal = CURDATE()")->fetchColumn();
$totalFeed = (float) $pdo->query("SELECT COALESCE(SUM(pakan_kg), 0) FROM pencatatan")->fetchColumn();
$totalBiomass = (float) $pdo->query("SELECT COALESCE(SUM(jumlah_hidup * berat_rata), 0) FROM populasi WHERE status = 'aktif'")->fetchColumn();
$fcr = $totalBiomass > 0 ? $totalFeed / $totalBiomass : 0;

$income = (float) $pdo->query("SELECT COALESCE(SUM(nominal), 0) FROM keuangan WHERE jenis = 'pemasukan'")->fetchColumn();
$expense = (float) $pdo->query("SELECT COALESCE(SUM(nominal), 0) FROM keuangan WHERE jenis = 'pengeluaran'")->fetchColumn();
$profit = $income - $expense;

$recent = $pdo->query("
    SELECT pc.tanggal, k.nama_kandang, pc.mati, pc.sakit, pc.suhu, pc.kelembaban, pc.berat_rata
    FROM pencatatan pc
    JOIN populasi p ON p.id = pc.populasi_id
    JOIN kandang k ON k.id = p.kandang_id
    ORDER BY pc.tanggal DESC, pc.id DESC
    LIMIT 8
")->fetchAll();

$growth = $pdo->query("
    SELECT pc.tanggal, ROUND(AVG(pc.berat_rata), 3) AS berat
    FROM pencatatan pc
    WHERE pc.berat_rata > 0
    GROUP BY pc.tanggal
    ORDER BY pc.tanggal DESC
    LIMIT 10
")->fetchAll();
$growth = array_reverse($growth);

$alerts = [];
foreach ($recent as $row) {
    if ((int)$row['mati'] > 0 || (int)$row['sakit'] > 0 || (float)$row['suhu'] > 33 || (float)$row['suhu'] < 24) {
        $alerts[] = $row;
    }
}

$pageTitle = 'Dashboard';
$pageScript = '<script>
const points = ' . json_encode($growth, JSON_UNESCAPED_UNICODE) . ';
const canvas = document.getElementById("growthChart");
if (canvas && points.length) {
    const ctx = canvas.getContext("2d");
    function drawChart() {
        const ratio = window.devicePixelRatio || 1;
        const width = canvas.clientWidth;
        const height = 300;
        canvas.width = width * ratio;
        canvas.height = height * ratio;
        ctx.scale(ratio, ratio);
        ctx.clearRect(0, 0, width, height);

        const pad = {left: 45, right: 20, top: 20, bottom: 45};
        const chartW = width - pad.left - pad.right;
        const chartH = height - pad.top - pad.bottom;
        const values = points.map(p => Number(p.berat));
        const max = Math.max(...values, 1);
        const min = Math.min(...values, 0);
        const span = Math.max(max - min, 0.5);

        ctx.strokeStyle = "#dfe7e1";
        ctx.lineWidth = 1;
        ctx.font = "12px system-ui";
        ctx.fillStyle = "#69756e";

        for (let i = 0; i <= 4; i++) {
            const y = pad.top + chartH * i / 4;
            ctx.beginPath();
            ctx.moveTo(pad.left, y);
            ctx.lineTo(width - pad.right, y);
            ctx.stroke();
            const label = (max - span * i / 4).toFixed(2);
            ctx.fillText(label, 4, y + 4);
        }

        const coords = points.map((p, i) => ({
            x: pad.left + (points.length === 1 ? chartW / 2 : chartW * i / (points.length - 1)),
            y: pad.top + chartH - ((Number(p.berat) - min) / span) * chartH
        }));

        ctx.strokeStyle = "#246b45";
        ctx.lineWidth = 3;
        ctx.beginPath();
        coords.forEach((c, i) => i ? ctx.lineTo(c.x, c.y) : ctx.moveTo(c.x, c.y));
        ctx.stroke();

        ctx.fillStyle = "#246b45";
        coords.forEach((c, i) => {
            ctx.beginPath();
            ctx.arc(c.x, c.y, 4, 0, Math.PI * 2);
            ctx.fill();
            const label = new Date(points[i].tanggal + "T00:00:00").toLocaleDateString("id-ID", {day:"2-digit", month:"short"});
            ctx.fillStyle = "#69756e";
            ctx.save();
            ctx.translate(c.x, height - 15);
            ctx.rotate(-0.35);
            ctx.fillText(label, -18, 0);
            ctx.restore();
            ctx.fillStyle = "#246b45";
        });
    }
    drawChart();
    window.addEventListener("resize", drawChart);
}
</script>';

require __DIR__ . '/includes/header.php';
?>
<div class="grid cols-4">
    <section class="card metric">
        <small>Total Kandang Aktif</small>
        <strong><?= number_format($totalKandang, 0, ',', '.') ?></strong>
        <em>Unit kandang</em>
        <div class="metric-icon">▦</div>
    </section>
    <section class="card metric">
        <small>Total Ayam Hidup</small>
        <strong><?= number_format($totalAyam, 0, ',', '.') ?></strong>
        <em>Populasi aktif</em>
        <div class="metric-icon">🐣</div>
    </section>
    <section class="card metric">
        <small>Mortalitas Hari Ini</small>
        <strong><?= number_format($deathsToday, 0, ',', '.') ?></strong>
        <em><?= $totalAyam > 0 ? number_format(($deathsToday / $totalAyam) * 100, 2, ',', '.') : '0,00' ?>%</em>
        <div class="metric-icon">!</div>
    </section>
    <section class="card metric">
        <small>Estimasi FCR</small>
        <strong><?= number_format($fcr, 2, ',', '.') ?></strong>
        <em>Pakan ÷ biomassa</em>
        <div class="metric-icon">≈</div>
    </section>
</div>

<div class="grid cols-3" style="margin-top:18px">
    <section class="card" style="grid-column:span 2">
        <div class="card-header">
            <h3>Pertumbuhan Bobot Rata-rata</h3>
            <a class="btn btn-secondary btn-sm" href="pencatatan.php">Tambah Catatan</a>
        </div>
        <div class="card-body chart-box">
            <?php if ($growth): ?>
                <canvas id="growthChart"></canvas>
            <?php else: ?>
                <div class="empty"><strong>Belum ada data bobot</strong>Isi pencatatan harian agar grafik tidak sekadar dekorasi optimistis.</div>
            <?php endif; ?>
        </div>
    </section>

    <section class="card">
        <div class="card-header"><h3>Ringkasan Keuangan</h3></div>
        <div class="card-body">
            <div class="kpi-line"><span>Pemasukan</span><strong><?= format_rupiah($income) ?></strong></div>
            <div class="kpi-line"><span>Pengeluaran</span><strong><?= format_rupiah($expense) ?></strong></div>
            <div class="kpi-line"><span>Laba/Rugi</span><strong><?= format_rupiah($profit) ?></strong></div>
            <a class="btn btn-primary" href="keuangan.php" style="width:100%;margin-top:18px">Kelola Keuangan</a>
        </div>
    </section>
</div>

<div class="grid cols-2" style="margin-top:18px">
    <section class="card">
        <div class="card-header"><h3>Peringatan Operasional</h3></div>
        <div class="card-body">
            <?php if ($alerts): ?>
                <?php foreach (array_slice($alerts, 0, 6) as $alert): ?>
                    <div class="kpi-line">
                        <div>
                            <strong><?= e($alert['nama_kandang']) ?></strong><br>
                            <small><?= e($alert['tanggal']) ?> · Mati <?= (int)$alert['mati'] ?> · Sakit <?= (int)$alert['sakit'] ?></small>
                        </div>
                        <span class="badge <?= ((int)$alert['mati'] > 0 || (int)$alert['sakit'] > 0) ? 'danger' : 'warning' ?>">
                            <?= e($alert['suhu']) ?>°C
                        </span>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty"><strong>Tidak ada peringatan</strong>Semoga memang sehat, bukan karena belum ada yang mencatat.</div>
            <?php endif; ?>
        </div>
    </section>

    <section class="card">
        <div class="card-header"><h3>Pencatatan Terbaru</h3></div>
        <div class="card-body" style="padding:0">
            <?php if ($recent): ?>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>Tanggal</th><th>Kandang</th><th>Mati</th><th>Sakit</th><th>Bobot</th></tr></thead>
                        <tbody>
                        <?php foreach ($recent as $row): ?>
                            <tr>
                                <td><?= e($row['tanggal']) ?></td>
                                <td><?= e($row['nama_kandang']) ?></td>
                                <td><?= (int)$row['mati'] ?></td>
                                <td><?= (int)$row['sakit'] ?></td>
                                <td><?= number_format((float)$row['berat_rata'], 3, ',', '.') ?> kg</td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty"><strong>Belum ada pencatatan</strong>Tambahkan data harian pertama.</div>
            <?php endif; ?>
        </div>
    </section>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
