<?php
require_once '../../config/database.php';
require_once '../../config/auth.php';
requireAdmin();

$pageTitle  = 'Input Penggajian';
$activePage = 'gaji';
$breadcrumb = 'Penggajian / Input';

$bulan      = $_GET['bulan']    ?? date('Y-m');
$karyawanId = (int)($_GET['karyawan'] ?? 0);
$editId     = (int)($_GET['edit']     ?? 0);
$errors     = [];

// Mode edit: ambil data gaji
$gajiData = null;
if ($editId) {
    $s = $pdo->prepare("SELECT tg.*, k.nama, k.gaji_pokok, k.jabatan, k.departemen FROM transaksi_gaji tg JOIN karyawan k ON k.id=tg.karyawan_id WHERE tg.id=?");
    $s->execute([$editId]);
    $gajiData = $s->fetch();
    if ($gajiData) $karyawanId = $gajiData['karyawan_id'];
}

// Ambil data karyawan
$karyawanData = null;
if ($karyawanId) {
    $s = $pdo->prepare("SELECT * FROM karyawan WHERE id=? AND status='aktif'");
    $s->execute([$karyawanId]);
    $karyawanData = $s->fetch();
}

// Semua karyawan aktif (untuk dropdown)
$allKaryawan = $pdo->query("SELECT id, nik, nama, jabatan, gaji_pokok FROM karyawan WHERE status='aktif' ORDER BY nama")->fetchAll();

// POST: Simpan
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $kId       = (int)$_POST['karyawan_id'];
    $bln       = sanitize($_POST['bulan'] ?? date('Y-m'));
    $gPok      = (float) str_replace(['.', ','], ['', '.'], $_POST['gaji_pokok'] ?? 0);
    $tunjangan = (float) str_replace(['.', ','], ['', '.'], $_POST['tunjangan'] ?? 0);
    $potongan  = (float) str_replace(['.', ','], ['', '.'], $_POST['potongan'] ?? 0);
    $gBersih   = $gPok + $tunjangan - $potongan;
    $status    = in_array($_POST['status_bayar']??'', ['belum','sudah']) ? $_POST['status_bayar'] : 'belum';
    $tglBayar  = ($status === 'sudah' && !empty($_POST['tanggal_bayar'])) ? sanitize($_POST['tanggal_bayar']) : null;
    $ket       = sanitize($_POST['keterangan'] ?? '');

    if (!$kId)           $errors[] = 'Pilih karyawan terlebih dahulu.';
    if ($gPok <= 0)      $errors[] = 'Gaji pokok harus lebih dari 0.';

    if (empty($errors)) {
        if ($editId) {
            $upd = $pdo->prepare("UPDATE transaksi_gaji SET
                karyawan_id=?, bulan=?, gaji_pokok=?, tunjangan=?, potongan=?,
                gaji_bersih=?, status_bayar=?, tanggal_bayar=?, keterangan=?
                WHERE id=?");
            $upd->execute([$kId,$bln,$gPok,$tunjangan,$potongan,$gBersih,$status,$tglBayar,$ket,$editId]);
            setFlash('success','Data gaji berhasil diperbarui.');
        } else {
            // Cek duplikat
            $cek = $pdo->prepare("SELECT id FROM transaksi_gaji WHERE karyawan_id=? AND bulan=?");
            $cek->execute([$kId,$bln]);
            if ($cek->fetch()) {
                $errors[] = 'Data gaji karyawan ini untuk bulan tersebut sudah ada. Gunakan tombol Edit.';
            } else {
                $ins = $pdo->prepare("INSERT INTO transaksi_gaji
                    (karyawan_id,bulan,gaji_pokok,tunjangan,potongan,gaji_bersih,status_bayar,tanggal_bayar,keterangan,dibuat_oleh)
                    VALUES (?,?,?,?,?,?,?,?,?,?)");
                $ins->execute([$kId,$bln,$gPok,$tunjangan,$potongan,$gBersih,$status,$tglBayar,$ket,$_SESSION['user_id']]);
                setFlash('success','Data gaji berhasil disimpan.');
            }
        }
        if (empty($errors)) {
            header('Location: ' . BASE_URL . '/admin/gaji/index.php?bulan=' . $bln);
            exit;
        }
    }
}

require_once '../../includes/header.php';
require_once '../../includes/sidebar.php';
require_once '../../includes/navbar.php';
?>

<div class="page-header">
    <div class="page-header-left">
        <h2><?= $editId ? 'Edit' : 'Input' ?> Penggajian</h2>
        <p>Masukkan detail gaji karyawan untuk bulan yang dipilih</p>
    </div>
    <a href="<?= BASE_URL ?>/admin/gaji/index.php?bulan=<?= $bulan ?>" class="btn btn-outline">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
        Kembali
    </a>
</div>

<?php if ($errors): ?>
<div class="alert alert-danger">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
    <div><strong>Kesalahan:</strong><ul style="margin:4px 0 0 16px;"><?php foreach($errors as $e) echo "<li>$e</li>"; ?></ul></div>
</div>
<?php endif; ?>

<div class="grid-2">
<form method="POST" id="gajiForm" data-validate>
    <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">

    <div style="display:flex;flex-direction:column;gap:20px;">
        <div class="card">
            <div class="card-header">
                <div class="card-title">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/></svg>
                    Pilih Karyawan & Bulan
                </div>
            </div>
            <div class="card-body">
                <div class="form-group">
                    <label class="form-label">Karyawan <span class="required">*</span></label>
                    <select name="karyawan_id" id="karyawanSelect" class="form-control" required <?= $editId ? 'disabled' : '' ?>>
                        <option value="">-- Pilih Karyawan --</option>
                        <?php foreach ($allKaryawan as $k): ?>
                        <option value="<?= $k['id'] ?>"
                            data-gaji="<?= $k['gaji_pokok'] ?>"
                            data-nama="<?= htmlspecialchars($k['nama']) ?>"
                            data-jabatan="<?= htmlspecialchars($k['jabatan'] ?? '') ?>"
                            <?= ($karyawanId == $k['id'] || ($gajiData && $gajiData['karyawan_id'] == $k['id'])) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($k['nik']) ?> – <?= htmlspecialchars($k['nama']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if ($editId): ?><input type="hidden" name="karyawan_id" value="<?= $gajiData['karyawan_id'] ?? $karyawanId ?>"><?php endif; ?>
                </div>
                <div class="form-group" style="margin-bottom:0;">
                    <label class="form-label">Bulan Gaji <span class="required">*</span></label>
                    <input type="month" name="bulan" class="form-control" value="<?= $gajiData['bulan'] ?? $bulan ?>" required <?= $editId ? 'readonly' : '' ?>>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <div class="card-title">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20M7 15h2m4 0h4"/></svg>
                    Komponen Gaji
                </div>
            </div>
            <div class="card-body">
                <div class="form-group">
                    <label class="form-label">Gaji Pokok <span class="required">*</span></label>
                    <div style="position:relative;">
                        <span style="position:absolute;left:12px;top:50%;transform:translateY(-50%);font-size:13px;color:#64748b;font-weight:600;">Rp</span>
                        <input type="text" name="gaji_pokok" id="gajiPokok" class="form-control" style="padding-left:36px;"
                            data-rupiah value="<?= number_format((float)($gajiData['gaji_pokok'] ?? $karyawanData['gaji_pokok'] ?? 0),0,',','.') ?>" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Tunjangan</label>
                        <div style="position:relative;">
                            <span style="position:absolute;left:12px;top:50%;transform:translateY(-50%);font-size:13px;color:#64748b;font-weight:600;">Rp</span>
                            <input type="text" name="tunjangan" id="tunjangan" class="form-control" style="padding-left:36px;" data-rupiah
                                value="<?= number_format((float)($gajiData['tunjangan'] ?? 0),0,',','.') ?>">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Potongan</label>
                        <div style="position:relative;">
                            <span style="position:absolute;left:12px;top:50%;transform:translateY(-50%);font-size:13px;color:#64748b;font-weight:600;">Rp</span>
                            <input type="text" name="potongan" id="potongan" class="form-control" style="padding-left:36px;" data-rupiah
                                value="<?= number_format((float)($gajiData['potongan'] ?? 0),0,',','.') ?>">
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Status Pembayaran</label>
                    <select name="status_bayar" id="statusBayar" class="form-control">
                        <option value="belum" <?= ($gajiData['status_bayar']??'belum')==='belum'?'selected':'' ?>>Belum Dibayar</option>
                        <option value="sudah" <?= ($gajiData['status_bayar']??'')==='sudah'?'selected':'' ?>>Sudah Dibayar</option>
                    </select>
                </div>
                <div class="form-group" id="tglBayarWrap" style="<?= ($gajiData['status_bayar']??'')==='sudah'?'':'display:none;' ?>">
                    <label class="form-label">Tanggal Pembayaran</label>
                    <input type="text" name="tanggal_bayar" class="form-control datepicker"
                        value="<?= htmlspecialchars($gajiData['tanggal_bayar'] ?? '') ?>">
                </div>
                <div class="form-group" style="margin-bottom:0;">
                    <label class="form-label">Keterangan</label>
                    <textarea name="keterangan" class="form-control" rows="2" placeholder="Catatan tambahan..."><?= htmlspecialchars($gajiData['keterangan'] ?? '') ?></textarea>
                </div>
            </div>
        </div>
    </div>
</form>

<!-- Preview Gaji -->
<div style="display:flex;flex-direction:column;gap:20px;">
    <div class="card">
        <div class="card-header">
            <div class="card-title">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 17v-2m3 2v-4m3 4v-6"/><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/></svg>
                Preview Slip Gaji
            </div>
        </div>
        <div class="card-body" id="previewSlip">
            <div style="background:#f8fafc;border-radius:12px;padding:20px;">
                <div style="text-align:center;margin-bottom:16px;padding-bottom:12px;border-bottom:2px dashed #e2e8f0;">
                    <div style="font-size:16px;font-weight:800;color:#0f172a;"><?= APP_NAME ?></div>
                    <div style="font-size:12px;color:#64748b;">Slip Gaji Karyawan</div>
                </div>

                <div style="margin-bottom:14px;">
                    <div style="font-size:12px;color:#64748b;">Nama Karyawan</div>
                    <div id="previewNama" style="font-size:14px;font-weight:700;color:#0f172a;">-</div>
                </div>
                <div style="margin-bottom:14px;">
                    <div style="font-size:12px;color:#64748b;">Jabatan</div>
                    <div id="previewJabatan" style="font-size:13px;color:#475569;">-</div>
                </div>
                <div style="margin-bottom:16px;padding-bottom:12px;border-bottom:1px dashed #e2e8f0;">
                    <div style="font-size:12px;color:#64748b;">Bulan</div>
                    <div id="previewBulan" style="font-size:13px;font-weight:600;color:#0f172a;">-</div>
                </div>

                <!-- Rincian -->
                <div style="display:flex;flex-direction:column;gap:8px;margin-bottom:16px;">
                    <div style="display:flex;justify-content:space-between;font-size:13px;">
                        <span style="color:#64748b;">Gaji Pokok</span>
                        <span id="prevGajiPokok" style="font-weight:600;">Rp 0</span>
                    </div>
                    <div style="display:flex;justify-content:space-between;font-size:13px;">
                        <span style="color:#10b981;">+ Tunjangan</span>
                        <span id="prevTunjangan" style="color:#10b981;font-weight:600;">Rp 0</span>
                    </div>
                    <div style="display:flex;justify-content:space-between;font-size:13px;">
                        <span style="color:#ef4444;">– Potongan</span>
                        <span id="prevPotongan" style="color:#ef4444;font-weight:600;">Rp 0</span>
                    </div>
                </div>

                <div style="background:linear-gradient(135deg,#6366f1,#4f46e5);border-radius:10px;padding:14px 16px;display:flex;justify-content:space-between;align-items:center;">
                    <span style="color:rgba(255,255,255,0.8);font-size:13px;font-weight:600;">Gaji Bersih</span>
                    <span id="prevGajiBersih" style="color:#fff;font-size:20px;font-weight:800;">Rp 0</span>
                </div>
            </div>
        </div>
        <div class="card-footer" style="display:flex;justify-content:flex-end;gap:10px;">
            <a href="<?= BASE_URL ?>/admin/gaji/index.php?bulan=<?= $bulan ?>" class="btn btn-outline">Batal</a>
            <button type="submit" form="gajiForm" class="btn btn-primary">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 01-2-2V5a2 2 0 012-2h11l5 5v14a2 2 0 01-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                Simpan Gaji
            </button>
        </div>
    </div>
</div>
</div><!-- /grid-2 -->

<script>
const fmt = n => 'Rp ' + parseInt(n||0).toLocaleString('id-ID');
const raw = s => parseInt((s||'0').replace(/\./g,'').replace(',','.')) || 0;

function updatePreview() {
    const sel    = document.getElementById('karyawanSelect');
    const opt    = sel?.options[sel.selectedIndex];
    const gPok   = raw(document.getElementById('gajiPokok')?.value);
    const tunj   = raw(document.getElementById('tunjangan')?.value);
    const pot    = raw(document.getElementById('potongan')?.value);
    const bersih = gPok + tunj - pot;

    document.getElementById('previewNama').textContent    = opt?.dataset.nama    || '-';
    document.getElementById('previewJabatan').textContent = opt?.dataset.jabatan || '-';
    document.getElementById('prevGajiPokok').textContent  = fmt(gPok);
    document.getElementById('prevTunjangan').textContent  = fmt(tunj);
    document.getElementById('prevPotongan').textContent   = fmt(pot);
    document.getElementById('prevGajiBersih').textContent = fmt(bersih);

    const bulan = document.querySelector('[name="bulan"]')?.value;
    if (bulan) {
        const d = new Date(bulan + '-01');
        document.getElementById('previewBulan').textContent =
            d.toLocaleDateString('id-ID', {month:'long',year:'numeric'});
    }
}

// Auto-fill gaji pokok dari pilihan karyawan
document.getElementById('karyawanSelect')?.addEventListener('change', function() {
    const opt  = this.options[this.selectedIndex];
    const gaji = opt?.dataset.gaji || 0;
    const inp  = document.getElementById('gajiPokok');
    if (inp) {
        inp.value = parseInt(gaji).toLocaleString('id-ID');
    }
    updatePreview();
});

// Toggle tanggal bayar
document.getElementById('statusBayar')?.addEventListener('change', function() {
    document.getElementById('tglBayarWrap').style.display = this.value === 'sudah' ? 'block' : 'none';
});

// Live update preview
['gajiPokok','tunjangan','potongan'].forEach(id => {
    document.getElementById(id)?.addEventListener('input', updatePreview);
});
document.querySelector('[name="bulan"]')?.addEventListener('change', updatePreview);

// Init
updatePreview();
</script>

<?php require_once '../../includes/footer.php'; ?>
