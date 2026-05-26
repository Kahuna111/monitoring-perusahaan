<?php
require_once '../../config/database.php';
require_once '../../config/auth.php';
requireAdmin();

$pageTitle  = 'Manajemen Karyawan';
$activePage = 'karyawan';
$breadcrumb = 'Manajemen / Karyawan';

// Filter
$filterStatus     = $_GET['status']     ?? '';
$filterDepartemen = $_GET['departemen'] ?? '';
$search           = $_GET['q']          ?? '';

$where  = ['1=1'];
$params = [];

if ($filterStatus) {
    $where[]  = 'k.status = ?';
    $params[] = $filterStatus;
}
if ($filterDepartemen) {
    $where[]  = 'k.departemen = ?';
    $params[] = $filterDepartemen;
}
if ($search) {
    $where[]  = '(k.nama LIKE ? OR k.nik LIKE ? OR k.jabatan LIKE ?)';
    $s = "%$search%";
    array_push($params, $s, $s, $s);
}

$sql = "SELECT k.* FROM karyawan k WHERE " . implode(' AND ', $where) . " ORDER BY k.created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$karyawanList = $stmt->fetchAll();

// Departemen list untuk filter
$departemenList = $pdo->query("SELECT DISTINCT departemen FROM karyawan WHERE departemen IS NOT NULL ORDER BY departemen")->fetchAll(PDO::FETCH_COLUMN);

require_once '../../includes/header.php';
require_once '../../includes/sidebar.php';
require_once '../../includes/navbar.php';
?>

<div class="page-header">
    <div class="page-header-left">
        <h2>Data Karyawan</h2>
        <p>Kelola seluruh data karyawan perusahaan</p>
    </div>
    <a href="<?= BASE_URL ?>/admin/karyawan/tambah.php" class="btn btn-primary" id="btnTambahKaryawan">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        Tambah Karyawan
    </a>
</div>

<!-- Filter Bar -->
<div class="card mb-20">
    <div class="card-body" style="padding:16px 24px;">
        <form method="GET" class="filter-bar-form">
            <div class="search-input" style="position:relative;flex:1;min-width:200px;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="position:absolute;left:12px;top:50%;transform:translateY(-50%);width:15px;height:15px;color:#94a3b8;">
                    <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
                </svg>
                <input type="text" name="q" class="form-control" style="padding-left:36px;" placeholder="Cari nama, NIK, jabatan..." value="<?= htmlspecialchars($search) ?>">
            </div>
            <select name="status" class="form-control">
                <option value="">Semua Status</option>
                <option value="aktif"    <?= $filterStatus==='aktif'    ?'selected':'' ?>>Aktif</option>
                <option value="nonaktif" <?= $filterStatus==='nonaktif' ?'selected':'' ?>>Non-Aktif</option>
            </select>
            <select name="departemen" class="form-control">
                <option value="">Semua Departemen</option>
                <?php foreach ($departemenList as $dep): ?>
                    <option value="<?= htmlspecialchars($dep) ?>" <?= $filterDepartemen===$dep?'selected':'' ?>>
                        <?= htmlspecialchars($dep) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn btn-primary">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                Filter
            </button>
            <?php if ($filterStatus || $filterDepartemen || $search): ?>
            <a href="<?= BASE_URL ?>/admin/karyawan/index.php" class="btn btn-outline" style="display:inline-flex;align-items:center;justify-content:center;">Reset</a>
            <?php endif; ?>
        </form>
    </div>
</div>

<!-- Table -->
<div class="card">
    <div class="card-header">
        <div class="card-title">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/>
                <path d="M23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"/>
            </svg>
            Daftar Karyawan
        </div>
        <span style="font-size:13px;color:#64748b;"><?= count($karyawanList) ?> karyawan ditemukan</span>
    </div>
    <div class="table-responsive">
        <table class="table data-table" id="tableKaryawan">
            <thead>
                <tr>
                    <th>No</th>
                    <th>NIK</th>
                    <th>Nama Karyawan</th>
                    <th>Jabatan</th>
                    <th>Departemen</th>
                    <th>Tgl. Masuk</th>
                    <th>Gaji Pokok</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($karyawanList as $i => $k): ?>
                <tr>
                    <td style="color:#94a3b8;font-size:13px;"><?= $i+1 ?></td>
                    <td><span style="font-family:monospace;font-size:13px;background:#f1f5f9;padding:2px 8px;border-radius:5px;"><?= htmlspecialchars($k['nik']) ?></span></td>
                    <td>
                        <div style="display:flex;align-items:center;gap:10px;">
                            <div class="avatar" style="width:34px;height:34px;font-size:12px;flex-shrink:0;"><?= getInitials($k['nama']) ?></div>
                            <div>
                                <div style="font-weight:600;font-size:13.5px;"><?= htmlspecialchars($k['nama']) ?></div>
                                <?php if ($k['no_telp']): ?>
                                <div style="font-size:12px;color:#94a3b8;"><?= htmlspecialchars($k['no_telp']) ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </td>
                    <td><?= htmlspecialchars($k['jabatan'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($k['departemen'] ?? '-') ?></td>
                    <td style="white-space:nowrap;font-size:13px;color:#64748b;"><?= $k['tanggal_masuk'] ? formatTanggal($k['tanggal_masuk']) : '-' ?></td>
                    <td class="fw-semibold" style="color:#0f172a;"><?= formatRupiah($k['gaji_pokok']) ?></td>
                    <td>
                        <span class="badge <?= $k['status']==='aktif' ? 'badge-success' : 'badge-secondary' ?>">
                            <?= ucfirst($k['status']) ?>
                        </span>
                    </td>
                    <td>
                        <div style="display:flex;gap:6px;">
                            <a href="<?= BASE_URL ?>/admin/karyawan/edit.php?id=<?= $k['id'] ?>"
                               class="btn btn-warning btn-sm btn-icon" title="Edit">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                            </a>
                            <a href="<?= BASE_URL ?>/admin/karyawan/hapus.php?id=<?= $k['id'] ?>"
                               class="btn btn-danger btn-sm btn-icon"
                               title="Hapus"
                               data-confirm="Yakin ingin menghapus karyawan '<?= htmlspecialchars($k['nama']) ?>'?">
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
