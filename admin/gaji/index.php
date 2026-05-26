<?php
require_once '../../config/database.php';
require_once '../../config/auth.php';
requireAdmin();

$pageTitle  = 'Penggajian';
$activePage = 'gaji';
$breadcrumb = 'Penggajian / Daftar';

$bulan = $_GET['bulan'] ?? date('Y-m');

// ============================================================
// POST: Tandai Lunas (individu atau semua sekaligus)
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $tglBayar = date('Y-m-d'); // tanggal hari ini sebagai tanggal pembayaran

    if ($action === 'tandai_lunas' && !empty($_POST['gaji_id'])) {
        // Tandai satu data gaji sebagai lunas
        $gajiId = (int)$_POST['gaji_id'];
        $upd = $pdo->prepare("UPDATE transaksi_gaji SET status_bayar = 'sudah', tanggal_bayar = ? WHERE id = ? AND status_bayar = 'belum'");
        $upd->execute([$tglBayar, $gajiId]);
        setFlash('success', 'Gaji berhasil ditandai lunas.');

    } elseif ($action === 'tandai_semua_lunas') {
        // Tandai SEMUA gaji yang sudah diinput (status belum) di bulan ini menjadi lunas
        $bulanPost = sanitize($_POST['bulan'] ?? $bulan);
        $upd = $pdo->prepare("UPDATE transaksi_gaji SET status_bayar = 'sudah', tanggal_bayar = ? WHERE bulan = ? AND status_bayar = 'belum'");
        $upd->execute([$tglBayar, $bulanPost]);
        $affected = $upd->rowCount();
        setFlash('success', "Berhasil menandai <strong>$affected</strong> gaji karyawan sebagai Lunas untuk bulan " . date('F Y', strtotime($bulanPost . '-01')) . ".");
        $bulan = $bulanPost;
    }

    header('Location: ' . BASE_URL . '/admin/gaji/index.php?bulan=' . $bulan);
    exit;
}

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

// Apakah ada gaji yang bisa ditandai semua? (ada yg belum bayar)
$adaYangBelumBayar = $belumBayar > 0;

require_once '../../includes/header.php';
require_once '../../includes/sidebar.php';
require_once '../../includes/navbar.php';
?>

<?php $flash = getFlash(); if ($flash): ?>
<div class="alert alert-<?= $flash['type'] ?>">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 12l2 2 4-4"/><circle cx="12" cy="12" r="10"/></svg>
    <div><?= $flash['message'] ?></div>
    <button class="alert-close"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>
</div>
<?php endif; ?>

<div class="page-header">
    <div class="page-header-left">
        <h2>Penggajian</h2>
        <p>Kelola gaji karyawan bulan <?= date('F Y', strtotime($bulan . '-01')) ?></p>
    </div>
    <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:center;">
        <?php if ($adaYangBelumBayar): ?>
        <button type="button" class="btn btn-success" id="btnTandaiSemua" data-modal-open="modalTandaiSemua">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 12l2 2 4-4"/><circle cx="12" cy="12" r="10"/></svg>
            Tandai Semua Lunas
            <span style="background:rgba(255,255,255,0.3);color:#fff;font-size:11px;font-weight:700;padding:2px 7px;border-radius:10px;margin-left:4px;"><?= $belumBayar ?></span>
        </button>
        <?php endif; ?>
        <a href="<?= BASE_URL ?>/admin/gaji/proses.php?bulan=<?= $bulan ?>" class="btn btn-primary">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Input Penggajian
        </a>
    </div>
</div>

<!-- Filter Bulan -->
<div class="card mb-20">
    <div class="card-body" style="padding:16px 24px;">
        <form method="GET" class="filter-bar-form">
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
        <?php if ($adaYangBelumBayar): ?>
        <div style="font-size:12.5px;color:#64748b;display:flex;align-items:center;gap:6px;">
            <span style="width:8px;height:8px;border-radius:50%;background:#f59e0b;display:inline-block;animation:pulse 1.5s infinite;"></span>
            <?= $belumBayar ?> gaji menunggu pembayaran
        </div>
        <?php endif; ?>
    </div>
    <div class="table-responsive">
        <table class="table data-table" id="gajiTable">
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
                            <span class="badge badge-success">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="width:10px;height:10px;"><path d="M20 6L9 17l-5-5"/></svg>
                                Lunas
                            </span>
                        <?php else: ?>
                            <span class="badge badge-warning">Belum Bayar</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div style="display:flex;gap:6px;flex-wrap:wrap;">
                            <?php if ($row['gaji_id']): ?>
                            <a href="<?= BASE_URL ?>/admin/gaji/proses.php?edit=<?= $row['gaji_id'] ?>&bulan=<?= $bulan ?>"
                               class="btn btn-warning btn-sm btn-icon" title="Edit Gaji">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                            </a>
                            <a href="<?= BASE_URL ?>/admin/gaji/slip.php?id=<?= $row['gaji_id'] ?>"
                               class="btn btn-outline btn-sm btn-icon" title="Cetak Slip" target="_blank">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                            </a>
                            <?php if ($row['status_bayar'] === 'belum'): ?>
                            <!-- Tombol Tandai Lunas per baris -->
                            <form method="POST" style="display:inline;" onsubmit="return confirm('Tandai gaji <?= htmlspecialchars(addslashes($row['nama'])) ?> sebagai Lunas?')">
                                <input type="hidden" name="action" value="tandai_lunas">
                                <input type="hidden" name="gaji_id" value="<?= $row['gaji_id'] ?>">
                                <button type="submit" class="btn btn-success btn-sm btn-icon" title="Tandai Lunas"
                                        style="font-size:11px;gap:4px;padding:6px 10px;white-space:nowrap;">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="width:13px;height:13px;"><path d="M20 6L9 17l-5-5"/></svg>
                                    Lunas
                                </button>
                            </form>
                            <?php endif; ?>
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

<!-- =========================================================
     MODAL: Konfirmasi Tandai Semua Lunas
     ========================================================= -->
<div class="modal-overlay" id="modalTandaiSemua">
    <div class="modal" style="max-width:460px;">
        <div class="modal-header">
            <div class="modal-title" style="display:flex;align-items:center;gap:10px;">
                <div style="width:36px;height:36px;border-radius:10px;background:linear-gradient(135deg,#10b981,#059669);display:flex;align-items:center;justify-content:center;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5" style="width:18px;height:18px;"><path d="M9 12l2 2 4-4"/><circle cx="12" cy="12" r="10"/></svg>
                </div>
                Konfirmasi Tandai Semua Lunas
            </div>
            <button class="modal-close" data-modal-close>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>
        <div class="modal-body">
            <div style="background:#f0fdf4;border:1px solid #86efac;border-radius:10px;padding:16px;margin-bottom:16px;display:flex;gap:12px;align-items:flex-start;">
                <svg viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="2" style="width:20px;height:20px;flex-shrink:0;margin-top:1px;"><path d="M9 12l2 2 4-4"/><circle cx="12" cy="12" r="10"/></svg>
                <div>
                    <div style="font-weight:700;color:#15803d;font-size:14px;margin-bottom:4px;">Tandai Semua Gaji Lunas</div>
                    <div style="font-size:13px;color:#166534;line-height:1.5;">
                        Akan menandai <strong><?= $belumBayar ?> gaji</strong> yang berstatus <em>"Belum Bayar"</em> menjadi <strong>Lunas</strong> untuk bulan 
                        <strong><?= date('F Y', strtotime($bulan.'-01')) ?></strong>.
                        Tanggal bayar akan diisi otomatis dengan tanggal hari ini.
                    </div>
                </div>
            </div>
            <div style="background:#fff7ed;border:1px solid #fed7aa;border-radius:10px;padding:12px 16px;font-size:12.5px;color:#9a3412;">
                <strong>⚠ Perhatian:</strong> Aksi ini tidak dapat dibatalkan secara otomatis. Jika ada kesalahan, Anda perlu mengedit data gaji satu per satu.
            </div>
        </div>
        <div class="modal-footer" style="display:flex;justify-content:flex-end;gap:10px;">
            <button type="button" class="btn btn-outline" data-modal-close>Batal</button>
            <form method="POST" id="formTandaiSemua">
                <input type="hidden" name="action" value="tandai_semua_lunas">
                <input type="hidden" name="bulan" value="<?= htmlspecialchars($bulan) ?>">
                <button type="submit" class="btn btn-success" id="btnKonfirmasiSemua">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 12l2 2 4-4"/><circle cx="12" cy="12" r="10"/></svg>
                    Ya, Tandai <?= $belumBayar ?> Gaji Sebagai Lunas
                </button>
            </form>
        </div>
    </div>
</div>

<style>
@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.4; }
}
.btn-success {
    background: linear-gradient(135deg, #10b981, #059669);
    color: white;
    border: none;
}
.btn-success:hover {
    background: linear-gradient(135deg, #059669, #047857);
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(16,185,129,0.35);
}
</style>

<?php require_once '../../includes/footer.php'; ?>
