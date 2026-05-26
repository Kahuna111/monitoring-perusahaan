<?php
require_once '../../config/database.php';
require_once '../../config/auth.php';
requireAdmin();

$pageTitle  = 'Catat Uang Masuk';
$activePage = 'pemasukan';
$breadcrumb = 'Uang Masuk / Tambah Baru';

$errors = [];

// Ambil kategori pemasukan
$kategoriList = $pdo->query("SELECT * FROM kategori_transaksi WHERE tipe = 'pemasukan' ORDER BY nama")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tanggal     = sanitize($_POST['tanggal'] ?? '');
    $kategoriId  = (int)($_POST['kategori_id'] ?? 0);
    $deskripsi   = sanitize($_POST['deskripsi'] ?? '');
    $jumlah      = (float) str_replace(['.', ','], ['', '.'], $_POST['jumlah'] ?? 0);
    $dibuatOleh  = $_SESSION['user_id'];

    if (empty($tanggal))    $errors[] = 'Tanggal transaksi wajib diisi.';
    if (empty($kategoriId)) $errors[] = 'Kategori transaksi wajib dipilih.';
    if ($jumlah <= 0)       $errors[] = 'Jumlah pemasukan harus lebih besar dari 0.';

    // Proses upload bukti
    $buktiName = null;
    if (isset($_FILES['bukti']) && $_FILES['bukti']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath   = $_FILES['bukti']['tmp_name'];
        $fileName      = $_FILES['bukti']['name'];
        $fileSize      = $_FILES['bukti']['size'];
        $fileType      = $_FILES['bukti']['type'];
        $fileNameCmps  = explode(".", $fileName);
        $fileExtension = strtolower(end($fileNameCmps));

        $allowedExtensions = ['jpg', 'jpeg', 'png', 'pdf'];
        if (in_array($fileExtension, $allowedExtensions)) {
            // Batasi ukuran file 2MB
            if ($fileSize <= 2 * 1024 * 1024) {
                $newFileName = 'masuk_' . time() . '_' . md5(uniqid()) . '.' . $fileExtension;
                $uploadFileDir = '../../uploads/';
                
                // Pastikan folder uploads ada
                if (!is_dir($uploadFileDir)) {
                    mkdir($uploadFileDir, 0755, true);
                }

                $dest_path = $uploadFileDir . $newFileName;
                if (move_uploaded_file($fileTmpPath, $dest_path)) {
                    $buktiName = $newFileName;
                } else {
                    $errors[] = 'Terjadi kesalahan saat memindahkan file bukti.';
                }
            } else {
                $errors[] = 'Ukuran file bukti maksimal 2MB.';
            }
        } else {
            $errors[] = 'Ekstensi file tidak diizinkan. Hanya JPG, JPEG, PNG, dan PDF.';
        }
    }

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO pemasukan (tanggal, kategori_id, deskripsi, jumlah, bukti, dibuat_oleh) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$tanggal, $kategoriId, $deskripsi, $jumlah, $buktiName, $dibuatOleh]);

            setFlash('success', 'Transaksi uang masuk berhasil dicatat.');
            header('Location: ' . BASE_URL . '/admin/pemasukan/index.php');
            exit;
        } catch (Exception $e) {
            $errors[] = 'Gagal menyimpan transaksi: ' . $e->getMessage();
        }
    }
}

require_once '../../includes/header.php';
require_once '../../includes/sidebar.php';
require_once '../../includes/navbar.php';
?>

<div class="page-header">
    <div class="page-header-left">
        <h2>Catat Uang Masuk</h2>
        <p>Input transaksi pemasukan kas perusahaan secara berkala</p>
    </div>
    <a href="<?= BASE_URL ?>/admin/pemasukan/index.php" class="btn btn-outline">
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

<form method="POST" enctype="multipart/form-data" data-validate>
    <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">

    <div class="grid-2">

        <!-- Kolom Kiri: Form Fields -->
        <div style="display:flex;flex-direction:column;gap:20px;">
            <div class="card">
                <div class="card-header">
                    <div class="card-title">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 5v14M5 12l7-7 7 7"/>
                        </svg>
                        Rincian Transaksi
                    </div>
                </div>
                <div class="card-body">
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Tanggal Transaksi <span class="required">*</span></label>
                            <input type="text" name="tanggal" class="form-control datepicker" required value="<?= htmlspecialchars($_POST['tanggal'] ?? date('Y-m-d')) ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Kategori Pemasukan <span class="required">*</span></label>
                            <select name="kategori_id" class="form-control" required>
                                <option value="">-- Pilih Kategori --</option>
                                <?php foreach ($kategoriList as $kat): ?>
                                    <option value="<?= $kat['id'] ?>" <?= ($_POST['kategori_id'] ?? '') == $kat['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($kat['nama']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Jumlah Uang Masuk <span class="required">*</span></label>
                        <div style="position:relative;">
                            <span style="position:absolute;left:12px;top:50%;transform:translateY(-50%);font-size:13px;color:#64748b;font-weight:600;">Rp</span>
                            <input type="text" name="jumlah" class="form-control" style="padding-left:36px;" placeholder="0" data-rupiah required value="<?= htmlspecialchars($_POST['jumlah'] ?? '') ?>">
                        </div>
                    </div>

                    <div class="form-group" style="margin-bottom:0;">
                        <label class="form-label">Keterangan / Deskripsi</label>
                        <textarea name="deskripsi" class="form-control" rows="4" placeholder="Masukkan detail informasi transaksi pemasukan ini..."><?= htmlspecialchars($_POST['deskripsi'] ?? '') ?></textarea>
                    </div>
                </div>
            </div>
        </div>

        <!-- Kolom Kanan: Upload Bukti & Petunjuk -->
        <div style="display:flex;flex-direction:column;gap:20px;">
            <div class="card">
                <div class="card-header">
                    <div class="card-title">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4M17 8l-5-5-5 5M12 3v12"/>
                        </svg>
                        Upload Bukti Transaksi (Opsional)
                    </div>
                </div>
                <div class="card-body" style="display:flex;flex-direction:column;align-items:center;justify-content:center;min-height:180px;border:2px dashed #cbd5e1;border-radius:12px;margin:20px;padding:24px;text-align:center;background:#fafafa;position:relative;">
                    <input type="file" name="bukti" id="buktiInput" accept="image/*,application/pdf" style="position:absolute;width:100%;height:100%;opacity:0;cursor:pointer;top:0;left:0;">
                    
                    <div id="uploadPlaceholder">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="width:48px;height:48px;color:#94a3b8;margin-bottom:12px;">
                            <path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4M17 8l-5-5-5 5M12 3v12"/>
                        </svg>
                        <div style="font-size:14px;font-weight:600;color:#334155;">Pilih file bukti transaksi</div>
                        <div style="font-size:12px;color:#64748b;margin-top:4px;">Klik atau seret file ke sini<br>Maksimal 2MB (JPG, PNG, atau PDF)</div>
                    </div>

                    <!-- Preview Container -->
                    <div id="previewContainer" style="display:none;width:100%;height:100%;align-items:center;flex-direction:column;gap:12px;">
                        <img id="imagePreview" src="" alt="Preview Bukti" style="max-height:150px;max-width:100%;border-radius:8px;box-shadow:0 4px 6px -1px rgba(0,0,0,0.1);">
                        <div id="fileInfo" style="font-size:13px;font-weight:600;color:#0f172a;word-break:break-all;"></div>
                        <button type="button" id="removeFileBtn" class="btn btn-outline btn-sm" style="color:#ef4444;border-color:#fca5a5;">Hapus File</button>
                    </div>
                </div>
            </div>

            <!-- Petunjuk -->
            <div class="card" style="border:1.5px solid #dbeafe;background:#eff6ff;">
                <div class="card-body">
                    <div style="font-size:13.5px;font-weight:700;color:#1e40af;margin-bottom:8px;display:flex;align-items:center;gap:8px;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px;"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                        Informasi Pencatatan
                    </div>
                    <ul style="font-size:13px;color:#1e40af;line-height:1.8;padding-left:16px;">
                        <li>Pencatatan yang akurat membantu monitoring kesehatan keuangan perusahaan.</li>
                        <li>Pastikan jumlah uang yang dicatat sudah sesuai dengan nota/kwitansi.</li>
                        <li>Disarankan mengunggah foto bukti transaksi (resi, kwitansi, invoice) untuk memudahkan audit.</li>
                    </ul>
                </div>
            </div>
        </div>

    </div>

    <div style="display:flex;justify-content:flex-end;gap:10px;margin-top:20px;">
        <a href="<?= BASE_URL ?>/admin/pemasukan/index.php" class="btn btn-outline">Batal</a>
        <button type="submit" class="btn btn-success">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 01-2-2V5a2 2 0 012-2h11l5 5v14a2 2 0 01-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
            Simpan Transaksi
        </button>
    </div>
</form>

<script>
// Format Rupiah
document.querySelectorAll('[data-rupiah]').forEach(input => {
    input.addEventListener('input', () => {
        let raw = input.value.replace(/\D/g,'');
        input.value = raw ? parseInt(raw).toLocaleString('id-ID') : '';
    });
});

// File upload preview
const buktiInput        = document.getElementById('buktiInput');
const uploadPlaceholder = document.getElementById('uploadPlaceholder');
const previewContainer  = document.getElementById('previewContainer');
const imagePreview      = document.getElementById('imagePreview');
const fileInfo          = document.getElementById('fileInfo');
const removeFileBtn     = document.getElementById('removeFileBtn');

buktiInput.addEventListener('change', function() {
    const file = this.files[0];
    if (file) {
        fileInfo.textContent = file.name + ' (' + (file.size / 1024 / 1024).toFixed(2) + ' MB)';
        
        if (file.type.startsWith('image/')) {
            const reader = new FileReader();
            reader.onload = function(e) {
                imagePreview.src = e.target.result;
                imagePreview.style.display = 'block';
            }
            reader.readAsDataURL(file);
        } else {
            imagePreview.style.display = 'none'; // PDF atau non-gambar
        }

        uploadPlaceholder.style.display = 'none';
        previewContainer.style.display = 'flex';
    }
});

removeFileBtn.addEventListener('click', function(e) {
    e.stopPropagation();
    buktiInput.value = '';
    uploadPlaceholder.style.display = 'block';
    previewContainer.style.display = 'none';
    imagePreview.src = '';
});
</script>

<?php require_once '../../includes/footer.php'; ?>
