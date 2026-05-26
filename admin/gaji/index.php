<?php
require_once '../../config/database.php';
require_once '../../config/auth.php';
requireAdmin();

$pageTitle  = 'Penggajian';
$activePage = 'gaji';
$breadcrumb = 'Penggajian / Daftar';

$bulan = $_GET['bulan'] ?? date('Y-m');

// Ambil semua karyawan aktif beserta transaksi gaji bulan ini
$stmt = $pdo->prepare("
    SELECT k.id, k.nik, k.nama, k.jabatan, k.departemen, k.gaji_pokok,
           tg.id AS gaji_id, tg.tunjangan, tg.potongan, tg.gaji_bersih,
           tg.status_bayar, tg.tanggal_bayar
    FROM karyawan k
    LEFT JOIN transaksi_gaji tg ON tg.karyawan_id = k.id AND tg.bulan = ?
    WHERE k.status = 'aktif'
    ORDER BY k.nama ASC
");
$stmt->execute([$bulan]);
$data = $stmt->fetchAll();

// Summary
$totalGaji   = array_sum(array_column(array_filter($data, fn($r)=>$r['gaji_id']), 'gaji_bersih'));
$sudahBayar  = count(array_filter($data, fn($r)=>$r['status_bayar']==='sudah'));
$belumBayar  = count(array_filter($data, fn($r)=>$r['gaji_id'] && $r['status_bayar']==='belum'));
$belumInput  = count(array_filter($data, fn($r)=>!$r['gaji_id']));

require_once '../../includes/header.php';
require_once '../../includes/sidebar.php';
require_once '../../includes/navbar.php';
?>

<div class="page-header">
    <div class="page-header-left">
        <h2>Penggajian</h2>
        <p>Kelola gaji karyawan bulan <?= date('F Y', strtotime($bulan . '-01')) ?></p>
    </div>
    <a href="<?= BASE_URL ?>/admin/gaji/proses.php?bulan=<?= $bulan ?>" class="btn btn-primary">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        Input Penggajian
    </a>
</div>

<!-- Filter Bulan -->
<div class="card mb-20">
    <div class="card-body" style="padding:16px 24px;">
        <form method="GET" style="display:flex;align-items:center;gap:12px;">
            <label style="font-size:13.5px;font-weight:600;color:#475569;">Pilih Bulan:</label>
            <input type="month" name="bulan" class="form-control" style="width:200px;" value="<?= $bulan ?>">
            <button type="submit" class="btn btn-primary">Tampilkan</button>
        </form>
    </div>
</div>

<!-- Stat Cards -->
<div class="stats-grid mb-24" style="grid-template-columns:repeat(4,1fr);">
    <div class="stat-card indigo">
        <div class="stat-icon indigo">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/></svg>
        </div>
        <div class="stat-info">
            <div class="stat-label">Total Gaji</div>
            <div class="stat-value" style="font-size:16px;"><?= formatRupiah($totalGaji) ?></div>
            <div class="stat-sub">Bulan ini</div>
        </div>
    </div>
    <div class="stat-card green">
        <div class="stat-icon green">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 12l2 2 4-4"/><circle cx="12" cy="12" r="10"/></svg>
        </div>
        <div class="stat-info">
            <div class="stat-label">Sudah Dibayar</div>
            <div class="stat-value"><?= $sudahBayar ?> Orang</div>
            <div class="stat-sub">Gaji lunas</div>
        </div>
    </div>
    <div class="stat-card amber">
        <div class="stat-icon amber">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
        </div>
        <div class="stat-info">
            <div class="stat-label">Belum Dibayar</div>
            <div class="stat-value"><?= $belumBayar ?> Orang</div>
            <div class="stat-sub">Menunggu pembayaran</div>
        </div>
    </div>
    <div class="stat-card red">
        <div class="stat-icon red">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        </div>
        <div class="stat-info">
            <div class="stat-label">Belum Diinput</div>
            <div class="stat-value"><?= $belumInput ?> Orang</div>
            <div class="stat-sub">Perlu diinput</div>
        </div>
    </div>
</div>

<!-- Table -->
<div class="card">
    <div class="card-header">
        <div class="card-title">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20M7 15h2m4 0h4"/></svg>
            Daftar Penggajian – <?= date('F Y', strtotime($bulan.'-01')) ?>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table data-table">
            <thead>
                <tr>
                    <th>No</th>
                    <th>NIK</th>
                    <th>Nama Karyawan</th>
                    <th>Jabatan</th>
                    <th>Gaji Pokok</th>
                    <th>Tunjangan</th>
                    <th>Potongan</th>
                    <th>Gaji Bersih</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($data as $i => $row): ?>
                <tr>
                    <td style="color:#94a3b8;font-size:13px;"><?= $i+1 ?></td>
                    <td><span style="font-family:monospace;font-size:12px;background:#f1f5f9;padding:2px 8px;border-radius:5px;"><?= htmlspecialchars($row['nik']) ?></span></td>
                    <td>
                        <div style="font-weight:600;font-size:13.5px;"><?= htmlspecialchars($row['nama']) ?></div>
                        <div style="font-size:12px;color:#94a3b8;"><?= htmlspecialchars($row['departemen'] ?? '') ?></div>
                    </td>
                    <td style="font-size:13px;"><?= htmlspecialchars($row['jabatan'] ?? '-') ?></td>
                    <td><?= formatRupiah($row['gaji_pokok']) ?></td>
                    <td><?= $row['gaji_id'] ? formatRupiah($row['tunjangan']) : '<span style="color:#cbd5e1;">-</span>' ?></td>
                    <td><?= $row['gaji_id'] ? formatRupiah($row['potongan']) : '<span style="color:#cbd5e1;">-</span>' ?></td>
                    <td class="fw-semibold"><?= $row['gaji_id'] ? formatRupiah($row['gaji_bersih']) : '<span style="color:#cbd5e1;">-</span>' ?></td>
                    <td>
                        <?php if (!$row['gaji_id']): ?>
                            <span class="badge badge-secondary">Belum Input</span>
                        <?php elseif ($row['status_bayar'] === 'sudah'): ?>
                            <span class="badge badge-success">Lunas</span>
                        <?php else: ?>
                            <span class="badge badge-warning">Belum Bayar</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div style="display:flex;gap:6px;">
                            <?php if ($row['gaji_id']): ?>
                            <a href="<?= BASE_URL ?>/admin/gaji/proses.php?edit=<?= $row['gaji_id'] ?>&bulan=<?= $bulan ?>"
                               class="btn btn-warning btn-sm btn-icon" title="Edit">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                            </a>
                            <a href="<?= BASE_URL ?>/admin/gaji/slip.php?id=<?= $row['gaji_id'] ?>"
                               class="btn btn-outline btn-sm" title="Cetak Slip" target="_blank">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                                Slip
                            </a>
                            <?php else: ?>
                            <a href="<?= BASE_URL ?>/admin/gaji/proses.php?karyawan=<?= $row['id'] ?>&bulan=<?= $bulan ?>"
                               class="btn btn-primary btn-sm">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                                Input
                            </a>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
