<?php
require_once '../../config/database.php';
require_once '../../config/auth.php';
requireAdmin();

$pageTitle  = 'Profil Saya';
$activePage = 'akun';
$breadcrumb = 'Pengaturan / Profil Saya';

$errors = [];
$successMsg = '';

// Ambil data user aktif
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if (!$user) {
    header('Location: ' . BASE_URL . '/logout.php');
    exit;
}

// Proses Update Profil
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_profile') {
    $nama  = sanitize($_POST['nama'] ?? '');
    $email = sanitize($_POST['email'] ?? '');

    if (empty($nama))  $errors[] = 'Nama lengkap wajib diisi.';
    if (empty($email)) $errors[] = 'Email wajib diisi.';

    // Cek duplikat email
    $cek = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
    $cek->execute([$email, $user['id']]);
    if ($cek->fetch()) {
        $errors[] = 'Email sudah digunakan oleh akun lain.';
    }

    if (empty($errors)) {
        try {
            $update = $pdo->prepare("UPDATE users SET nama = ?, email = ? WHERE id = ?");
            $update->execute([$nama, $email, $user['id']]);

            // Update session
            $_SESSION['user_nama']  = $nama;
            $_SESSION['user_email'] = $email;

            setFlash('success', 'Profil Anda berhasil diperbarui.');
            header('Location: ' . BASE_URL . '/admin/akun/profil.php');
            exit;
        } catch (Exception $e) {
            $errors[] = 'Gagal memperbarui profil: ' . $e->getMessage();
        }
    }
}

// Proses Update Password
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_password') {
    $passwordLama = $_POST['password_lama'] ?? '';
    $passwordBaru = $_POST['password_baru'] ?? '';
    $konfirmasi   = $_POST['konfirmasi_password'] ?? '';

    if (empty($passwordLama)) $errors[] = 'Password lama wajib diisi.';
    if (strlen($passwordBaru) < 6) $errors[] = 'Password baru minimal 6 karakter.';
    if ($passwordBaru !== $konfirmasi) $errors[] = 'Konfirmasi password baru tidak cocok.';

    // Verifikasi password lama
    if (!password_verify($passwordLama, $user['password'])) {
        $errors[] = 'Password lama yang Anda masukkan salah.';
    }

    if (empty($errors)) {
        try {
            $hash = password_hash($passwordBaru, PASSWORD_BCRYPT);
            $update = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $update->execute([$hash, $user['id']]);

            setFlash('success', 'Password Anda berhasil diubah.');
            header('Location: ' . BASE_URL . '/admin/akun/profil.php');
            exit;
        } catch (Exception $e) {
            $errors[] = 'Gagal memperbarui password: ' . $e->getMessage();
        }
    }
}

require_once '../../includes/header.php';
require_once '../../includes/sidebar.php';
require_once '../../includes/navbar.php';
?>

<div class="page-header">
    <div class="page-header-left">
        <h2>Profil Saya</h2>
        <p>Kelola data diri dan keamanan akun Anda</p>
    </div>
</div>

<?php if ($errors): ?>
<div class="alert alert-danger">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
    <div><strong>Terdapat kesalahan:</strong><ul style="margin:4px 0 0 16px;"><?php foreach ($errors as $e) echo "<li>$e</li>"; ?></ul></div>
    <button class="alert-close"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>
</div>
<?php endif; ?>

<div class="grid-2">

    <!-- Card Edit Profil -->
    <div class="card">
        <div class="card-header">
            <div class="card-title">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/></svg>
                Informasi Akun
            </div>
        </div>
        <div class="card-body">
            <div style="display:flex;align-items:center;gap:16px;margin-bottom:24px;padding-bottom:16px;border-bottom:1px solid #f1f5f9;">
                <div class="avatar" style="width:64px;height:64px;font-size:24px;font-weight:700;">
                    <?= getInitials($user['nama']) ?>
                </div>
                <div>
                    <h3 style="margin:0 0 4px;font-size:16px;font-weight:700;color:#0f172a;"><?= htmlspecialchars($user['nama']) ?></h3>
                    <p style="margin:0 0 4px;color:#64748b;font-size:13px;"><?= htmlspecialchars($user['email']) ?></p>
                    <span class="badge badge-success"><?= ucfirst($user['role']) ?></span>
                </div>
            </div>

            <form method="POST">
                <input type="hidden" name="action" value="update_profile">
                <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">

                <div class="form-group">
                    <label class="form-label">Nama Lengkap</label>
                    <input type="text" name="nama" class="form-control" value="<?= htmlspecialchars($user['nama']) ?>" required>
                </div>

                <div class="form-group" style="margin-bottom:20px;">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" required>
                </div>

                <button type="submit" class="btn btn-primary" style="width:100%;">Simpan Perubahan</button>
            </form>
        </div>
    </div>

    <!-- Card Ganti Password -->
    <div class="card">
        <div class="card-header">
            <div class="card-title">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg>
                Keamanan & Password
            </div>
        </div>
        <div class="card-body">
            <form method="POST">
                <input type="hidden" name="action" value="update_password">
                <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">

                <div class="form-group">
                    <label class="form-label">Password Saat Ini</label>
                    <div class="password-input-wrap">
                        <input type="password" name="password_lama" class="form-control" placeholder="Masukkan password sekarang" required>
                        <button type="button" class="toggle-password" title="Tampilkan password">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                        </button>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Password Baru</label>
                        <div class="password-input-wrap">
                            <input type="password" name="password_baru" class="form-control" placeholder="Minimal 6 karakter" required>
                            <button type="button" class="toggle-password" title="Tampilkan password">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                            </button>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Konfirmasi Password Baru</label>
                        <div class="password-input-wrap">
                            <input type="password" name="konfirmasi_password" class="form-control" placeholder="Ulangi password baru" required>
                            <button type="button" class="toggle-password" title="Tampilkan password">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                            </button>
                        </div>
                    </div>
                </div>

                <div style="margin-top:20px;">
                    <button type="submit" class="btn btn-warning" style="width:100%;">Ubah Password</button>
                </div>
            </form>
        </div>
    </div>

</div>

<?php require_once '../../includes/footer.php'; ?>
