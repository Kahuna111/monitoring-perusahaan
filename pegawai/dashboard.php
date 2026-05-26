<?php
require_once '../config/database.php';
require_once '../config/auth.php';
requireLogin();

$pageTitle  = 'Dashboard Pegawai';
$activePage = 'dashboard';
$breadcrumb = 'Beranda / Dashboard';

// Ambil data user beserta detail karyawan jika ada
$stmt = $pdo->prepare("
    SELECT u.nama, u.email, u.role, u.foto, u.created_at AS user_created,
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
$totalGajiDiterima = 0;
$jumlahBulanGaji = 0;
if ($userData && $userData['karyawan_id']) {
    $stmtGaji = $pdo->prepare("
        SELECT * FROM transaksi_gaji 
        WHERE karyawan_id = ? AND status_bayar = 'sudah' 
        ORDER BY bulan DESC LIMIT 1
    ");
    $stmtGaji->execute([$userData['karyawan_id']]);
    $gajiTerakhir = $stmtGaji->fetch();

    // Hitung total gaji diterima
    $stmtTotal = $pdo->prepare("
        SELECT COALESCE(SUM(gaji_bersih), 0) AS total, COUNT(*) AS jumlah
        FROM transaksi_gaji 
        WHERE karyawan_id = ? AND status_bayar = 'sudah'
    ");
    $stmtTotal->execute([$userData['karyawan_id']]);
    $totalData = $stmtTotal->fetch();
    $totalGajiDiterima = $totalData['total'];
    $jumlahBulanGaji = $totalData['jumlah'];
}

// Hitung masa kerja
$masaKerja = '';
if ($userData && $userData['tanggal_masuk']) {
    $masuk = new DateTime($userData['tanggal_masuk']);
    $now   = new DateTime();
    $diff  = $masuk->diff($now);
    if ($diff->y > 0) {
        $masaKerja = $diff->y . ' Tahun ' . $diff->m . ' Bulan';
    } else {
        $masaKerja = $diff->m . ' Bulan ' . $diff->d . ' Hari';
    }
}

// Greeting berdasarkan jam
$jam = (int)date('H');
if ($jam >= 5 && $jam < 11) $greeting = 'Selamat Pagi';
elseif ($jam >= 11 && $jam < 15) $greeting = 'Selamat Siang';
elseif ($jam >= 15 && $jam < 18) $greeting = 'Selamat Sore';
else $greeting = 'Selamat Malam';

// Tanggal hari ini format Indonesia
$hariList = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];
$bulanList = ['','Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
$hariIni = $hariList[(int)date('w')] . ', ' . date('d') . ' ' . $bulanList[(int)date('n')] . ' ' . date('Y');

require_once '../includes/header.php';
require_once '../includes/sidebar.php';
require_once '../includes/navbar.php';
?>

<!-- Mobile Dashboard Styles -->
<style>
/* ===== MOBILE DASHBOARD ===== */
.m-dashboard {
    max-width: 480px;
    margin: 0 auto;
    padding: 0;
}

/* Hero Profile Card */
.m-hero {
    background: linear-gradient(135deg, #059669 0%, #047857 40%, #065f46 100%);
    border-radius: 0 0 28px 28px;
    padding: 28px 24px 32px;
    margin: -28px -16px 0;
    position: relative;
    overflow: hidden;
}

.m-hero::before {
    content: '';
    position: absolute;
    top: -50px; right: -50px;
    width: 180px; height: 180px;
    background: rgba(255,255,255,0.06);
    border-radius: 50%;
}

.m-hero::after {
    content: '';
    position: absolute;
    bottom: -30px; left: -30px;
    width: 120px; height: 120px;
    background: rgba(255,255,255,0.04);
    border-radius: 50%;
}

.m-hero-top {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 20px;
    position: relative;
    z-index: 1;
}

.m-hero-profile {
    display: flex;
    align-items: center;
    gap: 14px;
}

.m-hero-avatar {
    width: 52px; height: 52px;
    border-radius: 50%;
    background: rgba(255,255,255,0.2);
    backdrop-filter: blur(10px);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
    font-weight: 800;
    color: white;
    border: 2.5px solid rgba(255,255,255,0.35);
    flex-shrink: 0;
}

.m-hero-info h3 {
    color: white;
    font-size: 17px;
    font-weight: 700;
    margin-bottom: 2px;
    letter-spacing: -0.3px;
}

.m-hero-info p {
    color: rgba(255,255,255,0.75);
    font-size: 12.5px;
    font-weight: 500;
}

.m-hero-notif {
    width: 40px; height: 40px;
    border-radius: 12px;
    background: rgba(255,255,255,0.12);
    backdrop-filter: blur(8px);
    border: none;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    color: white;
    transition: all 0.2s;
    text-decoration: none;
}

.m-hero-notif:hover { background: rgba(255,255,255,0.2); }
.m-hero-notif svg { width: 20px; height: 20px; }

/* Live Clock */
.m-clock-wrap {
    text-align: center;
    position: relative;
    z-index: 1;
    margin-bottom: 4px;
}

.m-clock {
    font-size: 48px;
    font-weight: 800;
    color: white;
    letter-spacing: -1px;
    line-height: 1;
    margin-bottom: 6px;
    font-variant-numeric: tabular-nums;
    text-shadow: 0 2px 12px rgba(0,0,0,0.15);
}

.m-clock-sec {
    font-size: 24px;
    opacity: 0.7;
    font-weight: 600;
}

.m-date {
    color: rgba(255,255,255,0.7);
    font-size: 13px;
    font-weight: 500;
}

/* Announcement Card */
.m-announce {
    background: white;
    border-radius: 16px;
    padding: 16px;
    margin: -16px 8px 0;
    position: relative;
    z-index: 2;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08), 0 1px 3px rgba(0,0,0,0.05);
    display: flex;
    align-items: center;
    gap: 14px;
    border-left: 4px solid #059669;
}

.m-announce-icon {
    width: 44px; height: 44px;
    border-radius: 12px;
    background: linear-gradient(135deg, #d1fae5, #a7f3d0);
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    color: #059669;
}

.m-announce-icon svg { width: 22px; height: 22px; }

.m-announce-text h4 {
    font-size: 13.5px;
    font-weight: 700;
    color: #0f172a;
    margin-bottom: 2px;
}

.m-announce-text p {
    font-size: 12px;
    color: #64748b;
    line-height: 1.4;
}

/* Stats Summary */
.m-summary {
    background: white;
    border-radius: 18px;
    padding: 20px;
    margin-top: 20px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.05);
}

.m-summary-title {
    font-size: 14px;
    font-weight: 700;
    color: #0f172a;
    margin-bottom: 16px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.m-summary-title svg { width: 18px; height: 18px; color: #059669; }

.m-summary-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 12px;
}

.m-sum-item {
    text-align: center;
    padding: 14px 8px;
    border-radius: 14px;
    background: #f8fafc;
    border: 1px solid #f1f5f9;
    transition: all 0.2s;
}

.m-sum-item:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.06); }

.m-sum-value {
    font-size: 22px;
    font-weight: 800;
    line-height: 1;
    margin-bottom: 4px;
}

.m-sum-value.green  { color: #059669; }
.m-sum-value.blue   { color: #2563eb; }
.m-sum-value.amber  { color: #d97706; }
.m-sum-value.red    { color: #dc2626; }
.m-sum-value.indigo { color: #4f46e5; }
.m-sum-value.teal   { color: #0d9488; }

.m-sum-label {
    font-size: 11px;
    color: #64748b;
    font-weight: 600;
}

/* Quick Menu Grid */
.m-menu-section {
    margin-top: 20px;
}

.m-menu-title {
    font-size: 14px;
    font-weight: 700;
    color: #0f172a;
    margin-bottom: 14px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.m-menu-title svg { width: 18px; height: 18px; color: #059669; }

.m-menu-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 12px;
}

.m-menu-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
    padding: 16px 8px;
    border-radius: 16px;
    background: white;
    box-shadow: 0 2px 8px rgba(0,0,0,0.04);
    border: 1px solid #f1f5f9;
    text-decoration: none;
    transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
    cursor: pointer;
}

.m-menu-item:hover {
    transform: translateY(-3px);
    box-shadow: 0 6px 20px rgba(0,0,0,0.1);
    border-color: #e2e8f0;
}

.m-menu-item:active {
    transform: scale(0.96);
}

.m-menu-icon {
    width: 46px; height: 46px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
}

.m-menu-icon svg { width: 22px; height: 22px; }

.m-menu-icon.green  { background: linear-gradient(135deg, #d1fae5, #a7f3d0); color: #059669; }
.m-menu-icon.blue   { background: linear-gradient(135deg, #dbeafe, #bfdbfe); color: #2563eb; }
.m-menu-icon.amber  { background: linear-gradient(135deg, #fef3c7, #fde68a); color: #d97706; }
.m-menu-icon.red    { background: linear-gradient(135deg, #fee2e2, #fecaca); color: #dc2626; }
.m-menu-icon.indigo { background: linear-gradient(135deg, #e0e7ff, #c7d2fe); color: #4f46e5; }
.m-menu-icon.teal   { background: linear-gradient(135deg, #ccfbf1, #99f6e4); color: #0d9488; }
.m-menu-icon.pink   { background: linear-gradient(135deg, #fce7f3, #fbcfe8); color: #db2777; }
.m-menu-icon.slate  { background: linear-gradient(135deg, #f1f5f9, #e2e8f0); color: #475569; }

.m-menu-label {
    font-size: 11px;
    font-weight: 600;
    color: #334155;
    text-align: center;
    line-height: 1.3;
}

/* Gaji Card */
.m-gaji-card {
    background: white;
    border-radius: 18px;
    padding: 20px;
    margin-top: 20px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.05);
}

.m-gaji-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 16px;
}

.m-gaji-header h4 {
    font-size: 14px;
    font-weight: 700;
    color: #0f172a;
    display: flex;
    align-items: center;
    gap: 8px;
}

.m-gaji-header h4 svg { width: 18px; height: 18px; color: #059669; }

.m-gaji-period {
    font-size: 12px;
    color: #64748b;
    font-weight: 500;
    background: #f1f5f9;
    padding: 4px 10px;
    border-radius: 20px;
}

.m-gaji-amount {
    text-align: center;
    padding: 20px 0;
    background: linear-gradient(135deg, #f0fdf4, #dcfce7);
    border-radius: 14px;
    margin-bottom: 16px;
    border: 1px solid #bbf7d0;
}

.m-gaji-amount .label {
    font-size: 11.5px;
    color: #059669;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 4px;
}

.m-gaji-amount .value {
    font-size: 28px;
    font-weight: 800;
    color: #047857;
    letter-spacing: -0.5px;
}

.m-gaji-detail {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 10px;
}

.m-gaji-detail-item {
    text-align: center;
    padding: 12px 6px;
    background: #f8fafc;
    border-radius: 10px;
}

.m-gaji-detail-item .dl {
    font-size: 10.5px;
    color: #64748b;
    font-weight: 600;
    text-transform: uppercase;
    margin-bottom: 4px;
}

.m-gaji-detail-item .dv {
    font-size: 13px;
    font-weight: 700;
    color: #0f172a;
}

.m-gaji-detail-item .dv.green { color: #059669; }
.m-gaji-detail-item .dv.red   { color: #dc2626; }

.m-gaji-btn {
    display: flex;
    width: 100%;
    margin-top: 14px;
    padding: 12px;
    background: linear-gradient(135deg, #059669, #047857);
    color: white;
    border: none;
    border-radius: 12px;
    font-size: 13.5px;
    font-weight: 700;
    font-family: 'Inter', sans-serif;
    cursor: pointer;
    text-decoration: none;
    align-items: center;
    justify-content: center;
    gap: 8px;
    transition: all 0.2s;
    box-shadow: 0 3px 12px rgba(5, 150, 105, 0.3);
}

.m-gaji-btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 6px 20px rgba(5, 150, 105, 0.4);
}

.m-gaji-btn svg { width: 16px; height: 16px; }

/* Profile Info Card */
.m-profile-card {
    background: white;
    border-radius: 18px;
    padding: 20px;
    margin-top: 20px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.05);
}

.m-profile-card h4 {
    font-size: 14px;
    font-weight: 700;
    color: #0f172a;
    margin-bottom: 16px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.m-profile-card h4 svg { width: 18px; height: 18px; color: #059669; }

.m-profile-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 11px 0;
    border-bottom: 1px solid #f1f5f9;
}

.m-profile-row:last-child { border-bottom: none; }

.m-profile-row .label {
    font-size: 12.5px;
    color: #64748b;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 6px;
}

.m-profile-row .label svg { width: 14px; height: 14px; opacity: 0.5; }

.m-profile-row .value {
    font-size: 13px;
    font-weight: 600;
    color: #0f172a;
    text-align: right;
    max-width: 60%;
    word-break: break-word;
}

/* Belum Tertaut Warning */
.m-warning {
    background: linear-gradient(135deg, #fef9c3, #fef08a);
    border-radius: 16px;
    padding: 24px 20px;
    margin-top: 20px;
    text-align: center;
    border: 1.5px solid #fde68a;
}

.m-warning-icon {
    width: 56px; height: 56px;
    border-radius: 50%;
    background: rgba(202, 138, 4, 0.12);
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 12px;
    color: #ca8a04;
}

.m-warning-icon svg { width: 28px; height: 28px; }

.m-warning h3 {
    font-size: 16px;
    font-weight: 700;
    color: #854d0e;
    margin-bottom: 6px;
}

.m-warning p {
    font-size: 13px;
    color: #a16207;
    line-height: 1.6;
}

/* Spacing */
.m-spacer { height: 24px; }

/* Mobile Overlay */
.mobile-overlay {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.5);
    z-index: 999;
}

.mobile-overlay.active { display: block; }

/* ===== RESPONSIVE OVERRIDES ===== */

/* Desktop: limit width & center */
@media (min-width: 769px) {
    .m-hero {
        border-radius: 20px;
        margin: 0 0 0 0;
    }
    .m-announce {
        margin: -16px 0 0;
        max-width: 440px;
        margin-left: auto;
        margin-right: auto;
    }
}

/* Phone screens */
@media (max-width: 480px) {
    .m-menu-grid { grid-template-columns: repeat(4, 1fr); gap: 10px; }
    .m-menu-icon { width: 42px; height: 42px; }
    .m-menu-icon svg { width: 20px; height: 20px; }
    .m-clock { font-size: 42px; }
    .m-clock-sec { font-size: 20px; }
    .m-hero { padding: 24px 20px 28px; }
    .m-gaji-amount .value { font-size: 24px; }
}

@media (max-width: 360px) {
    .m-menu-grid { grid-template-columns: repeat(3, 1fr); }
    .m-summary-grid { grid-template-columns: repeat(2, 1fr); }
}
</style>

<div class="m-dashboard">

    <!-- ===== HERO PROFILE + CLOCK ===== -->
    <div class="m-hero">
        <div class="m-hero-top">
            <div class="m-hero-profile">
                <div class="m-hero-avatar"><?= getInitials($userData['nama']) ?></div>
                <div class="m-hero-info">
                    <h3><?= $greeting ?>,</h3>
                    <p><?= htmlspecialchars($userData['nama']) ?> &bull; <?= htmlspecialchars($userData['jabatan'] ?? 'Pegawai') ?></p>
                </div>
            </div>
            <a href="<?= BASE_URL ?>/pegawai/profil.php" class="m-hero-notif" title="Profil Saya">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/></svg>
            </a>
        </div>
        <div class="m-clock-wrap">
            <div class="m-clock" id="liveClock">--:--<span class="m-clock-sec">:--</span></div>
            <div class="m-date"><?= $hariIni ?></div>
        </div>
    </div>

    <!-- ===== ANNOUNCEMENT CARD ===== -->
    <?php if ($userData['karyawan_id']): ?>
    <div class="m-announce">
        <div class="m-announce-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>
        </div>
        <div class="m-announce-text">
            <h4>Status: <?= ucfirst($userData['status']) ?></h4>
            <p>Departemen <?= htmlspecialchars($userData['departemen'] ?? '-') ?> &mdash; <?= htmlspecialchars($userData['jabatan'] ?? '-') ?></p>
        </div>
    </div>
    <?php endif; ?>

    <?php if (!$userData['karyawan_id']): ?>
        <!-- ===== BELUM TERTAUT ===== -->
        <div class="m-warning">
            <div class="m-warning-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            </div>
            <h3>Akun Belum Tertaut</h3>
            <p>Akun login Anda belum dikaitkan dengan database Karyawan oleh Administrator. Silakan hubungi bagian administrasi/HRD.</p>
        </div>

    <?php else: ?>

        <!-- ===== REKAP RINGKASAN ===== -->
        <div class="m-summary">
            <div class="m-summary-title">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="3" width="20" height="14" rx="2"/><path d="M2 10h20"/><path d="M12 17v4M8 21h8"/></svg>
                Rekap Informasi Anda
            </div>
            <div class="m-summary-grid">
                <div class="m-sum-item">
                    <div class="m-sum-value green"><?= $jumlahBulanGaji ?></div>
                    <div class="m-sum-label">Gaji Cair</div>
                </div>
                <div class="m-sum-item">
                    <div class="m-sum-value blue"><?= $masaKerja ? explode(' ', $masaKerja)[0] : '0' ?></div>
                    <div class="m-sum-label"><?= $masaKerja ? explode(' ', $masaKerja)[1] : 'Bulan' ?></div>
                </div>
                <div class="m-sum-item">
                    <div class="m-sum-value <?= $userData['status'] === 'aktif' ? 'green' : 'red' ?>">
                        <?= $userData['status'] === 'aktif' ? '✓' : '✕' ?>
                    </div>
                    <div class="m-sum-label"><?= ucfirst($userData['status']) ?></div>
                </div>
            </div>
        </div>

        <!-- ===== QUICK MENU ===== -->
        <div class="m-menu-section">
            <div class="m-menu-title">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
                Menu Utama
            </div>
            <div class="m-menu-grid">
                <a href="<?= BASE_URL ?>/pegawai/gaji/index.php" class="m-menu-item">
                    <div class="m-menu-icon green">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/></svg>
                    </div>
                    <span class="m-menu-label">Gaji Saya</span>
                </a>
                <a href="<?= BASE_URL ?>/pegawai/profil.php" class="m-menu-item">
                    <div class="m-menu-icon blue">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/></svg>
                    </div>
                    <span class="m-menu-label">Profil</span>
                </a>
                <?php if ($gajiTerakhir): ?>
                <a href="<?= BASE_URL ?>/admin/gaji/slip.php?id=<?= $gajiTerakhir['id'] ?>" target="_blank" class="m-menu-item">
                    <div class="m-menu-icon amber">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                    </div>
                    <span class="m-menu-label">Slip Gaji</span>
                </a>
                <?php else: ?>
                <div class="m-menu-item" style="opacity:0.5; cursor:not-allowed;">
                    <div class="m-menu-icon slate">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                    </div>
                    <span class="m-menu-label">Slip Gaji</span>
                </div>
                <?php endif; ?>
                <a href="<?= BASE_URL ?>/pegawai/profil.php" class="m-menu-item">
                    <div class="m-menu-icon red">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg>
                    </div>
                    <span class="m-menu-label">Password</span>
                </a>
                <a href="<?= BASE_URL ?>/pegawai/dashboard.php" class="m-menu-item">
                    <div class="m-menu-icon indigo">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2z"/><path d="M15 19V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                    </div>
                    <span class="m-menu-label">Dashboard</span>
                </a>
                <a href="<?= BASE_URL ?>/pegawai/gaji/index.php" class="m-menu-item">
                    <div class="m-menu-icon teal">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 1v22M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg>
                    </div>
                    <span class="m-menu-label">Riwayat</span>
                </a>
                <a href="<?= BASE_URL ?>/pegawai/profil.php" class="m-menu-item">
                    <div class="m-menu-icon pink">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                    </div>
                    <span class="m-menu-label">Kontak</span>
                </a>
                <a href="<?= BASE_URL ?>/logout.php" class="m-menu-item" onclick="return confirm('Yakin ingin keluar?')">
                    <div class="m-menu-icon slate">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                    </div>
                    <span class="m-menu-label">Keluar</span>
                </a>
            </div>
        </div>

        <!-- ===== GAJI TERAKHIR ===== -->
        <div class="m-gaji-card">
            <div class="m-gaji-header">
                <h4>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20M7 15h2m4 0h4"/></svg>
                    Gaji Terakhir
                </h4>
                <?php if ($gajiTerakhir): ?>
                <span class="m-gaji-period"><?= date('M Y', strtotime($gajiTerakhir['bulan'] . '-01')) ?></span>
                <?php endif; ?>
            </div>

            <?php if ($gajiTerakhir): ?>
                <div class="m-gaji-amount">
                    <div class="label">Gaji Bersih Diterima</div>
                    <div class="value"><?= formatRupiah($gajiTerakhir['gaji_bersih']) ?></div>
                </div>
                <div class="m-gaji-detail">
                    <div class="m-gaji-detail-item">
                        <div class="dl">Pokok</div>
                        <div class="dv"><?= formatRupiah($gajiTerakhir['gaji_pokok']) ?></div>
                    </div>
                    <div class="m-gaji-detail-item">
                        <div class="dl">Tunjangan</div>
                        <div class="dv green">+<?= formatRupiah($gajiTerakhir['tunjangan']) ?></div>
                    </div>
                    <div class="m-gaji-detail-item">
                        <div class="dl">Potongan</div>
                        <div class="dv red">-<?= formatRupiah($gajiTerakhir['potongan']) ?></div>
                    </div>
                </div>
                <a href="<?= BASE_URL ?>/pegawai/gaji/index.php" class="m-gaji-btn">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    Lihat Seluruh Riwayat Gaji
                </a>
            <?php else: ?>
                <div style="text-align:center; padding:24px 0;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="width:48px;height:48px;color:#cbd5e1;margin-bottom:10px;">
                        <circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/>
                    </svg>
                    <div style="font-size:14px;font-weight:600;color:#64748b;">Belum ada riwayat gaji</div>
                    <div style="font-size:12.5px;color:#94a3b8;margin-top:4px;">Pembayaran gaji Anda belum terdaftar di sistem</div>
                </div>
            <?php endif; ?>
        </div>

        <!-- ===== DETAIL PROFIL ===== -->
        <div class="m-profile-card">
            <h4>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/></svg>
                Detail Profil Anda
            </h4>
            <div class="m-profile-row">
                <span class="label">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="M2 8h20"/></svg>
                    NIK
                </span>
                <span class="value" style="font-family:monospace;"><?= htmlspecialchars($userData['nik'] ?? '-') ?></span>
            </div>
            <div class="m-profile-row">
                <span class="label">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                    Nama
                </span>
                <span class="value"><?= htmlspecialchars($userData['nama']) ?></span>
            </div>
            <div class="m-profile-row">
                <span class="label">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>
                    Jabatan
                </span>
                <span class="value"><?= htmlspecialchars($userData['jabatan'] ?? '-') ?></span>
            </div>
            <div class="m-profile-row">
                <span class="label">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/></svg>
                    Departemen
                </span>
                <span class="value"><?= htmlspecialchars($userData['departemen'] ?? '-') ?></span>
            </div>
            <div class="m-profile-row">
                <span class="label">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                    Masuk
                </span>
                <span class="value"><?= $userData['tanggal_masuk'] ? formatTanggal($userData['tanggal_masuk']) : '-' ?></span>
            </div>
            <div class="m-profile-row">
                <span class="label">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 8v4l3 3"/></svg>
                    Masa Kerja
                </span>
                <span class="value"><?= $masaKerja ?: '-' ?></span>
            </div>
            <div class="m-profile-row">
                <span class="label">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/></svg>
                    Gaji Pokok
                </span>
                <span class="value" style="color:#059669; font-weight:700;"><?= formatRupiah($userData['gaji_pokok']) ?></span>
            </div>
            <div class="m-profile-row">
                <span class="label">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07 19.5 19.5 0 01-6-6 19.79 19.79 0 01-3.07-8.67A2 2 0 014.11 2h3a2 2 0 012 1.72"/></svg>
                    Telepon
                </span>
                <span class="value"><?= htmlspecialchars($userData['no_telp'] ?? '-') ?></span>
            </div>
        </div>

    <?php endif; ?>

    <div class="m-spacer"></div>
</div>

<!-- Live Clock Script -->
<script>
(function() {
    const clockEl = document.getElementById('liveClock');
    if (!clockEl) return;

    function updateClock() {
        const now = new Date();
        const h = String(now.getHours()).padStart(2, '0');
        const m = String(now.getMinutes()).padStart(2, '0');
        const s = String(now.getSeconds()).padStart(2, '0');
        clockEl.innerHTML = h + ':' + m + '<span class="m-clock-sec">:' + s + '</span>';
    }

    updateClock();
    setInterval(updateClock, 1000);
})();
</script>

<?php require_once '../includes/footer.php'; ?>
