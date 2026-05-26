<?php
require_once '../config/database.php';
require_once '../config/auth.php';
requireAdmin();

$pageTitle  = 'Dashboard';
$activePage = 'dashboard';
$breadcrumb = 'Beranda / Dashboard';

// ── Statistik ──────────────────────────────────────────
$totalKaryawan = $pdo->query("SELECT COUNT(*) FROM karyawan WHERE status='aktif'")->fetchColumn();

$bulanIni = date('Y-m');
$totalPemasukan = $pdo->prepare("
    SELECT COALESCE(SUM(jumlah),0) FROM pemasukan
    WHERE DATE_FORMAT(tanggal,'%Y-%m') = ?");
$totalPemasukan->execute([$bulanIni]);
$totalPemasukan = (float)$totalPemasukan->fetchColumn();

$totalPengeluaran = $pdo->prepare("
    SELECT COALESCE(SUM(jumlah),0) FROM pengeluaran
    WHERE DATE_FORMAT(tanggal,'%Y-%m') = ?");
$totalPengeluaran->execute([$bulanIni]);
$totalPengeluaran = (float)$totalPengeluaran->fetchColumn();

$totalGajiBulanIni = $pdo->prepare("
    SELECT COALESCE(SUM(gaji_bersih),0) FROM transaksi_gaji WHERE bulan = ?");
$totalGajiBulanIni->execute([$bulanIni]);
$totalGajiBulanIni = (float)$totalGajiBulanIni->fetchColumn();

$saldo = $totalPemasukan - $totalPengeluaran - $totalGajiBulanIni;

// ── Chart data: 6 bulan terakhir ──────────────────────
$chartLabels   = [];
$chartPemasukan   = [];
$chartPengeluaran = [];
for ($i = 5; $i >= 0; $i--) {
    $bln = date('Y-m', strtotime("-$i months"));
    $blnLabel = date('M Y', strtotime("-$i months"));
    $chartLabels[] = $blnLabel;

    $p = $pdo->prepare("SELECT COALESCE(SUM(jumlah),0) FROM pemasukan WHERE DATE_FORMAT(tanggal,'%Y-%m')=?");
    $p->execute([$bln]); $chartPemasukan[] = (float)$p->fetchColumn();

    $k = $pdo->prepare("SELECT COALESCE(SUM(jumlah),0) FROM pengeluaran WHERE DATE_FORMAT(tanggal,'%Y-%m')=?");
    $k->execute([$bln]); $chartPengeluaran[] = (float)$k->fetchColumn();
}

// ── Transaksi Terbaru ──────────────────────────────────
$recentTransaksi = $pdo->query("
    SELECT 'masuk' AS tipe, tanggal, deskripsi, jumlah FROM pemasukan
    UNION ALL
    SELECT 'keluar', tanggal, deskripsi, jumlah FROM pengeluaran
    ORDER BY tanggal DESC LIMIT 8
")->fetchAll();

// ── Karyawan terbaru ──────────────────────────────────
$recentKaryawan = $pdo->query("
    SELECT nama, jabatan, departemen, tanggal_masuk, status
    FROM karyawan ORDER BY created_at DESC LIMIT 5
")->fetchAll();

// ── Gaji belum dibayar ─────────────────────────────────
$gagiBelum = $pdo->prepare("
    SELECT COUNT(*) FROM transaksi_gaji WHERE bulan=? AND status_bayar='belum'");
$gagiBelum->execute([$bulanIni]);
$gagiBelum = (int)$gagiBelum->fetchColumn();

require_once '../includes/header.php';
require_once '../includes/sidebar.php';
require_once '../includes/navbar.php';
?>

<!-- ══════════════════════════════════════════════
     PAGE HEADER
     ══════════════════════════════════════════════ -->
<div class="page-header">
    <div class="page-header-left">
        <h2>Dashboard</h2>
        <p>Ringkasan aktivitas perusahaan bulan <?= date('F Y') ?></p>
    </div>
    <div style="display:flex;gap:10px;">
        <a href="<?= BASE_URL ?>/admin/pemasukan/tambah.php" class="btn btn-success">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12l7-7 7 7"/></svg>
            Catat Pemasukan
        </a>
        <a href="<?= BASE_URL ?>/admin/pengeluaran/tambah.php" class="btn btn-danger">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 19V5M19 12l-7 7-7-7"/></svg>
            Catat Pengeluaran
        </a>
    </div>
</div>

<!-- ══════════════════════════════════════════════
     STAT CARDS
     ══════════════════════════════════════════════ -->
<div class="stats-grid">

    <div class="stat-card indigo">
        <div class="stat-icon indigo">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/>
                <path d="M23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"/>
            </svg>
        </div>
        <div class="stat-info">
            <div class="stat-label">Total Karyawan Aktif</div>
            <div class="stat-value"><?= $totalKaryawan ?> Orang</div>
            <div class="stat-sub">Data karyawan terdaftar</div>
        </div>
    </div>

    <div class="stat-card green">
        <div class="stat-icon green">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M12 5v14M5 12l7-7 7 7"/>
            </svg>
        </div>
        <div class="stat-info">
            <div class="stat-label">Pemasukan Bulan Ini</div>
            <div class="stat-value" style="font-size:17px;"><?= formatRupiah($totalPemasukan) ?></div>
            <div class="stat-sub"><?= date('F Y') ?></div>
        </div>
    </div>

    <div class="stat-card red">
        <div class="stat-icon red">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M12 19V5M19 12l-7 7-7-7"/>
            </svg>
        </div>
        <div class="stat-info">
            <div class="stat-label">Pengeluaran Bulan Ini</div>
            <div class="stat-value" style="font-size:17px;"><?= formatRupiah($totalPengeluaran + $totalGajiBulanIni) ?></div>
            <div class="stat-sub">Termasuk gaji karyawan</div>
        </div>
    </div>

    <div class="stat-card amber">
        <div class="stat-icon amber">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <rect x="2" y="7" width="20" height="14" rx="2"/>
                <path d="M16 7V5a2 2 0 00-2-2h-4a2 2 0 00-2 2v2"/>
                <line x1="12" y1="12" x2="12" y2="16"/><line x1="10" y1="14" x2="14" y2="14"/>
            </svg>
        </div>
        <div class="stat-info">
            <div class="stat-label">Saldo Bersih</div>
            <div class="stat-value" style="font-size:17px;color:<?= $saldo >= 0 ? '#10b981' : '#ef4444' ?>">
                <?= formatRupiah(abs($saldo)) ?>
            </div>
            <div class="stat-sub"><?= $saldo >= 0 ? '▲ Surplus' : '▼ Defisit' ?> bulan ini</div>
        </div>
    </div>

</div>

<!-- ══════════════════════════════════════════════
     CHARTS + RECENT
     ══════════════════════════════════════════════ -->
<div class="grid-2 mb-24">

    <!-- Chart -->
    <div class="card">
        <div class="card-header">
            <div class="card-title">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/>
                </svg>
                Arus Keuangan 6 Bulan Terakhir
            </div>
        </div>
        <div class="card-body">
            <div class="chart-container">
                <canvas id="keuanganChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Quick Stats -->
    <div style="display:flex;flex-direction:column;gap:16px;">

        <!-- Gaji bulan ini -->
        <div class="card">
            <div class="card-header">
                <div class="card-title">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="2" y="5" width="20" height="14" rx="2"/>
                        <path d="M2 10h20M7 15h2m4 0h4"/>
                    </svg>
                    Penggajian Bulan Ini
                </div>
                <a href="<?= BASE_URL ?>/admin/gaji/index.php" class="btn btn-outline btn-sm">Kelola</a>
            </div>
            <div class="card-body">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
                    <div>
                        <div style="font-size:24px;font-weight:800;color:#0f172a;"><?= formatRupiah($totalGajiBulanIni) ?></div>
                        <div style="font-size:13px;color:#64748b;margin-top:2px;">Total gaji dibayarkan</div>
                    </div>
                    <?php if ($gagiBelum > 0): ?>
                    <span class="badge badge-warning"><?= $gagiBelum ?> belum bayar</span>
                    <?php else: ?>
                    <span class="badge badge-success">Semua lunas</span>
                    <?php endif; ?>
                </div>
                <div style="background:#f1f5f9;border-radius:8px;height:6px;overflow:hidden;">
                    <?php
                    $pct = $totalKaryawan > 0 ? min(100, round((($totalKaryawan - $gagiBelum) / $totalKaryawan) * 100)) : 0;
                    ?>
                    <div style="background:linear-gradient(135deg,#6366f1,#4f46e5);height:100%;width:<?= $pct ?>%;border-radius:8px;transition:width 1s ease;"></div>
                </div>
                <div style="font-size:12px;color:#64748b;margin-top:6px;"><?= $pct ?>% karyawan sudah menerima gaji</div>
            </div>
        </div>

        <!-- Karyawan terbaru -->
        <div class="card" style="flex:1;">
            <div class="card-header">
                <div class="card-title">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/>
                    </svg>
                    Karyawan Terbaru
                </div>
                <a href="<?= BASE_URL ?>/admin/karyawan/index.php" class="btn btn-outline btn-sm">Lihat Semua</a>
            </div>
            <div class="card-body" style="padding:0;">
                <?php foreach (array_slice($recentKaryawan, 0, 3) as $k): ?>
                <div style="display:flex;align-items:center;gap:12px;padding:12px 24px;border-bottom:1px solid #f1f5f9;">
                    <div class="avatar" style="width:36px;height:36px;font-size:12px;"><?= getInitials($k['nama']) ?></div>
                    <div style="flex:1;min-width:0;">
                        <div style="font-size:13.5px;font-weight:600;color:#0f172a;"><?= htmlspecialchars($k['nama']) ?></div>
                        <div style="font-size:12px;color:#64748b;"><?= htmlspecialchars($k['jabatan'] ?? '-') ?> · <?= htmlspecialchars($k['departemen'] ?? '-') ?></div>
                    </div>
                    <span class="badge <?= $k['status'] === 'aktif' ? 'badge-success' : 'badge-secondary' ?>"><?= ucfirst($k['status']) ?></span>
                </div>
                <?php endforeach; ?>
                <?php if (empty($recentKaryawan)): ?>
                <div class="empty-state" style="padding:30px;">
                    <p>Belum ada data karyawan.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>

    </div>
</div>

<!-- ══════════════════════════════════════════════
     RECENT TRANSACTIONS
     ══════════════════════════════════════════════ -->
<div class="card">
    <div class="card-header">
        <div class="card-title">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M13 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V9z"/><polyline points="13 2 13 9 20 9"/>
            </svg>
            Transaksi Terbaru
        </div>
        <div style="display:flex;gap:8px;">
            <a href="<?= BASE_URL ?>/admin/pemasukan/index.php" class="btn btn-outline btn-sm">Pemasukan</a>
            <a href="<?= BASE_URL ?>/admin/pengeluaran/index.php" class="btn btn-outline btn-sm">Pengeluaran</a>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Tanggal</th>
                    <th>Keterangan</th>
                    <th>Tipe</th>
                    <th class="text-right">Jumlah</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recentTransaksi as $t): ?>
                <tr>
                    <td style="white-space:nowrap;color:#64748b;font-size:13px;"><?= formatTanggal($t['tanggal']) ?></td>
                    <td><?= htmlspecialchars($t['deskripsi'] ?? '-') ?></td>
                    <td>
                        <?php if ($t['tipe'] === 'masuk'): ?>
                            <span class="badge badge-success">
                                <svg viewBox="0 0 20 20" fill="currentColor" style="width:10px;height:10px;"><path d="M10 3a1 1 0 00-1 1v5H4a1 1 0 100 2h5v5a1 1 0 102 0v-5h5a1 1 0 100-2h-5V4a1 1 0 00-1-1z"/></svg>
                                Masuk
                            </span>
                        <?php else: ?>
                            <span class="badge badge-danger">
                                <svg viewBox="0 0 20 20" fill="currentColor" style="width:10px;height:10px;"><path d="M3 10a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z"/></svg>
                                Keluar
                            </span>
                        <?php endif; ?>
                    </td>
                    <td class="text-right fw-semibold" style="color:<?= $t['tipe'] === 'masuk' ? '#10b981' : '#ef4444' ?>">
                        <?= $t['tipe'] === 'masuk' ? '+' : '-' ?><?= formatRupiah($t['jumlah']) ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($recentTransaksi)): ?>
                <tr>
                    <td colspan="4">
                        <div class="empty-state">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                <path d="M9 17v-2m3 2v-4m3 4v-6M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            <h3>Belum ada transaksi</h3>
                            <p>Mulai catat pemasukan atau pengeluaran perusahaan Anda.</p>
                        </div>
                    </td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Chart Script -->
<script>
document.addEventListener('DOMContentLoaded', () => {
    const ctx = document.getElementById('keuanganChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?= json_encode($chartLabels) ?>,
            datasets: [
                {
                    label: 'Pemasukan',
                    data: <?= json_encode($chartPemasukan) ?>,
                    borderColor: '#10b981',
                    backgroundColor: 'rgba(16,185,129,0.08)',
                    borderWidth: 2.5,
                    pointBackgroundColor: '#10b981',
                    pointRadius: 4,
                    pointHoverRadius: 6,
                    tension: 0.4,
                    fill: true
                },
                {
                    label: 'Pengeluaran',
                    data: <?= json_encode($chartPengeluaran) ?>,
                    borderColor: '#ef4444',
                    backgroundColor: 'rgba(239,68,68,0.06)',
                    borderWidth: 2.5,
                    pointBackgroundColor: '#ef4444',
                    pointRadius: 4,
                    pointHoverRadius: 6,
                    tension: 0.4,
                    fill: true
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: {
                    position: 'top',
                    labels: { font: { family: 'Inter', size: 12 }, padding: 16, usePointStyle: true }
                },
                tooltip: {
                    callbacks: {
                        label: ctx => ' ' + ctx.dataset.label + ': Rp ' + parseInt(ctx.parsed.y).toLocaleString('id-ID')
                    }
                }
            },
            scales: {
                x: { grid: { display: false }, ticks: { font: { family: 'Inter', size: 11 } } },
                y: {
                    grid: { color: 'rgba(0,0,0,0.04)' },
                    ticks: {
                        font: { family: 'Inter', size: 11 },
                        callback: val => 'Rp ' + (val/1000000).toFixed(1) + 'jt'
                    }
                }
            }
        }
    });
});
</script>

<?php require_once '../includes/footer.php'; ?>
