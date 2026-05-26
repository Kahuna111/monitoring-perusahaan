<?php
require_once '../../config/database.php';
require_once '../../config/auth.php';
requireAdmin();

$pageTitle  = 'Tambah Karyawan';
$activePage = 'karyawan';
$breadcrumb = 'Karyawan / Tambah Baru';

$errors = [];

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

    // Buat akun user (opsional)
    $buat_akun  = isset($_POST['buat_akun']);
    $email_akun = sanitize($_POST['email_akun'] ?? '');
    $pass_akun  = $_POST['pass_akun'] ?? '';

    if (empty($nik))  $errors[] = 'NIK wajib diisi.';
    if (empty($nama)) $errors[] = 'Nama wajib diisi.';

    // Cek duplikat NIK
    $cek = $pdo->prepare("SELECT id FROM karyawan WHERE nik = ?");
    $cek->execute([$nik]);
    if ($cek->fetch()) $errors[] = 'NIK sudah terdaftar.';

    if ($buat_akun) {
        if (empty($email_akun)) $errors[] = 'Email akun wajib diisi.';
        if (strlen($pass_akun) < 6) $errors[] = 'Password minimal 6 karakter.';
        $cekEmail = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $cekEmail->execute([$email_akun]);
        if ($cekEmail->fetch()) $errors[] = 'Email sudah digunakan.';
    }

    if (empty($errors)) {
        $pdo->beginTransaction();
        try {
            $userId = null;
            if ($buat_akun) {
                $hash = password_hash($pass_akun, PASSWORD_BCRYPT);
                $insUser = $pdo->prepare("INSERT INTO users (nama, email, password, role) VALUES (?,?,?,'pegawai')");
                $insUser->execute([$nama, $email_akun, $hash]);
                $userId = $pdo->lastInsertId();
            }

            $ins = $pdo->prepare("INSERT INTO karyawan
                (user_id, nik, nama, jabatan, departemen, tanggal_masuk, gaji_pokok, no_telp, alamat, status)
                VALUES (?,?,?,?,?,?,?,?,?,?)");
            $ins->execute([$userId, $nik, $nama, $jabatan, $departemen,
                $tanggal_masuk ?: null, $gaji_pokok, $no_telp, $alamat, $status]);

            $pdo->commit();
            setFlash('success', "Karyawan <strong>$nama</strong> berhasil ditambahkan.");
            header('Location: ' . BASE_URL . '/admin/karyawan/index.php');
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = 'Gagal menyimpan data: ' . $e->getMessage();
        }
    }
}

require_once '../../includes/header.php';
require_once '../../includes/sidebar.php';
require_once '../../includes/navbar.php';
?>

<div class="page-header">
    <div class="page-header-left">
        <h2>Tambah Karyawan</h2>
        <p>Isi data lengkap karyawan baru</p>
    </div>
    <a href="<?= BASE_URL ?>/admin/karyawan/index.php" class="btn btn-outline">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
        Kembali
    </a>
</div>

<?php if ($errors): ?>
<div class="alert alert-danger">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
    <div><strong>Terdapat kesalahan:</strong><ul style="margin:4px 0 0 16px;"><?php foreach ($errors as $e) echo "<li>$e</li>"; ?></ul></div>
    <button class="alert-close"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>
</div>
<?php endif; ?>

<form method="POST" data-validate>
    <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">

    <div class="grid-2">

        <!-- Kolom kiri -->
        <div style="display:flex;flex-direction:column;gap:20px;">

            <div class="card">
                <div class="card-header">
                    <div class="card-title">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/></svg>
                        Data Pribadi
                    </div>
                </div>
                <div class="card-body">
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">NIK <span class="required">*</span></label>
                            <input type="text" name="nik" class="form-control" placeholder="Contoh: EMP001" required value="<?= htmlspecialchars($_POST['nik'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Status <span class="required">*</span></label>
                            <select name="status" class="form-control" required>
                                <option value="aktif"    <?= ($_POST['status']??'aktif')==='aktif'    ?'selected':'' ?>>Aktif</option>
                                <option value="nonaktif" <?= ($_POST['status']??'')==='nonaktif' ?'selected':'' ?>>Non-Aktif</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Nama Lengkap <span class="required">*</span></label>
                        <input type="text" name="nama" class="form-control" placeholder="Nama lengkap karyawan" required value="<?= htmlspecialchars($_POST['nama'] ?? '') ?>">
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Jabatan</label>
                            <input type="text" name="jabatan" class="form-control" placeholder="Contoh: Staff IT" value="<?= htmlspecialchars($_POST['jabatan'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Departemen</label>
                            <input type="text" name="departemen" class="form-control" placeholder="Contoh: Teknologi" value="<?= htmlspecialchars($_POST['departemen'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Tanggal Masuk</label>
                            <input type="text" name="tanggal_masuk" class="form-control datepicker" placeholder="YYYY-MM-DD" value="<?= htmlspecialchars($_POST['tanggal_masuk'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Gaji Pokok</label>
                            <div style="position:relative;">
                                <span style="position:absolute;left:12px;top:50%;transform:translateY(-50%);font-size:13px;color:#64748b;font-weight:600;">Rp</span>
                                <input type="text" name="gaji_pokok" class="form-control" style="padding-left:36px;" placeholder="0" data-rupiah value="<?= htmlspecialchars(number_format((float)($_POST['gaji_pokok']??0),0,',','.')) ?>">
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">No. Telepon</label>
                        <input type="text" name="no_telp" class="form-control" placeholder="08xxxxxxxxxx" value="<?= htmlspecialchars($_POST['no_telp'] ?? '') ?>">
                    </div>
                    <div class="form-group" style="margin-bottom:0;">
                        <label class="form-label">Alamat</label>
                        <textarea name="alamat" class="form-control" rows="3" placeholder="Alamat lengkap karyawan..."><?= htmlspecialchars($_POST['alamat'] ?? '') ?></textarea>
                    </div>
                </div>
            </div>

        </div>

        <!-- Kolom kanan -->
        <div style="display:flex;flex-direction:column;gap:20px;">

            <div class="card">
                <div class="card-header">
                    <div class="card-title">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 3h4a2 2 0 012 2v14a2 2 0 01-2 2h-4M10 17l5-5-5-5M15 12H3"/></svg>
                        Akun Login (Opsional)
                    </div>
                </div>
                <div class="card-body">
                    <div style="display:flex;align-items:center;gap:12px;padding:14px;background:#f8fafc;border-radius:10px;border:1.5px solid #e2e8f0;margin-bottom:16px;cursor:pointer;" id="akunToggle">
                        <input type="checkbox" name="buat_akun" id="buatAkun" style="width:18px;height:18px;cursor:pointer;accent-color:#6366f1;" <?= isset($_POST['buat_akun']) ? 'checked' : '' ?>>
                        <div>
                            <div style="font-size:13.5px;font-weight:600;color:#0f172a;">Buat akun login untuk karyawan ini</div>
                            <div style="font-size:12px;color:#64748b;">Karyawan bisa login ke sistem dengan email dan password</div>
                        </div>
                    </div>
                    <div id="akunFields" style="<?= isset($_POST['buat_akun']) ? '' : 'display:none;' ?>">
                        <div class="form-group">
                            <label class="form-label">Email Login</label>
                            <input type="email" name="email_akun" class="form-control" placeholder="email@perusahaan.com" value="<?= htmlspecialchars($_POST['email_akun'] ?? '') ?>">
                        </div>
                        <div class="form-group" style="margin-bottom:0;">
                            <label class="form-label">Password Awal</label>
                            <input type="text" name="pass_akun" class="form-control" placeholder="Minimal 6 karakter" value="<?= htmlspecialchars($_POST['pass_akun'] ?? '') ?>">
                            <div class="form-text">Informasikan password ini kepada karyawan untuk login pertama kali.</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tips -->
            <div class="card" style="border:1.5px solid #dbeafe;background:#eff6ff;">
                <div class="card-body">
                    <div style="font-size:13.5px;font-weight:700;color:#1e40af;margin-bottom:8px;display:flex;align-items:center;gap:8px;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px;"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                        Petunjuk Pengisian
                    </div>
                    <ul style="font-size:13px;color:#1e40af;line-height:1.8;padding-left:16px;">
                        <li>NIK harus unik untuk setiap karyawan</li>
                        <li>Gaji pokok digunakan sebagai dasar penggajian</li>
                        <li>Akun login bersifat opsional</li>
                        <li>Karyawan non-aktif tidak akan muncul di penggajian</li>
                    </ul>
                </div>
            </div>

        </div>
    </div><!-- /grid-2 -->

    <div style="display:flex;justify-content:flex-end;gap:10px;margin-top:20px;">
        <a href="<?= BASE_URL ?>/admin/karyawan/index.php" class="btn btn-outline">Batal</a>
        <button type="submit" class="btn btn-primary" id="btnSimpan">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 01-2-2V5a2 2 0 012-2h11l5 5v14a2 2 0 01-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
            Simpan Karyawan
        </button>
    </div>
</form>

<script>
const buatAkun   = document.getElementById('buatAkun');
const akunFields = document.getElementById('akunFields');
const akunToggle = document.getElementById('akunToggle');

akunToggle.addEventListener('click', (e) => {
    if (e.target !== buatAkun) buatAkun.checked = !buatAkun.checked;
    akunFields.style.display = buatAkun.checked ? 'block' : 'none';
});

// Rupiah format
document.querySelectorAll('[data-rupiah]').forEach(input => {
    input.addEventListener('input', () => {
        let raw = input.value.replace(/\D/g,'');
        input.value = raw ? parseInt(raw).toLocaleString('id-ID') : '';
    });
    input.addEventListener('blur', () => {
        // Store raw value
    });
});
</script>

<?php require_once '../../includes/footer.php'; ?>
