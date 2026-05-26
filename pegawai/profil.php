<?php
require_once '../config/database.php';
require_once '../config/auth.php';
requireLogin();

$pageTitle  = 'Profil Saya';
$activePage = 'profil';
$breadcrumb = 'Pribadi / Profil Saya';

$errors = [];
$successMsg = '';

// Ambil data user beserta detail karyawan jika ada
$stmt = $pdo->prepare("
    SELECT u.*, k.id AS karyawan_id, k.nik, k.jabatan, k.departemen, k.no_telp, k.alamat
    FROM users u
    LEFT JOIN karyawan k ON k.user_id = u.id
    WHERE u.id = ?
");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if (!$user) {
    header('Location: ' . BASE_URL . '/logout.php');
    exit;
}

// Proses Update Profil
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_profile') {
    $nama   = sanitize($_POST['nama'] ?? '');
    $email  = sanitize($_POST['email'] ?? '');
    $noTelp = sanitize($_POST['no_telp'] ?? '');
    $alamat = sanitize($_POST['alamat'] ?? '');

    if (empty($nama))  $errors[] = 'Nama lengkap wajib diisi.';
    if (empty($email)) $errors[] = 'Email wajib diisi.';

    // Cek duplikat email
    $cek = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
    $cek->execute([$email, $user['id']]);
    if ($cek->fetch()) {
        $errors[] = 'Email sudah digunakan oleh akun lain.';
    }

    if (empty($errors)) {
        $pdo->beginTransaction();
        try {
            // Update tabel users
            $updateUser = $pdo->prepare("UPDATE users SET nama = ?, email = ? WHERE id = ?");
            $updateUser->execute([$nama, $email, $user['id']]);

            // Update session
            $_SESSION['user_nama']  = $nama;
            $_SESSION['user_email'] = $email;

            // Update tabel karyawan jika sudah dikaitkan
            if ($user['karyawan_id']) {
                $updateKaryawan = $pdo->prepare("UPDATE karyawan SET nama = ?, no_telp = ?, alamat = ? WHERE user_id = ?");
                $updateKaryawan->execute([$nama, $noTelp, $alamat, $user['id']]);
            }

            $pdo->commit();
            setFlash('success', 'Profil Anda berhasil diperbarui.');
            header('Location: ' . BASE_URL . '/pegawai/profil.php');
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
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
            header('Location: ' . BASE_URL . '/pegawai/profil.php');
            exit;
        } catch (Exception $e) {
            $errors[] = 'Gagal memperbarui password: ' . $e->getMessage();
        }
    }
}

require_once '../includes/header.php';
require_once '../includes/sidebar.php';
require_once '../includes/navbar.php';
?>

<div class="page-header">
    <div class="page-header-left">
        <h2>Profil Saya</h2>
        <p>Lihat detail data karyawan dan perbarui data kontak pribadi Anda</p>
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

    <!-- Card Profil Pegawai -->
    <div class="card">
        <div class="card-header">
            <div class="card-title">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/></svg>
                Detail Informasi Anda
            </div>
        </div>
        <div class="card-body">
            <form method="POST">
                <input type="hidden" name="action" value="update_profile">
                <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">

                <div class="form-group">
                    <label class="form-label">Nama Lengkap</label>
                    <input type="text" name="nama" class="form-control" value="<?= htmlspecialchars($user['nama']) ?>" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" required>
                </div>

                <?php if ($user['karyawan_id']): ?>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">NIK (Locked)</label>
                            <input type="text" class="form-control" value="<?= htmlspecialchars($user['nik']) ?>" disabled style="background:#f1f5f9;">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Jabatan (Locked)</label>
                            <input type="text" class="form-control" value="<?= htmlspecialchars($user['jabatan'] ?? '-') ?>" disabled style="background:#f1f5f9;">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">No. Telepon / WhatsApp</label>
                        <input type="text" name="no_telp" class="form-control" value="<?= htmlspecialchars($user['no_telp'] ?? '') ?>" placeholder="08xxxxxxxxxx">
                    </div>

                    <div class="form-group" style="margin-bottom:20px;">
                        <label class="form-label">Alamat Lengkap</label>
                        <textarea name="alamat" class="form-control" rows="3" placeholder="Masukkan alamat lengkap Anda..."><?= htmlspecialchars($user['alamat'] ?? '') ?></textarea>
                    </div>
                <?php else: ?>
                    <div style="padding:14px;background:#f8fafc;border-radius:8px;border:1px solid #e2e8f0;font-size:12.5px;color:#64748b;margin-bottom:20px;">
                        Catatan: Akun Anda belum terhubung dengan data karyawan di sistem HRD. Hubungi admin jika ini merupakan kesalahan.
                    </div>
                <?php endif; ?>

                <button type="submit" class="btn btn-primary" style="width:100%;">Perbarui Profil</button>
            </form>
        </div>
    </div>

    <!-- Card Ganti Password -->
    <div class="card" style="height: fit-content;">
        <div class="card-header">
            <div class="card-title">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg>
                Ubah Password Login
            </div>
        </div>
        <div class="card-body">
            <form method="POST">
                <input type="hidden" name="action" value="update_password">
                <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">

                <div class="form-group">
                    <label class="form-label">Password Lama</label>
                    <input type="password" name="password_lama" class="form-control" placeholder="Masukkan password lama Anda" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Password Baru</label>
                    <input type="password" name="password_baru" class="form-control" placeholder="Minimal 6 karakter" required>
                </div>

                <div class="form-group" style="margin-bottom:20px;">
                    <label class="form-label">Ulangi Password Baru</label>
                    <input type="password" name="konfirmasi_password" class="form-control" placeholder="Konfirmasi password baru" required>
                </div>

                <button type="submit" class="btn btn-warning" style="width:100%;">Ganti Password</button>
            </form>
        </div>
    </div>

</div>

<?php require_once '../includes/footer.php'; ?>
