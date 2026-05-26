<?php
require_once '../../config/database.php';
require_once '../../config/auth.php';
requireAdmin();

$pageTitle  = 'Uang Masuk (Pemasukan)';
$activePage = 'pemasukan';
$breadcrumb = 'Keuangan / Uang Masuk';

// Filter
$tanggalMulai = $_GET['tanggal_mulai'] ?? date('Y-m-01');
$tanggalSelesai = $_GET['tanggal_selesai'] ?? date('Y-m-t');
$filterKategori = $_GET['kategori_id'] ?? '';

$where = ['p.tanggal BETWEEN ? AND ?'];
$params = [$tanggalMulai, $tanggalSelesai];

if ($filterKategori !== '') {
    $where[] = 'p.kategori_id = ?';
    $params[] = (int)$filterKategori;
}

$sql = "SELECT p.*, kt.nama AS kategori_nama, u.nama AS pembuat_nama 
        FROM pemasukan p
        LEFT JOIN kategori_transaksi kt ON p.kategori_id = kt.id
        LEFT JOIN users u ON p.dibuat_oleh = u.id
        WHERE " . implode(' AND ', $where) . " 
        ORDER BY p.tanggal DESC, p.id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$pemasukanList = $stmt->fetchAll();

// Hitung total
$totalPemasukan = array_sum(array_column($pemasukanList, 'jumlah'));

// Ambil kategori untuk filter dropdown
$kategoriList = $pdo->query("SELECT * FROM kategori_transaksi WHERE tipe = 'pemasukan' ORDER BY nama")->fetchAll();

require_once '../../includes/header.php';
require_once '../../includes/sidebar.php';
require_once '../../includes/navbar.php';
?>

<div class="page-header">
    <div class="page-header-left">
        <h2>Uang Masuk (Pemasukan)</h2>
        <p>Catat dan pantau seluruh arus kas masuk perusahaan</p>
    </div>
    <a href="<?= BASE_URL ?>/admin/pemasukan/tambah.php" class="btn btn-success" id="btnTambahPemasukan">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        Catat Uang Masuk
    </a>
</div>

<!-- Filter Bar -->
<div class="card mb-20">
    <div class="card-body" style="padding:16px 24px;">
        <form method="GET" style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end;">
            <div class="form-group" style="margin-bottom:0;flex:1;min-width:150px;">
                <label class="form-label" style="margin-bottom:6px;font-size:12px;">Mulai Tanggal</label>
                <input type="text" name="tanggal_mulai" class="form-control datepicker" value="<?= htmlspecialchars($tanggalMulai) ?>">
            </div>
            <div class="form-group" style="margin-bottom:0;flex:1;min-width:150px;">
                <label class="form-label" style="margin-bottom:6px;font-size:12px;">Selesai Tanggal</label>
                <input type="text" name="tanggal_selesai" class="form-control datepicker" value="<?= htmlspecialchars($tanggalSelesai) ?>">
            </div>
            <div class="form-group" style="margin-bottom:0;min-width:200px;">
                <label class="form-label" style="margin-bottom:6px;font-size:12px;">Kategori</label>
                <select name="kategori_id" class="form-control">
                    <option value="">Semua Kategori</option>
                    <?php foreach ($kategoriList as $kat): ?>
                        <option value="<?= $kat['id'] ?>" <?= $filterKategori == $kat['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($kat['nama']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="btn btn-primary" style="height:42px;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                Filter
            </button>
            <?php if (isset($_GET['kategori_id']) || isset($_GET['tanggal_mulai'])): ?>
                <a href="<?= BASE_URL ?>/admin/pemasukan/index.php" class="btn btn-outline" style="height:42px;display:inline-flex;align-items:center;">Reset</a>
            <?php endif; ?>
        </form>
    </div>
</div>

<!-- Stats Card -->
<div class="stats-grid mb-24" style="grid-template-columns: 1fr;">
    <div class="stat-card green" style="max-width:350px;">
        <div class="stat-icon green">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12l7-7 7 7"/></svg>
        </div>
        <div class="stat-info">
            <div class="stat-label">Total Pemasukan Periode Ini</div>
            <div class="stat-value" style="font-size:22px;"><?= formatRupiah($totalPemasukan) ?></div>
            <div class="stat-sub"><?= formatTanggal($tanggalMulai) ?> s/d <?= formatTanggal($tanggalSelesai) ?></div>
        </div>
    </div>
</div>

<!-- Table -->
<div class="card">
    <div class="card-header">
        <div class="card-title">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            Daftar Uang Masuk
        </div>
        <span style="font-size:13px;color:#64748b;"><?= count($pemasukanList) ?> transaksi ditemukan</span>
    </div>
    <div class="table-responsive">
        <table class="table data-table" id="tablePemasukan">
            <thead>
                <tr>
                    <th style="width: 50px;">No</th>
                    <th>Tanggal</th>
                    <th>Kategori</th>
                    <th>Keterangan / Deskripsi</th>
                    <th>Dicatat Oleh</th>
                    <th>Bukti</th>
                    <th class="text-right">Jumlah</th>
                    <th style="width: 100px;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($pemasukanList as $i => $row): ?>
                <tr>
                    <td style="color:#94a3b8;font-size:13px;"><?= $i+1 ?></td>
                    <td style="white-space:nowrap;font-size:13.5px;"><?= formatTanggal($row['tanggal']) ?></td>
                    <td>
                        <span class="badge badge-success" style="font-size:11.5px;font-weight:600;">
                            <?= htmlspecialchars($row['kategori_nama'] ?? 'Lain-lain') ?>
                        </span>
                    </td>
                    <td style="font-size:13.5px;"><?= htmlspecialchars($row['deskripsi'] ?? '-') ?></td>
                    <td style="font-size:13px;color:#64748b;"><?= htmlspecialchars($row['pembuat_nama'] ?? 'System') ?></td>
                    <td>
                        <?php if ($row['bukti']): ?>
                            <a href="<?= BASE_URL ?>/uploads/<?= htmlspecialchars($row['bukti']) ?>" target="_blank" class="btn btn-outline btn-sm" style="padding:4px 8px;font-size:11.5px;">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:12px;height:12px;margin-right:4px;"><path d="M15 3h4a2 2 0 012 2v14a2 2 0 01-2 2h-4M10 17l5-5-5-5M15 12H3"/></svg>
                                Lihat Bukti
                            </a>
                        <?php else: ?>
                            <span style="color:#cbd5e1;font-size:12px;">Tidak Ada</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-right fw-semibold" style="color:#10b981;font-size:14px;">
                        +<?= formatRupiah($row['jumlah']) ?>
                    </td>
                    <td>
                        <div style="display:flex;gap:6px;">
                            <a href="<?= BASE_URL ?>/admin/pemasukan/hapus.php?id=<?= $row['id'] ?>"
                               class="btn btn-danger btn-sm btn-icon"
                               title="Hapus"
                               data-confirm="Yakin ingin menghapus transaksi ini? Tindakan ini tidak dapat dibatalkan.">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6"/><path d="M10 11v6M14 11v6"/><path d="M9 6V4a1 1 0 011-1h4a1 1 0 011 1v2"/></svg>
                            </a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
