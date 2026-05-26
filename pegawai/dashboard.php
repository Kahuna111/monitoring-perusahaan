<?php
require_once '../config/database.php';
require_once '../config/auth.php';
requireLogin();

$pageTitle  = 'Dashboard Pegawai';
$activePage = 'dashboard';
$breadcrumb = 'Beranda / Dashboard';

// Ambil data user beserta detail karyawan jika ada
$stmt = $pdo->prepare("
    SELECT u.nama, u.email, u.role,
           k.id AS karyawan_id, k.nik, k.jabatan, k.departemen, 
           k.tanggal_masuk, k.gaji_pokok, k.no_telp, k.alamat, k.status
    FROM users u
    LEFT JOIN karyawan k ON k.user_id = u.id
    WHERE u.id = ?
");
$stmt->execute([$_SESSION['user_id']]);
$userData = $stmt->fetch();

// Ambil data gaji terakhir jika terdaftar sebagai karyawan
$gajiTerakhir = null;
if ($userData && $userData['karyawan_id']) {
    $stmtGaji = $pdo->prepare("
        SELECT * FROM transaksi_gaji 
        WHERE karyawan_id = ? AND status_bayar = 'sudah' 
        ORDER BY bulan DESC LIMIT 1
    ");
    $stmtGaji->execute([$userData['karyawan_id']]);
    $gajiTerakhir = $stmtGaji->fetch();
}

require_once '../includes/header.php';
require_once '../includes/sidebar.php';
require_once '../includes/navbar.php';
?>

<div class="page-header">
    <div class="page-header-left">
        <h2>Selamat Datang, <?= htmlspecialchars($userData['nama']) ?>!</h2>
        <p>Akses informasi profil, slip gaji, dan riwayat pendapatan Anda di sini</p>
    </div>
</div>

<?php if (!$userData['karyawan_id']): ?>
    <!-- Notice Belum Dikaitkan -->
    <div class="card" style="border:1.5px solid #fef08a; background:#fef9c3; margin-bottom:24px;">
        <div class="card-body" style="padding:24px; text-align:center;">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="width:48px;height:48px;color:#ca8a04;margin-bottom:12px;">
                <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
            </svg>
            <h3 style="margin:0 0 8px; font-weight:700; color:#854d0e;">Akun Belum Tertaut</h3>
            <p style="margin:0; color:#a16207; font-size:13.5px; max-width:600px; margin-inline:auto; line-height:1.6;">
                Akun login Anda belum dikaitkan dengan database Karyawan oleh Administrator. 
                Silakan hubungi bagian administrasi/HRD untuk menautkan akun Anda agar dapat melihat slip gaji dan detail jabatan.
            </p>
        </div>
    </div>
<?php else: ?>

    <!-- Stat Cards -->
    <div class="stats-grid mb-24" style="grid-template-columns: repeat(3, 1fr);">
        <div class="stat-card indigo">
            <div class="stat-icon indigo">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/></svg>
            </div>
            <div class="stat-info">
                <div class="stat-label">Gaji Pokok Utama</div>
                <div class="stat-value" style="font-size:17px;"><?= formatRupiah($userData['gaji_pokok']) ?></div>
                <div class="stat-sub">Gaji dasar per bulan</div>
            </div>
        </div>

        <div class="stat-card green">
            <div class="stat-icon green">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 8v4l3 3"/></svg>
            </div>
            <div class="stat-info">
                <div class="stat-label">Tanggal Masuk</div>
                <div class="stat-value" style="font-size:17px;"><?= formatTanggal($userData['tanggal_masuk']) ?></div>
                <div class="stat-sub">Masa bakti karyawan</div>
            </div>
        </div>

        <div class="stat-card amber">
            <div class="stat-icon amber">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M9 12l2 2 4-4"/></svg>
            </div>
            <div class="stat-info">
                <div class="stat-label">Status Keaktifan</div>
                <div class="stat-value" style="font-size:17px;"><?= ucfirst($userData['status']) ?></div>
                <div class="stat-sub">Status kontrak aktif</div>
            </div>
        </div>
    </div>

    <div class="grid-2">

        <!-- Column Kiri: Informasi Profil Karyawan -->
        <div class="card">
            <div class="card-header">
                <div class="card-title">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/></svg>
                    Detail Profil Karyawan
                </div>
                <a href="<?= BASE_URL ?>/pegawai/profil.php" class="btn btn-outline btn-sm">Edit Profil</a>
            </div>
            <div class="card-body">
                <table class="table" style="border:none;">
                    <tr style="border-bottom:1px solid #f1f5f9;">
                        <td style="padding:12px 0;font-weight:600;color:#64748b;width:150px;">NIK</td>
                        <td style="padding:12px 0;font-family:monospace;font-weight:700;"><?= htmlspecialchars($userData['nik']) ?></td>
                    </tr>
                    <tr style="border-bottom:1px solid #f1f5f9;">
                        <td style="padding:12px 0;font-weight:600;color:#64748b;">Nama Lengkap</td>
                        <td style="padding:12px 0;font-weight:600;"><?= htmlspecialchars($userData['nama']) ?></td>
                    </tr>
                    <tr style="border-bottom:1px solid #f1f5f9;">
                        <td style="padding:12px 0;font-weight:600;color:#64748b;">Jabatan</td>
                        <td style="padding:12px 0;"><?= htmlspecialchars($userData['jabatan'] ?? '-') ?></td>
                    </tr>
                    <tr style="border-bottom:1px solid #f1f5f9;">
                        <td style="padding:12px 0;font-weight:600;color:#64748b;">Departemen</td>
                        <td style="padding:12px 0;"><?= htmlspecialchars($userData['departemen'] ?? '-') ?></td>
                    </tr>
                    <tr style="border-bottom:1px solid #f1f5f9;">
                        <td style="padding:12px 0;font-weight:600;color:#64748b;">No. Telepon</td>
                        <td style="padding:12px 0;"><?= htmlspecialchars($userData['no_telp'] ?? '-') ?></td>
                    </tr>
                    <tr>
                        <td style="padding:12px 0;font-weight:600;color:#64748b;">Alamat</td>
                        <td style="padding:12px 0;line-height:1.5;"><?= nl2br(htmlspecialchars($userData['alamat'] ?? '-')) ?></td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Column Kanan: Gaji Terakhir & Quick Link -->
        <div style="display:flex;flex-direction:column;gap:20px;">
            
            <div class="card">
                <div class="card-header">
                    <div class="card-title">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20M7 15h2m4 0h4"/></svg>
                        Gaji Terakhir Diterima
                    </div>
                </div>
                <div class="card-body">
                    <?php if ($gajiTerakhir): ?>
                        <div style="margin-bottom:16px;">
                            <div style="font-size:12px;color:#64748b;margin-bottom:4px;">Bulan Periode</div>
                            <div style="font-size:16px;font-weight:700;color:#0f172a;"><?= date('F Y', strtotime($gajiTerakhir['bulan'] . '-01')) ?></div>
                        </div>
                        <div style="margin-bottom:20px;padding:12px;background:#f8fafc;border-radius:8px;border:1px solid #e2e8f0;display:flex;justify-content:space-between;align-items:center;">
                            <div>
                                <div style="font-size:11px;color:#64748b;text-transform:uppercase;">Gaji Bersih Diterima</div>
                                <div style="font-size:20px;font-weight:800;color:#10b981;"><?= formatRupiah($gajiTerakhir['gaji_bersih']) ?></div>
                            </div>
                            <a href="<?= BASE_URL ?>/admin/gaji/slip.php?id=<?= $gajiTerakhir['id'] ?>" target="_blank" class="btn btn-outline btn-sm">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px;height:14px;margin-right:4px;"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                                Cetak Slip
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="empty-state" style="padding: 24px 0;">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/></svg>
                            <h3>Belum ada riwayat gaji</h3>
                            <p>Pembayaran gaji Anda belum terdaftar di sistem.</p>
                        </div>
                    <?php endif; ?>
                    <a href="<?= BASE_URL ?>/pegawai/gaji/index.php" class="btn btn-primary" style="width:100%;">
                        Lihat Seluruh Riwayat Gaji
                    </a>
                </div>
            </div>

            <!-- Petunjuk Keamanan -->
            <div class="card" style="border:1.5px solid #dbeafe;background:#eff6ff;">
                <div class="card-body">
                    <div style="font-size:13px;font-weight:700;color:#1e40af;margin-bottom:8px;display:flex;align-items:center;gap:8px;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px;"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                        Tips Keamanan Akun
                    </div>
                    <p style="font-size:12.5px;color:#1e40af;line-height:1.6;margin:0;">
                        Selalu rahasiakan password Anda. Jika mencurigai aktivitas mencurigakan, segera ganti password Anda melalui menu <strong>Profil Saya</strong>.
                    </p>
                </div>
            </div>

        </div>

    </div>

<?php endif; ?>

<?php require_once '../includes/footer.php'; ?>
