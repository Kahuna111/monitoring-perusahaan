<?php
// includes/sidebar.php
// Requires session to be started and user data available
$userName  = $_SESSION['user_nama']  ?? 'Pengguna';
$userRole  = $_SESSION['user_role']  ?? 'pegawai';
$activePage = $activePage ?? '';
$initials  = getInitials($userName);
$isAdmin   = ($userRole === 'admin');
?>

<div class="app-wrapper">

<!-- ==============================
     SIDEBAR
     ============================== -->
<nav class="sidebar" id="sidebar">

    <!-- Logo -->
    <a href="<?= BASE_URL ?>/<?= $isAdmin ? 'admin' : 'pegawai' ?>/dashboard.php" class="sidebar-logo">
        <div class="sidebar-logo-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
            </svg>
        </div>
        <div class="sidebar-logo-text">
            <span><?= APP_NAME ?></span>
            <span><?= APP_COMPANY ?></span>
        </div>
    </a>

    <!-- Navigation -->
    <div class="sidebar-nav">

        <!-- Umum -->
        <div class="nav-section-label">Umum</div>

        <a href="<?= BASE_URL ?>/<?= $isAdmin ? 'admin' : 'pegawai' ?>/dashboard.php"
           class="nav-item <?= $activePage === 'dashboard' ? 'active' : '' ?>"
           data-tooltip="Dashboard">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/>
                <rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/>
            </svg>
            <span>Dashboard</span>
        </a>

        <?php if ($isAdmin): ?>

        <!-- Admin Menu -->
        <div class="nav-section-label">Manajemen</div>

        <a href="<?= BASE_URL ?>/admin/karyawan/index.php"
           class="nav-item <?= $activePage === 'karyawan' ? 'active' : '' ?>"
           data-tooltip="Karyawan">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/>
                <path d="M23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"/>
            </svg>
            <span>Karyawan</span>
        </a>

        <a href="<?= BASE_URL ?>/admin/gaji/index.php"
           class="nav-item <?= $activePage === 'gaji' ? 'active' : '' ?>"
           data-tooltip="Penggajian">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <rect x="2" y="5" width="20" height="14" rx="2"/>
                <path d="M2 10h20M7 15h2m4 0h4"/>
            </svg>
            <span>Penggajian</span>
        </a>

        <div class="nav-section-label">Keuangan</div>

        <a href="<?= BASE_URL ?>/admin/pemasukan/index.php"
           class="nav-item <?= $activePage === 'pemasukan' ? 'active' : '' ?>"
           data-tooltip="Pemasukan">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M12 5v14M5 12l7-7 7 7"/>
            </svg>
            <span>Uang Masuk</span>
        </a>

        <a href="<?= BASE_URL ?>/admin/pengeluaran/index.php"
           class="nav-item <?= $activePage === 'pengeluaran' ? 'active' : '' ?>"
           data-tooltip="Pengeluaran">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M12 19V5M19 12l-7 7-7-7"/>
            </svg>
            <span>Uang Keluar</span>
        </a>

        <div class="nav-section-label">Laporan</div>

        <a href="<?= BASE_URL ?>/admin/laporan/keuangan.php"
           class="nav-item <?= $activePage === 'laporan' ? 'active' : '' ?>"
           data-tooltip="Laporan">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            <span>Laporan</span>
        </a>

        <div class="nav-section-label">Pengaturan</div>

        <a href="<?= BASE_URL ?>/admin/akun/index.php"
           class="nav-item <?= $activePage === 'akun' ? 'active' : '' ?>"
           data-tooltip="Akun User">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="8" r="4"/>
                <path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/>
            </svg>
            <span>Akun User</span>
        </a>

        <?php else: ?>

        <!-- Pegawai Menu -->
        <div class="nav-section-label">Pribadi</div>

        <a href="<?= BASE_URL ?>/pegawai/gaji/index.php"
           class="nav-item <?= $activePage === 'gaji' ? 'active' : '' ?>"
           data-tooltip="Gaji Saya">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <rect x="2" y="5" width="20" height="14" rx="2"/>
                <path d="M2 10h20M7 15h2m4 0h4"/>
            </svg>
            <span>Gaji Saya</span>
        </a>

        <a href="<?= BASE_URL ?>/pegawai/profil.php"
           class="nav-item <?= $activePage === 'profil' ? 'active' : '' ?>"
           data-tooltip="Profil Saya">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="8" r="4"/>
                <path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/>
            </svg>
            <span>Profil Saya</span>
        </a>

        <?php endif; ?>

    </div><!-- /sidebar-nav -->

    <!-- Footer user -->
    <div class="sidebar-footer">
        <div class="dropdown">
            <div class="sidebar-user" data-dropdown-trigger>
                <div class="avatar"><?= $initials ?></div>
                <div class="sidebar-user-info">
                    <div class="user-name"><?= htmlspecialchars($userName) ?></div>
                    <div class="user-role"><?= $isAdmin ? 'Administrator' : 'Pegawai' ?></div>
                </div>
            </div>
            <div class="dropdown-menu" style="bottom: calc(100% + 8px); top: auto;">
                <a href="<?= BASE_URL ?>/<?= $isAdmin ? 'admin' : 'pegawai' ?>/<?= $isAdmin ? 'akun/profil.php' : 'profil.php' ?>" class="dropdown-item">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/></svg>
                    Profil Saya
                </a>
                <div class="dropdown-divider"></div>
                <a href="<?= BASE_URL ?>/logout.php" class="dropdown-item danger">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                    Keluar
                </a>
            </div>
        </div>
    </div>

</nav>
<!-- /SIDEBAR -->

<!-- Main Content Wrapper -->
<div class="main-content" id="mainContent">
