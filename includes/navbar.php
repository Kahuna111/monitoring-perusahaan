<?php
// includes/navbar.php
// Requires: $pageTitle, $breadcrumb (opsional)
$breadcrumb = $breadcrumb ?? '';
$flash = getFlash();
?>

<!-- ==============================
     TOP NAVBAR
     ============================== -->
<header class="topbar">
    <div class="topbar-left">
        <button class="sidebar-toggle" id="sidebarToggle" title="Toggle Sidebar">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="3" y1="6" x2="21" y2="6"/>
                <line x1="3" y1="12" x2="21" y2="12"/>
                <line x1="3" y1="18" x2="21" y2="18"/>
            </svg>
        </button>
        <div class="page-title-bar">
            <h1><?= htmlspecialchars($pageTitle ?? 'Dashboard') ?></h1>
            <?php if ($breadcrumb): ?>
                <div class="breadcrumb"><?= $breadcrumb ?></div>
            <?php endif; ?>
        </div>
    </div>

    <div class="topbar-right">
        <!-- Notification Button -->
        <button class="topbar-btn" title="Notifikasi" id="notifBtn">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M18 8A6 6 0 006 8c0 7-3 9-3 9h18s-3-2-3-9M13.73 21a2 2 0 01-3.46 0"/>
            </svg>
        </button>

        <!-- User Dropdown -->
        <div class="dropdown">
            <div class="topbar-avatar" data-dropdown-trigger title="Akun Saya">
                <?= getInitials($_SESSION['user_nama'] ?? 'U') ?>
            </div>
            <div class="dropdown-menu">
                <div style="padding: 10px 12px 8px;">
                    <div style="font-size:13px;font-weight:700;color:#0f172a;"><?= htmlspecialchars($_SESSION['user_nama'] ?? '') ?></div>
                    <div style="font-size:12px;color:#64748b;"><?= htmlspecialchars($_SESSION['user_email'] ?? '') ?></div>
                </div>
                <div class="dropdown-divider"></div>
                <?php if (isAdmin()): ?>
                <a href="<?= BASE_URL ?>/admin/akun/profil.php" class="dropdown-item">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/></svg>
                    Profil Saya
                </a>
                <?php else: ?>
                <a href="<?= BASE_URL ?>/pegawai/profil.php" class="dropdown-item">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/></svg>
                    Profil Saya
                </a>
                <?php endif; ?>
                <div class="dropdown-divider"></div>
                <a href="<?= BASE_URL ?>/logout.php" class="dropdown-item danger">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                    Keluar
                </a>
            </div>
        </div>
    </div>
</header>

<!-- Page Content Area -->
<main class="page-content">

<?php if ($flash): ?>
    <div class="alert alert-<?= $flash['type'] ?>" id="flashAlert">
        <?php if ($flash['type'] === 'success'): ?>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <?php elseif ($flash['type'] === 'danger'): ?>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        <?php else: ?>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <?php endif; ?>
        <?= htmlspecialchars($flash['message']) ?>
        <button class="alert-close">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
        </button>
    </div>
<?php endif; ?>