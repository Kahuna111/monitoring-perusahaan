<?php
require_once '../../config/database.php';
require_once '../../config/auth.php';
requireAdmin();

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: ' . BASE_URL . '/admin/karyawan/index.php'); exit; }

$karyawan = $pdo->prepare("SELECT * FROM karyawan WHERE id = ?");
$karyawan->execute([$id]);
$karyawan = $karyawan->fetch();
if (!$karyawan) { setFlash('danger','Karyawan tidak ditemukan.'); header('Location: ' . BASE_URL . '/admin/karyawan/index.php'); exit; }

$pageTitle  = 'Edit Karyawan';
$activePage = 'karyawan';
$breadcrumb = 'Karyawan / Edit';
$errors     = [];

// Ambil daftar user yang belum tertaut dengan karyawan lain, atau yang sedang tertaut dengan karyawan ini (mendukung semua role)
$stmtUsers = $pdo->prepare("
    SELECT id, nama, email, role 
    FROM users 
    WHERE (id NOT IN (SELECT user_id FROM karyawan WHERE user_id IS NOT NULL AND id != ?) OR id = ?)
    ORDER BY nama ASC
");
$stmtUsers->execute([$id, $karyawan['user_id']]);
$userPegawaiList = $stmtUsers->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nik           = sanitize($_POST['nik'] ?? '');
    $nama          = sanitize($_POST['nama'] ?? '');
    $jabatan       = sanitize($_POST['jabatan'] ?? '');
    $departemen    = sanitize($_POST['departemen'] ?? '');
    $tanggal_masuk = sanitize($_POST['tanggal_masuk'] ?? '');
    $gaji_pokok    = (float) str_replace(['.', ','], ['', '.'], $_POST['gaji_pokok'] ?? 0);
    $no_telp       = sanitize($_POST['no_telp'] ?? '');
    $alamat        = sanitize($_POST['alamat'] ?? '');
    $status        = in_array($_POST['status'] ?? '', ['aktif','nonaktif']) ? $_POST['status'] : 'aktif';
    $user_id       = $_POST['user_id'] !== '' ? (int)$_POST['user_id'] : null;

    if (empty($nik))  $errors[] = 'NIK wajib diisi.';
    if (empty($nama)) $errors[] = 'Nama wajib diisi.';

    $cek = $pdo->prepare("SELECT id FROM karyawan WHERE nik = ? AND id != ?");
    $cek->execute([$nik, $id]);
    if ($cek->fetch()) $errors[] = 'NIK sudah digunakan oleh karyawan lain.';

    if (empty($errors)) {
        $upd = $pdo->prepare("UPDATE karyawan SET
            user_id=?, nik=?, nama=?, jabatan=?, departemen=?, tanggal_masuk=?,
            gaji_pokok=?, no_telp=?, alamat=?, status=?
            WHERE id=?");
        $upd->execute([$user_id, $nik, $nama, $jabatan, $departemen,
            $tanggal_masuk ?: null, $gaji_pokok, $no_telp, $alamat, $status, $id]);

        setFlash('success', "Data karyawan <strong>$nama</strong> berhasil diperbarui.");
        header('Location: ' . BASE_URL . '/admin/karyawan/index.php');
        exit;
    }
    // Overlay form data
    $karyawan = array_merge($karyawan, $_POST);
}

require_once '../../includes/header.php';
require_once '../../includes/sidebar.php';
require_once '../../includes/navbar.php';
?>

<div class="page-header">
    <div class="page-header-left">
        <h2>Edit Karyawan</h2>
        <p>Perbarui data karyawan: <strong><?= htmlspecialchars($karyawan['nama']) ?></strong></p>
    </div>
    <a href="<?= BASE_URL ?>/admin/karyawan/index.php" class="btn btn-outline">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
        Kembali
    </a>
</div>

<?php if ($errors): ?>
<div class="alert alert-danger">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
    <div><strong>Terdapat kesalahan:</strong><ul style="margin:4px 0 0 16px;"><?php foreach($errors as $e) echo "<li>$e</li>"; ?></ul></div>
</div>
<?php endif; ?>

<form method="POST" data-validate>
    <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">

    <div class="card">
        <div class="card-header">
            <div class="card-title">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/></svg>
                Data Karyawan
            </div>
            <div class="avatar" style="width:40px;height:40px;font-size:15px;"><?= getInitials($karyawan['nama']) ?></div>
        </div>
        <div class="card-body">
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">NIK <span class="required">*</span></label>
                    <input type="text" name="nik" class="form-control" required value="<?= htmlspecialchars($karyawan['nik']) ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-control">
                        <option value="aktif"    <?= $karyawan['status']==='aktif'    ?'selected':'' ?>>Aktif</option>
                        <option value="nonaktif" <?= $karyawan['status']==='nonaktif' ?'selected':'' ?>>Non-Aktif</option>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Akun Login Pengguna (Admin / Pegawai)</label>
                <select name="user_id" class="form-control" <?= empty($userPegawaiList) ? 'disabled' : '' ?>>
                    <?php if (empty($userPegawaiList)): ?>
                        <option value="">-- Tidak ada akun user yang belum terhubung --</option>
                    <?php else: ?>
                        <option value="">-- Hubungkan dengan Akun User --</option>
                        <?php foreach ($userPegawaiList as $u): ?>
                            <option value="<?= $u['id'] ?>" <?= (int)$karyawan['user_id'] === $u['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($u['nama']) ?> (<?= htmlspecialchars($u['email']) ?>) - [<?= ucfirst($u['role']) ?>]
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
                <div class="form-text">Hubungkan data karyawan ini dengan akun pengguna agar mereka bisa login dan melihat slip gaji. Jika akun belum terdaftar, buat terlebih dahulu di <strong>Pengaturan → Akun User</strong>.</div>
            </div>
            <div class="form-group">
                <label class="form-label">Nama Lengkap <span class="required">*</span></label>
                <input type="text" name="nama" class="form-control" required value="<?= htmlspecialchars($karyawan['nama']) ?>">
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Jabatan</label>
                    <input type="text" name="jabatan" class="form-control" value="<?= htmlspecialchars($karyawan['jabatan'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Departemen</label>
                    <input type="text" name="departemen" class="form-control" value="<?= htmlspecialchars($karyawan['departemen'] ?? '') ?>">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Tanggal Masuk</label>
                    <input type="text" name="tanggal_masuk" class="form-control datepicker" value="<?= htmlspecialchars($karyawan['tanggal_masuk'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Gaji Pokok</label>
                    <div style="position:relative;">
                        <span style="position:absolute;left:12px;top:50%;transform:translateY(-50%);font-size:13px;color:#64748b;font-weight:600;">Rp</span>
                        <input type="text" name="gaji_pokok" class="form-control" style="padding-left:36px;" data-rupiah
                            value="<?= number_format((float)$karyawan['gaji_pokok'], 0, ',', '.') ?>">
                    </div>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">No. Telepon</label>
                    <input type="text" name="no_telp" class="form-control" value="<?= htmlspecialchars($karyawan['no_telp'] ?? '') ?>">
                </div>
            </div>
            <div class="form-group" style="margin-bottom:0;">
                <label class="form-label">Alamat</label>
                <textarea name="alamat" class="form-control" rows="3"><?= htmlspecialchars($karyawan['alamat'] ?? '') ?></textarea>
            </div>
        </div>
        <div class="card-footer" style="display:flex;justify-content:flex-end;gap:10px;">
            <a href="<?= BASE_URL ?>/admin/karyawan/index.php" class="btn btn-outline">Batal</a>
            <button type="submit" class="btn btn-primary">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 01-2-2V5a2 2 0 012-2h11l5 5v14a2 2 0 01-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                Simpan Perubahan
            </button>
        </div>
    </div>
</form>

<?php require_once '../../includes/footer.php'; ?>
