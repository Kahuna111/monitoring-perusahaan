<?php
require_once '../../config/database.php';
require_once '../../config/auth.php';
requireAdmin();

$pageTitle  = 'Laporan Keuangan';
$activePage = 'laporan';
$breadcrumb = 'Laporan / Keuangan';

// Rentang tanggal default (bulan ini)
$tanggalMulai = $_GET['tanggal_mulai'] ?? date('Y-m-01');
$tanggalSelesai = $_GET['tanggal_selesai'] ?? date('Y-m-t');

// Query Pemasukan
$stmtPemasukan = $pdo->prepare("
    SELECT p.tanggal, p.deskripsi, p.jumlah, 'pemasukan' AS tipe, kt.nama AS kategori_nama 
    FROM pemasukan p
    LEFT JOIN kategori_transaksi kt ON p.kategori_id = kt.id
    WHERE p.tanggal BETWEEN ? AND ?
");
$stmtPemasukan->execute([$tanggalMulai, $tanggalSelesai]);
$pemasukan = $stmtPemasukan->fetchAll();

// Query Pengeluaran
$stmtPengeluaran = $pdo->prepare("
    SELECT pe.tanggal, pe.deskripsi, pe.jumlah, 'pengeluaran' AS tipe, kt.nama AS kategori_nama 
    FROM pengeluaran pe
    LEFT JOIN kategori_transaksi kt ON pe.kategori_id = kt.id
    WHERE pe.tanggal BETWEEN ? AND ?
");
$stmtPengeluaran->execute([$tanggalMulai, $tanggalSelesai]);
$pengeluaran = $stmtPengeluaran->fetchAll();

// Query Gaji Karyawan yang telah dibayar
$stmtGaji = $pdo->prepare("
    SELECT tg.tanggal_bayar AS tanggal, CONCAT('Gaji Karyawan - ', k.nama, ' (Bulan: ', tg.bulan, ')') AS deskripsi, tg.gaji_bersih AS jumlah, 'pengeluaran' AS tipe, 'Gaji Karyawan' AS kategori_nama
    FROM transaksi_gaji tg
    JOIN karyawan k ON tg.karyawan_id = k.id
    WHERE tg.status_bayar = 'sudah' AND tg.tanggal_bayar BETWEEN ? AND ?
");
$stmtGaji->execute([$tanggalMulai, $tanggalSelesai]);
$gaji = $stmtGaji->fetchAll();

// Gabungkan semua data transaksi
$semuaTransaksi = array_merge($pemasukan, $pengeluaran, $gaji);

// Urutkan transaksi berdasarkan tanggal ASC
usort($semuaTransaksi, function($a, $b) {
    return strcmp($a['tanggal'], $b['tanggal']);
});

// Hitung total ringkasan
$totalMasuk  = array_sum(array_column($pemasukan, 'jumlah'));
$totalKeluar = array_sum(array_column($pengeluaran, 'jumlah'));
$totalGaji   = array_sum(array_column($gaji, 'jumlah'));
$totalPengeluaranLengkap = $totalKeluar + $totalGaji;
$saldoBersih = $totalMasuk - $totalPengeluaranLengkap;

require_once '../../includes/header.php';
require_once '../../includes/sidebar.php';
require_once '../../includes/navbar.php';
?>

<!-- Custom CSS khusus Print & Layout Laporan -->
<style>
@media print {
    body { background: #fff; color: #000; font-size: 12px; }
    .sidebar, .topbar, .page-header, .filter-card, .btn, #keuanganChartCard { display: none !important; }
    .main-content { margin-left: 0 !important; padding: 0 !important; width: 100% !important; }
    .page-content { padding: 0 !important; }
    .card { border: none !important; box-shadow: none !important; margin: 0 !important; padding: 0 !important; }
    .table { width: 100% !important; border-collapse: collapse !important; }
    .table th, .table td { padding: 6px 8px !important; border: 1px solid #cbd5e1 !important; font-size: 11px !important; }
    .print-header { display: block !important; margin-bottom: 30px; text-align: center; }
    .stats-grid-print { display: flex !important; gap: 20px; margin-bottom: 30px; }
    .stat-card-print { flex: 1; border: 1px solid #cbd5e1; padding: 12px; border-radius: 8px; }
    .stat-card-print-label { font-size: 11px; color: #64748b; margin-bottom: 4px; }
    .stat-card-print-value { font-size: 14px; font-weight: 700; color: #000; }
}
.print-header { display: none; }
.stats-grid-print { display: none; }
</style>

<!-- Header Cetak Khusus PDF/Print -->
<div class="print-header">
    <h2 style="margin: 0 0 6px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px;"><?= APP_COMPANY ?></h2>
    <h3 style="margin: 0 0 6px; color: #475569; font-weight: 600;">Laporan Keuangan Bulanan</h3>
    <p style="margin: 0; color: #64748b; font-size: 13px;">Periode: <?= formatTanggal($tanggalMulai) ?> s/d <?= formatTanggal($tanggalSelesai) ?></p>
    <hr style="border: 0; border-top: 2px solid #0f172a; margin: 20px 0 10px;">
</div>

<div class="page-header">
    <div class="page-header-left">
        <h2>Laporan Keuangan</h2>
        <p>Analisis neraca laba rugi dan ringkasan arus kas keluar-masuk</p>
    </div>
    <button onclick="window.print()" class="btn btn-primary" id="btnCetakLaporan">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9V2h12v7M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2"/><path d="M6 14h12v8H6z"/></svg>
        Cetak Laporan
    </button>
</div>

<!-- Filter Bar -->
<div class="card mb-20 filter-card">
    <div class="card-body" style="padding:16px 24px;">
        <form method="GET" style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end;">
            <div class="form-group" style="margin-bottom:0;flex:1;min-width:180px;">
                <label class="form-label" style="margin-bottom:6px;font-size:12px;">Mulai Tanggal</label>
                <input type="text" name="tanggal_mulai" class="form-control datepicker" value="<?= htmlspecialchars($tanggalMulai) ?>">
            </div>
            <div class="form-group" style="margin-bottom:0;flex:1;min-width:180px;">
                <label class="form-label" style="margin-bottom:6px;font-size:12px;">Selesai Tanggal</label>
                <input type="text" name="tanggal_selesai" class="form-control datepicker" value="<?= htmlspecialchars($tanggalSelesai) ?>">
            </div>
            <button type="submit" class="btn btn-primary" style="height:42px;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                Tampilkan
            </button>
            <?php if (isset($_GET['tanggal_mulai'])): ?>
                <a href="<?= BASE_URL ?>/admin/laporan/keuangan.php" class="btn btn-outline" style="height:42px;display:inline-flex;align-items:center;">Reset</a>
            <?php endif; ?>
        </form>
    </div>
</div>

<!-- Stats Card (Tampilan Web) -->
<div class="stats-grid mb-24 filter-card" style="grid-template-columns: repeat(4, 1fr);">
    <div class="stat-card green">
        <div class="stat-icon green">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12l7-7 7 7"/></svg>
        </div>
        <div class="stat-info">
            <div class="stat-label">Total Pemasukan</div>
            <div class="stat-value" style="font-size:16px;"><?= formatRupiah($totalMasuk) ?></div>
            <div class="stat-sub">Semua arus kas masuk</div>
        </div>
    </div>
    
    <div class="stat-card red">
        <div class="stat-icon red">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 19V5M19 12l-7 7-7-7"/></svg>
        </div>
        <div class="stat-info">
            <div class="stat-label">Pengeluaran Operasional</div>
            <div class="stat-value" style="font-size:16px;"><?= formatRupiah($totalKeluar) ?></div>
            <div class="stat-sub">Transaksi kas keluar</div>
        </div>
    </div>

    <div class="stat-card indigo">
        <div class="stat-icon indigo">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20M7 15h2m4 0h4"/></svg>
        </div>
        <div class="stat-info">
            <div class="stat-label">Pengeluaran Gaji</div>
            <div class="stat-value" style="font-size:16px;"><?= formatRupiah($totalGaji) ?></div>
            <div class="stat-sub">Total gaji yang dibayar</div>
        </div>
    </div>

    <div class="stat-card amber">
        <div class="stat-icon amber">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 7V5a2 2 0 00-2-2h-4a2 2 0 00-2 2v2"/>
            </svg>
        </div>
        <div class="stat-info">
            <div class="stat-label">Laba / Rugi Bersih</div>
            <div class="stat-value" style="font-size:16px; color: <?= $saldoBersih >= 0 ? '#10b981' : '#ef4444' ?>;">
                <?= formatRupiah(abs($saldoBersih)) ?>
            </div>
            <div class="stat-sub"><?= $saldoBersih >= 0 ? 'Surplus (Laba)' : 'Defisit (Rugi)' ?></div>
        </div>
    </div>
</div>

<!-- Stats Card (Tampilan Print Cetak) -->
<div class="stats-grid-print">
    <div class="stat-card-print">
        <div class="stat-card-print-label">TOTAL PEMASUKAN</div>
        <div class="stat-card-print-value" style="color: #10b981;"><?= formatRupiah($totalMasuk) ?></div>
    </div>
    <div class="stat-card-print">
        <div class="stat-card-print-label">PENGELUARAN OPERASIONAL</div>
        <div class="stat-card-print-value" style="color: #ef4444;"><?= formatRupiah($totalKeluar) ?></div>
    </div>
    <div class="stat-card-print">
        <div class="stat-card-print-label">PENGELUARAN GAJI</div>
        <div class="stat-card-print-value" style="color: #6366f1;"><?= formatRupiah($totalGaji) ?></div>
    </div>
    <div class="stat-card-print">
        <div class="stat-card-print-label">LABA / RUGI BERSH</div>
        <div class="stat-card-print-value" style="color: <?= $saldoBersih >= 0 ? '#10b981' : '#ef4444' ?>;">
            <?= ($saldoBersih < 0 ? '-' : '') . formatRupiah(abs($saldoBersih)) ?> (<?= $saldoBersih >= 0 ? 'Laba' : 'Rugi' ?>)
        </div>
    </div>
</div>

<!-- Chart Visualisasi (Tampilan Web saja) -->
<div class="card mb-24 filter-card" id="keuanganChartCard">
    <div class="card-header">
        <div class="card-title">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
            Visualisasi Cash Flow
        </div>
    </div>
    <div class="card-body">
        <div class="chart-container" style="height: 250px; position: relative;">
            <canvas id="laporanChart"></canvas>
        </div>
    </div>
</div>

<!-- Table Transaksi -->
<div class="card">
    <div class="card-header filter-card">
        <div class="card-title">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            Buku Jurnal Kas Detail
        </div>
    </div>
    <div class="table-responsive">
        <table class="table" style="width: 100%;">
            <thead>
                <tr>
                    <th style="width: 50px;">No</th>
                    <th>Tanggal</th>
                    <th>Kategori</th>
                    <th>Keterangan Transaksi</th>
                    <th class="text-right" style="width: 180px;">Pemasukan (+)</th>
                    <th class="text-right" style="width: 180px;">Pengeluaran (-)</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($semuaTransaksi as $i => $row): ?>
                <tr>
                    <td style="color:#94a3b8;font-size:13px;"><?= $i+1 ?></td>
                    <td style="white-space:nowrap;font-size:13px;"><?= formatTanggal($row['tanggal']) ?></td>
                    <td>
                        <span class="badge badge-<?= $row['tipe'] === 'pemasukan' ? 'success' : ($row['kategori_nama'] === 'Gaji Karyawan' ? 'indigo' : 'danger') ?>" style="font-size:11px;font-weight:600;">
                            <?= htmlspecialchars($row['kategori_nama'] ?? 'Lain-lain') ?>
                        </span>
                    </td>
                    <td style="font-size:13px;"><?= htmlspecialchars($row['deskripsi'] ?? '-') ?></td>
                    
                    <td class="text-right fw-semibold" style="color:#10b981;font-size:13px;">
                        <?= $row['tipe'] === 'pemasukan' ? '+' . formatRupiah($row['jumlah']) : '-' ?>
                    </td>
                    <td class="text-right fw-semibold" style="color:#ef4444;font-size:13px;">
                        <?= $row['tipe'] === 'pengeluaran' ? '-' . formatRupiah($row['jumlah']) : '-' ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($semuaTransaksi)): ?>
                <tr>
                    <td colspan="6">
                        <div class="empty-state" style="padding: 40px 0;">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                <path d="M9 17v-2m3 2v-4m3 4v-6M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            <h3>Tidak ada transaksi dalam periode ini</h3>
                            <p>Sesuaikan rentang tanggal pencarian Anda.</p>
                        </div>
                    </td>
                </tr>
                <?php endif; ?>
            </tbody>
            <tfoot>
                <tr style="background:#f8fafc; font-weight:700;">
                    <td colspan="4" class="text-right" style="padding: 12px 24px; font-size:13px;">SUBTOTAL PERIODE:</td>
                    <td class="text-right" style="color:#10b981; padding: 12px; font-size:13px;">+<?= formatRupiah($totalMasuk) ?></td>
                    <td class="text-right" style="color:#ef4444; padding: 12px; font-size:13px;">-<?= formatRupiah($totalPengeluaranLengkap) ?></td>
                </tr>
                <tr style="background:#f1f5f9; font-weight:800; border-top: 2px solid #cbd5e1;">
                    <td colspan="4" class="text-right" style="padding: 12px 24px; font-size:13.5px;">LABA / RUGI BERSIH:</td>
                    <td colspan="2" class="text-right" style="color:<?= $saldoBersih >= 0 ? '#10b981' : '#ef4444' ?>; padding: 12px 24px; font-size:14px;">
                        <?= $saldoBersih >= 0 ? '+' : '-' ?><?= formatRupiah(abs($saldoBersih)) ?> (<?= $saldoBersih >= 0 ? 'SURPLUS / LABA' : 'DEFISIT / RUGI' ?>)
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

<!-- Chart Script (Hanya di Web) -->
<script>
document.addEventListener('DOMContentLoaded', () => {
    const ctx = document.getElementById('laporanChart');
    if (ctx) {
        new Chart(ctx.getContext('2d'), {
            type: 'bar',
            data: {
                labels: ['Pemasukan', 'Pengeluaran Operasional', 'Pengeluaran Gaji', 'Total Pengeluaran'],
                datasets: [{
                    label: 'Arus Kas (Rp)',
                    data: [
                        <?= $totalMasuk ?>, 
                        <?= $totalKeluar ?>, 
                        <?= $totalGaji ?>, 
                        <?= $totalPengeluaranLengkap ?>
                    ],
                    backgroundColor: [
                        'rgba(16, 185, 129, 0.75)',  // Green
                        'rgba(239, 68, 68, 0.75)',   // Red
                        'rgba(99, 102, 241, 0.75)',  // Indigo
                        'rgba(100, 116, 139, 0.75)'  // Slate
                    ],
                    borderColor: [
                        '#10b981', 
                        '#ef4444', 
                        '#6366f1', 
                        '#64748b'
                    ],
                    borderWidth: 1.5,
                    borderRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: ctx => ' Rp ' + parseInt(ctx.parsed.y).toLocaleString('id-ID')
                        }
                    }
                },
                scales: {
                    y: {
                        grid: { color: 'rgba(0,0,0,0.04)' },
                        ticks: {
                            font: { family: 'Inter', size: 11 },
                            callback: val => 'Rp ' + (val/1000000).toFixed(1) + 'jt'
                        }
                    },
                    x: {
                        grid: { display: false },
                        ticks: { font: { family: 'Inter', size: 11, weight: '500' } }
                    }
                }
            }
        });
    }
});
</script>

<?php require_once '../../includes/footer.php'; ?>
