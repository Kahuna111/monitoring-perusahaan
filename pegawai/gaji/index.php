<?php
require_once '../../config/database.php';
require_once '../../config/auth.php';
requireLogin();

$pageTitle  = 'Gaji Saya';
$activePage = 'gaji';
$breadcrumb = 'Pribadi / Riwayat Gaji';

// Ambil data karyawan terkait akun ini
$stmtKaryawan = $pdo->prepare("SELECT id FROM karyawan WHERE user_id = ?");
$stmtKaryawan->execute([$_SESSION['user_id']]);
$karyawan = $stmtKaryawan->fetch();

// JIKA BELUM TERTAUT: Hubungkan secara otomatis jika ada data karyawan dengan NAMA yang sama
if (!$karyawan) {
    $stmtCari = $pdo->prepare("SELECT id FROM karyawan WHERE nama = ? AND user_id IS NULL LIMIT 1");
    $stmtCari->execute([$_SESSION['user_nama']]);
    $karyawanCocok = $stmtCari->fetch();
    
    if ($karyawanCocok) {
        $stmtLink = $pdo->prepare("UPDATE karyawan SET user_id = ? WHERE id = ?");
        $stmtLink->execute([$_SESSION['user_id'], $karyawanCocok['id']]);
        
        // Ambil ulang data karyawan setelah berhasil ditautkan
        $stmtKaryawan->execute([$_SESSION['user_id']]);
        $karyawan = $stmtKaryawan->fetch();
    }
}

$gajiList = [];
if ($karyawan) {
    // Ambil data riwayat gaji
    $stmtGaji = $pdo->prepare("
        SELECT tg.*, u.nama AS pembayar_nama
        FROM transaksi_gaji tg
        LEFT JOIN users u ON tg.dibuat_oleh = u.id
        WHERE tg.karyawan_id = ?
        ORDER BY tg.bulan DESC
    ");
    $stmtGaji->execute([$karyawan['id']]);
    $gajiList = $stmtGaji->fetchAll();
}

require_once '../../includes/header.php';
require_once '../../includes/sidebar.php';
require_once '../../includes/navbar.php';
?>

<div class="page-header">
    <div class="page-header-left">
        <h2>Riwayat Gaji Saya</h2>
        <p>Daftar seluruh penerimaan gaji bulanan Anda</p>
    </div>
</div>

<?php if (!$karyawan): ?>
    <div class="card" style="border:1.5px solid #fef08a; background:#fef9c3;">
        <div class="card-body" style="padding:24px; text-align:center;">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="width:48px;height:48px;color:#ca8a04;margin-bottom:12px;">
                <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
            </svg>
            <h3 style="margin:0 0 8px; font-weight:700; color:#854d0e;">Akun Belum Tertaut</h3>
            <p style="margin:0; color:#a16207; font-size:13.5px; max-width:600px; margin-inline:auto;">
                Akun login Anda belum dikaitkan dengan database Karyawan oleh Administrator. 
                Oleh karena itu, data riwayat gaji tidak dapat ditampilkan.
            </p>
        </div>
    </div>
<?php else: ?>

    <div class="card">
        <div class="card-header">
            <div class="card-title">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20M7 15h2m4 0h4"/></svg>
                Daftar Slip Gaji
            </div>
            <span style="font-size:13px;color:#64748b;"><?= count($gajiList) ?> slip gaji ditemukan</span>
        </div>
        <div class="table-responsive">
            <table class="table data-table">
                <thead>
                    <tr>
                        <th style="width: 50px;">No</th>
                        <th>Bulan Periode</th>
                        <th>Gaji Pokok</th>
                        <th>Tunjangan</th>
                        <th>Potongan</th>
                        <th>Total Gaji Bersih</th>
                        <th>Tanggal Bayar</th>
                        <th>Status</th>
                        <th style="width: 120px;" class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($gajiList as $i => $row): ?>
                    <tr>
                        <td style="color:#94a3b8;font-size:13px;"><?= $i+1 ?></td>
                        <td style="font-weight:600;font-size:13.5px;"><?= date('F Y', strtotime($row['bulan'] . '-01')) ?></td>
                        <td><?= formatRupiah($row['gaji_pokok']) ?></td>
                        <td style="color:#10b981;">+<?= formatRupiah($row['tunjangan']) ?></td>
                        <td style="color:#ef4444;">-<?= formatRupiah($row['potongan']) ?></td>
                        <td class="fw-semibold" style="color:#0f172a;"><?= formatRupiah($row['gaji_bersih']) ?></td>
                        <td style="font-size:13px;color:#64748b;"><?= $row['tanggal_bayar'] ? formatTanggal($row['tanggal_bayar']) : '<span style="color:#cbd5e1;">-</span>' ?></td>
                        <td>
                            <?php if ($row['status_bayar'] === 'sudah'): ?>
                                <span class="badge badge-success">Lunas / Dibayar</span>
                            <?php else: ?>
                                <span class="badge badge-warning">Belum Dibayar</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <?php if ($row['status_bayar'] === 'sudah'): ?>
                                <a href="<?= BASE_URL ?>/admin/gaji/slip.php?id=<?= $row['id'] ?>" 
                                   target="_blank" 
                                   class="btn btn-outline btn-sm" 
                                   style="display:inline-flex;align-items:center;gap:6px;"
                                   title="Unduh Slip Gaji">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:12.5px;height:12.5px;"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                                    Cetak Slip
                                </a>
                            <?php else: ?>
                                <span style="font-size:12px;color:#94a3b8;font-style:italic;">Menunggu</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($gajiList)): ?>
                    <tr>
                        <td colspan="9">
                            <div class="empty-state">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20M7 15h2m4 0h4"/></svg>
                                <h3>Belum ada data slip gaji</h3>
                                <p>Silakan hubungi administrator jika Anda belum menerima gaji bulan ini.</p>
                            </div>
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

<?php endif; ?>

<?php require_once '../../includes/footer.php'; ?>
