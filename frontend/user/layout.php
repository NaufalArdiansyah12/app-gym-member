<?php
// frontend/user/layout.php
$pageDate = date('d M Y');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? 'User') ?> | USER GYM</title>
    <link rel="stylesheet" href="../common.css?v=<?= time(); ?>">
    <link rel="stylesheet" href="style.css?v=<?= time(); ?>">
</head>
<body>
    <!-- SVG Icon Sprite identical to Admin Layout + Mobile Nav Icons -->
    <svg class="icon-sprite" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false">
        <defs>
            <symbol id="icon-dashboard" viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7" rx="2"/><rect x="14" y="3" width="7" height="7" rx="2"/><rect x="3" y="14" width="7" height="7" rx="2"/><rect x="14" y="14" width="7" height="7" rx="2"/></symbol>
            <symbol id="icon-members" viewBox="0 0 24 24"><circle cx="9" cy="8" r="3"/><path d="M3 21v-2a6 6 0 0 1 12 0v2M16 5a3 3 0 0 1 0 6m2 3a5 5 0 0 1 3 5v2"/></symbol>
            <symbol id="icon-package" viewBox="0 0 24 24"><path d="m12 3 9 5v8l-9 5-9-5V8l9-5Zm-9 5 9 5 9-5M12 13v8M7.5 5.5l9 5"/></symbol>
            <symbol id="icon-receipt" viewBox="0 0 24 24"><path d="M5 3v18l3-2 4 2 4-2 3 2V3l-3 2-4-2-4 2-3-2ZM9 9h6m-6 4h6m-6 4h3"/></symbol>
            <symbol id="icon-check-in" viewBox="0 0 24 24"><path d="M14 3h6v18h-6M3 12h12m-5-5 5 5-5 5"/></symbol>
            <symbol id="icon-chart" viewBox="0 0 24 24"><path d="M3 3v18h18M7 16v-4m5 4V8m5 8V5"/></symbol>
            <symbol id="icon-settings" viewBox="0 0 24 24"><path d="M4 7h16M4 17h16"/><circle cx="9" cy="7" r="3" fill="currentColor" stroke="none"/><circle cx="15" cy="17" r="3" fill="currentColor" stroke="none"/></symbol>
            <symbol id="icon-logout" viewBox="0 0 24 24"><path d="M9 3H4v18h5m2-9h10m-5-5 5 5-5 5"/></symbol>
            <symbol id="icon-bell" viewBox="0 0 24 24"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4"/></symbol>
            <symbol id="icon-check" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="m8 12 3 3 5-6"/></symbol>
            <symbol id="icon-warning" viewBox="0 0 24 24"><path d="m12 3 10 18H2L12 3Zm0 6v5m0 3v.1"/></symbol>
            <symbol id="icon-chevron" viewBox="0 0 24 24"><path d="m9 5 7 7-7 7"/></symbol>
            <symbol id="icon-close" viewBox="0 0 24 24"><path d="M18 6 6 18M6 6l12 12"/></symbol>
            <symbol id="icon-download" viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M7 10l5 5 5-5M12 15V3" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></symbol>
            <symbol id="icon-gym" viewBox="0 0 24 24"><path d="M7 12h10M3 9v6m4-9v12M17 6v12m4-9v6M3 12h4m10 0h4"/></symbol>
            <symbol id="icon-menu" viewBox="0 0 24 24"><line x1="3" y1="12" x2="21" y2="12"></line><line x1="3" y1="6" x2="21" y2="6"></line><line x1="3" y1="18" x2="21" y2="18"></line></symbol>
            <symbol id="icon-nav-home" viewBox="0 0 24 24"><path d="M3 10.5 12 3l9 7.5V20a1.5 1.5 0 0 1-1.5 1.5H15v-5a3 3 0 0 0-6 0v5H4.5A1.5 1.5 0 0 1 3 20v-9.5Z" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></symbol>
            <symbol id="icon-nav-dumbbell" viewBox="0 0 24 24"><path d="M6 8v8M18 8v8M3 10v4M21 10v4M6 12h12" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></symbol>
            <symbol id="icon-nav-fork-knife" viewBox="0 0 24 24"><path d="M18 3v6a3 3 0 0 1-3 3v9M5 3v5a2 2 0 0 0 2 2h0a2 2 0 0 0 2-2V3M7 10v11" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></symbol>
            <symbol id="icon-nav-chart" viewBox="0 0 24 24"><path d="M4 19h16M7 19v-4M12 19v-7M17 19V8M7 14l5-5 5-2" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/><path d="m15 7 3-1-1 3" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></symbol>
            <symbol id="icon-nav-user" viewBox="0 0 24 24"><circle cx="12" cy="7.5" r="3.5" fill="none" stroke="currentColor" stroke-width="2.2"/><rect x="5.5" y="15" width="13" height="5.5" rx="2.75" fill="none" stroke="currentColor" stroke-width="2.2"/></symbol>
        </defs>
    </svg>

    <!-- Mobile Top Navigation Bar -->
    <div class="mobile-nav-bar">
        <button id="btnMobileMenu" class="mobile-menu-btn" type="button" aria-label="Buka Menu">
            <svg class="icon"><use href="#icon-menu"/></svg>
        </button>
        <div class="mobile-brand">
            <svg class="icon brand-mark" aria-hidden="true" focusable="false"><use href="#icon-gym"/></svg>
            <span>USER <span class="accent">GYM</span></span>
        </div>
        <div class="mobile-user-avatar" id="mobileUserAvatar" title="Lihat Profil">M</div>
    </div>

    <!-- Backdrop Overlay for Mobile Sidebar Drawer -->
    <div id="sidebarBackdrop" class="sidebar-backdrop"></div>

    <div class="app-shell">
        <!-- SIDEBAR -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header-row">
                <a class="brand" href="../dashboard/" id="brandLogoBtn">
                    <svg class="icon brand-mark" aria-hidden="true" focusable="false"><use href="#icon-gym"/></svg>
                    <span>USER <span class="accent">GYM</span></span>
                </a>
                <button id="btnSidebarClose" class="sidebar-close-btn" type="button" aria-label="Tutup Menu">
                    <svg class="icon"><use href="#icon-close"/></svg>
                </button>
            </div>

            <div class="sidebar-nav-wrap">
                <p class="menu-label">MENU UTAMA</p>
                <nav class="nav-list" aria-label="Menu utama">
                    <a class="nav-item <?= ($page ?? '') === 'dashboard' ? 'active' : '' ?>" href="../dashboard/">
                        <svg class="icon" aria-hidden="true" focusable="false"><use href="#icon-dashboard"/></svg>Dashboard
                    </a>
                    <a class="nav-item <?= ($page ?? '') === 'kehadiran' ? 'active' : '' ?>" href="../kehadiran/">
                        <svg class="icon" aria-hidden="true" focusable="false"><use href="#icon-check-in"/></svg>Riwayat Kehadiran
                    </a>
                    <a class="nav-item <?= ($page ?? '') === 'membership' ? 'active' : '' ?>" href="../membership/">
                        <svg class="icon" aria-hidden="true" focusable="false"><use href="#icon-package"/></svg>Halaman Member
                    </a>
                </nav>
            </div>
            <button id="btnLogout" class="logout" type="button">
                <svg class="icon" aria-hidden="true" focusable="false"><use href="#icon-logout"/></svg>Keluar
            </button>
        </aside>

        <!-- MAIN WRAPPER -->
        <div class="main-wrapper">
            <!-- TOPBAR NAVBAR -->
            <header class="navbar topbar">
                <div class="navbar-title-wrap">
                    <p class="eyebrow" id="pageEyebrow"><?= $pageEyebrow ?? ('MEMBER AREA · ' . strtoupper(htmlspecialchars($pageDate))) ?></p>
                    <h1 id="pageHeading"><?= $pageHeading ?? 'Selamat pagi, <span id="welcomeName">Gym Member</span>' ?></h1>
                    <p class="muted" id="pageSubtitle"><?= htmlspecialchars($pageSubtitle ?? 'Pantau status membership dan absensi gym Anda.') ?></p>
                </div>
                <div class="top-actions navbar-actions">
                    <button class="icon-button notification" type="button" aria-label="Notifikasi">
                        <svg class="icon" aria-hidden="true" focusable="false"><use href="#icon-bell"/></svg>
                        <i aria-hidden="true"></i>
                    </button>
                    <div class="profile-chip">
                        <div class="avatar" id="userAvatar">M</div>
                        <span id="navUserName">Gym Member</span>
                    </div>
                </div>
            </header>

            <!-- MAIN CONTENT AREA -->
            <main class="main-content">
                <?= $content ?? '' ?>
            </main>
        </div>
    </div>

    <!-- Mobile Bottom Navigation Bar -->
    <nav class="mobile-bottom-nav" aria-label="Navigasi Menu Mobile">
        <div class="bottom-nav-inner">
            <a class="nav-circle-btn <?= ($page ?? '') === 'dashboard' ? 'active' : '' ?>" id="btnBottomHome" href="../dashboard/" aria-label="Dashboard Utama">
                <svg class="nav-icon" aria-hidden="true" focusable="false"><use href="#icon-nav-home"/></svg>
            </a>
            <a class="nav-circle-btn <?= ($page ?? '') === 'kehadiran' ? 'active' : '' ?>" id="btnBottomAttendance" href="../kehadiran/" aria-label="Riwayat Kehadiran">
                <svg class="nav-icon" aria-hidden="true" focusable="false"><use href="#icon-check-in"/></svg>
            </a>
            <a class="nav-circle-btn <?= ($page ?? '') === 'membership' ? 'active' : '' ?>" id="btnBottomMembership" href="../membership/" aria-label="Status & Sisa Waktu Membership">
                <svg class="nav-icon" aria-hidden="true" focusable="false"><use href="#icon-package"/></svg>
            </a>
        </div>
    </nav>

    <!-- Mobile User Profile Bottom Sheet Modal -->
    <div id="mobileProfileModal" class="profile-modal-backdrop" aria-hidden="true">
        <div class="profile-modal-card">
            <div class="profile-modal-header">
                <div class="profile-modal-avatar" id="modalUserAvatar">M</div>
                <div class="profile-modal-info">
                    <h3 id="modalUserName">Gym Member</h3>
                    <p id="modalUserCode">GYM-0102</p>
                    <span class="status green-bg" style="font-size: 11px; padding: 2px 8px; margin-top: 4px; display: inline-block;">Member Aktif</span>
                </div>
                <button id="btnCloseProfileModal" class="profile-modal-close" type="button" aria-label="Tutup">
                    <svg class="icon"><use href="#icon-close"/></svg>
                </button>
            </div>
            <div class="profile-modal-body">
                <div class="qr-canvas-holder" id="modalUserQr" style="margin: 8px auto 14px; display: flex; justify-content: center;"></div>
                <div class="profile-meta-row">
                    <span class="muted">Paket Aktif</span>
                    <strong id="modalPackageName">Paket Platinum 2 Bulan</strong>
                </div>
                <div class="profile-meta-row">
                    <span class="muted">Sisa Durasi</span>
                    <strong style="color: #4ade80;"><span id="modalDaysRemaining">36</span> Hari Lagi</strong>
                </div>
                <div class="profile-meta-row">
                    <span class="muted">Akses Gym</span>
                    <span>Semua Fasilitas & Area</span>
                </div>
            </div>
            <div class="profile-modal-footer">
                <button id="btnModalLogout" class="btn-modal-logout" type="button">
                    <svg class="icon"><use href="#icon-logout"/></svg>
                    <span>Keluar dari Akun</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Toast Notification -->
    <div id="toast" class="toast" role="status"></div>

    <script src="../../assets/js/qrcode.min.js"></script>
    <script src="script.js?v=<?= time(); ?>"></script>
</body>
</html>
