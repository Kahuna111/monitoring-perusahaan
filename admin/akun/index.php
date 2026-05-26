<?php
require_once '../../config/database.php';
require_once '../../config/auth.php';
requireAdmin();

$pageTitle  = 'Manajemen User';
$activePage = 'akun';
$breadcrumb = 'Pengaturan / Akun User';

$errors = [];
$successMsg = '';

// Proses Simpan User Baru
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'tambah') {
    $nama  = sanitize($_POST['nama'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $pass  = $_POST['password'] ?? '';
    $role  = in_array($_POST['role'] ?? '', ['admin', 'pegawai']) ? $_POST['role'] : 'pegawai';

    if (empty($nama))  $errors[] = 'Nama lengkap wajib diisi.';
    if (empty($email)) $errors[] = 'Email wajib diisi.';
    if (strlen($pass) < 6) $errors[] = 'Password minimal 6 karakter.';

    // Cek duplikat email
    $cek = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $cek->execute([$email]);
    if ($cek->fetch()) {
        $errors[] = 'Email sudah digunakan oleh akun lain.';
    }

    if (empty($errors)) {
        try {
            $hash = password_hash($pass, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("INSERT INTO users (nama, email, password, role) VALUES (?, ?, ?, ?)");
            $stmt->execute([$nama, $email, $hash, $role]);
            
            // Set flash and redirect to prevent double submit
            setFlash('success', "Akun untuk <strong>$nama</strong> berhasil dibuat.");
            header('Location: ' . BASE_URL . '/admin/akun/index.php');
            exit;
        } catch (Exception $e) {
            $errors[] = 'Gagal mendaftarkan user: ' . $e->getMessage();
        }
    }
}

// Proses Hapus User
if (isset($_GET['hapus'])) {
    $idHapus = (int)$_GET['hapus'];
    
    // Cegah hapus diri sendiri
    if ($idHapus === (int)$_SESSION['user_id']) {
        setFlash('danger', 'Anda tidak dapat menghapus akun Anda sendiri yang sedang aktif.');
        header('Location: ' . BASE_URL . '/admin/akun/index.php');
        exit;
    }

    // Ambil data user sebelum hapus
    $stmtUser = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmtUser->execute([$idHapus]);
    $userHapus = $stmtUser->fetch();

    if ($userHapus) {
        // Peringatan/Hapus referensi di karyawan
        $pdo->prepare("UPDATE karyawan SET user_id = NULL WHERE user_id = ?")->execute([$idHapus]);
        
        $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$idHapus]);
        setFlash('success', "Akun <strong>{$userHapus['nama']}</strong> berhasil dihapus.");
    } else {
        setFlash('danger', 'Akun tidak ditemukan.');
    }
    header('Location: ' . BASE_URL . '/admin/akun/index.php');
    exit;
}

// Ambil data semua user
$usersList = $pdo->query("SELECT * FROM users ORDER BY role ASC, nama ASC")->fetchAll();

require_once '../../includes/header.php';
require_once '../../includes/sidebar.php';
require_once '../../includes/navbar.php';
?>

<div class="page-header">
    <div class="page-header-left">
        <h2>Manajemen Akun User</h2>
        <p>Kelola hak akses pengguna untuk Admin dan Pegawai</p>
    </div>
</div>

<?php if ($errors): ?>
<div class="alert alert-danger">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
    <div><strong>Terdapat kesalahan:</strong><ul style="margin:4px 0 0 16px;"><?php foreach ($errors as $e) echo "<li>$e</li>"; ?></ul></div>
    <button class="alert-close"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>
</div>
<?php endif; ?>

<div class="grid-2" style="grid-template-columns: 1.4fr 1fr;">

    <!-- Kolom Kiri: Tabel User -->
    <div class="card">
        <div class="card-header">
            <div class="card-title">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/></svg>
                Daftar Akun Pengguna
            </div>
            <span style="font-size:13px;color:#64748b;"><?= count($usersList) ?> akun terdaftar</span>
        </div>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th style="width:50px;">No</th>
                        <th>Pengguna</th>
                        <th>Role / Level</th>
                        <th>Tgl. Terdaftar</th>
                        <th style="width:100px;" class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($usersList as $i => $u): ?>
                    <tr>
                        <td style="color:#94a3b8;font-size:13px;"><?= $i+1 ?></td>
                        <td>
                            <div style="display:flex;align-items:center;gap:10px;">
                                <div class="avatar" style="width:34px;height:34px;font-size:12px;background:<?= $u['role'] === 'admin' ? 'linear-gradient(135deg, #818cf8, #4f46e5)' : '#cbd5e1' ?>;">
                                    <?= getInitials($u['nama']) ?>
                                </div>
                                <div>
                                    <div style="font-weight:600;font-size:13.5px;"><?= htmlspecialchars($u['nama']) ?></div>
                                    <div style="font-size:12px;color:#94a3b8;"><?= htmlspecialchars($u['email']) ?></div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="badge <?= $u['role'] === 'admin' ? 'badge-success' : 'badge-secondary' ?>">
                                <?= ucfirst($u['role']) ?>
                            </span>
                        </td>
                        <td style="font-size:12.5px;color:#64748b;"><?= formatTanggal(date('Y-m-d', strtotime($u['created_at']))) ?></td>
                        <td class="text-center">
                            <?php if ($u['id'] === (int)$_SESSION['user_id']): ?>
                                <span style="font-size:11.5px;color:#94a3b8;font-style:italic;">Aktif (Anda)</span>
                            <?php else: ?>
                                <a href="<?= BASE_URL ?>/admin/akun/index.php?hapus=<?= $u['id'] ?>"
                                   class="btn btn-danger btn-sm btn-icon"
                                   title="Hapus Akun"
                                   data-confirm="Yakin ingin menghapus akun user '<?= htmlspecialchars($u['nama']) ?>'? Akun ini tidak akan bisa login lagi.">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6"/><path d="M10 11v6M14 11v6"/><path d="M9 6V4a1 1 0 011-1h4a1 1 0 011 1v2"/></svg>
                                </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Kolom Kanan: Form Tambah -->
    <div class="card" style="height: fit-content;">
        <div class="card-header">
            <div class="card-title">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="19" y1="8" x2="19" y2="14"/><line x1="16" y1="11" x2="22" y2="11"/></svg>
                Buat Akun Baru
            </div>
        </div>
        <div class="card-body">
            <form method="POST" data-validate>
                <input type="hidden" name="action" value="tambah">
                <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">

                <div class="form-group">
                    <label class="form-label">Nama Lengkap <span class="required">*</span></label>
                    <input type="text" name="nama" class="form-control" placeholder="Nama lengkap user" required value="<?= htmlspecialchars($_POST['nama'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label class="form-label">Email Address <span class="required">*</span></label>
                    <input type="email" name="email" class="form-control" placeholder="email@perusahaan.com" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Password <span class="required">*</span></label>
                        <div class="password-input-wrap">
                            <input type="password" name="password" class="form-control" placeholder="Min. 6 karakter" required>
                            <button type="button" class="toggle-password" title="Tampilkan password">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                            </button>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Role Akses <span class="required">*</span></label>
                        <select name="role" class="form-control" required>
                            <option value="pegawai" <?= ($_POST['role'] ?? '') === 'pegawai' ? 'selected' : '' ?>>Pegawai</option>
                            <option value="admin"   <?= ($_POST['role'] ?? '') === 'admin' ? 'selected' : '' ?>>Admin</option>
                        </select>
                    </div>
                </div>

                <div class="form-text" style="margin-bottom:16px;">
                    Catatan: Akun pegawai yang dibuat di sini dapat dikaitkan dengan data karyawan di halaman Manajemen Karyawan.
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    Daftarkan Akun
                </button>
            </form>
        </div>
    </div>

</div>

<?php require_once '../../includes/footer.php'; ?>
