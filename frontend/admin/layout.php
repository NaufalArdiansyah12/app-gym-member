<?php
// frontend/admin/layout.php
$navigation = [
    'dashboard'  => ['Dashboard', 'dashboard'],
    'members'    => ['Member', 'members'],
    'packages'   => ['Paket Langganan', 'package'],
    'cashier'    => ['Kasir & Transaksi', 'receipt'],
    'attendance' => ['Presensi', 'check-in'],
    'reports'    => ['Laporan', 'chart'],
];
$pageDate = date('d M Y');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? 'Admin') ?> | KelolaGym</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
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
            <symbol id="icon-gym" viewBox="0 0 24 24"><path d="M7 12h10M3 9v6m4-9v12M17 6v12m4-9v6M3 12h4m10 0h4"/></symbol>
            <symbol id="icon-plus" viewBox="0 0 24 24"><path d="M12 5v14m-7-7h14"/></symbol>
            <symbol id="icon-sparkle" viewBox="0 0 24 24"><path d="m12 3 1.9 5.8a2 2 0 0 0 1.3 1.3L21 12l-5.8 1.9a2 2 0 0 0-1.3 1.3L12 21l-1.9-5.8a2 2 0 0 0-1.3-1.3L3 12l5.8-1.9a2 2 0 0 0 1.3-1.3L12 3z"/></symbol>
            <symbol id="icon-refresh" viewBox="0 0 24 24"><path d="M3 12a9 9 0 0 1 15.5-6.4L21 8m0-5v5h-5m5 4a9 9 0 0 1-15.5 6.4L3 16m0 5v-5h5"/></symbol>
            <symbol id="icon-printer" viewBox="0 0 24 24"><path d="M6 9V2h12v7M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2M6 14h12v8H6z"/></symbol>
            <symbol id="icon-download" viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4m-14-5 5 5 5-5m-5 5V3"/></symbol>
            <symbol id="icon-info" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4m0-4h.01"/></symbol>
            <symbol id="icon-eye" viewBox="0 0 24 24"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></symbol>
            <symbol id="icon-edit" viewBox="0 0 24 24"><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/></symbol>
            <symbol id="icon-trash" viewBox="0 0 24 24"><path d="M3 6h18m-2 0v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6m3 0V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/></symbol>
        </defs>
    </svg>
    <div class="app-shell">
        <aside class="sidebar">
            <a class="brand" href="../dashboard/">
                <svg class="icon brand-mark" aria-hidden="true" focusable="false"><use href="#icon-gym"/></svg>
                <span>Kelola<span class="accent">Gym</span></span>
            </a>
            <div class="sidebar-nav-wrap">
                <p class="menu-label">MENU UTAMA</p>
                <nav class="nav-list" aria-label="Menu utama">
                    <?php foreach ($navigation as $key => [$label, $icon]): ?>
                        <a class="nav-item <?= ($page ?? '') === $key ? 'active' : '' ?>" <?= ($page ?? '') === $key ? 'aria-current="page"' : '' ?> href="../<?= htmlspecialchars($key, ENT_QUOTES, 'UTF-8') ?>/">
                            <svg class="icon" aria-hidden="true" focusable="false"><use href="#icon-<?= htmlspecialchars($icon, ENT_QUOTES, 'UTF-8') ?>"/></svg><?= htmlspecialchars($label) ?>
                        </a>
                    <?php endforeach; ?>
                </nav>
                <p class="menu-label">PENGATURAN</p>
                <nav class="nav-list" aria-label="Pengaturan">
                    <a class="nav-item <?= ($page ?? '') === 'settings' ? 'active' : '' ?>" <?= ($page ?? '') === 'settings' ? 'aria-current="page"' : '' ?> href="../settings/">
                        <svg class="icon" aria-hidden="true" focusable="false"><use href="#icon-settings"/></svg>Pengaturan
                    </a>
                </nav>
            </div>
            <button id="btnLogout" class="logout" type="button"><svg class="icon" aria-hidden="true" focusable="false"><use href="#icon-logout"/></svg>Keluar</button>
        </aside>

        <div class="main-wrapper">
            <header class="navbar topbar">
                <div class="navbar-title-wrap">
                    <p class="eyebrow"><?= strtoupper(htmlspecialchars($title ?? '')) ?> · <?= htmlspecialchars($pageDate) ?></p>
                    <h1>
                        <?php if (($page ?? '') === 'dashboard'): ?>
                            Selamat pagi, <span id="userName">Admin</span>
                        <?php else: ?>
                            <?= htmlspecialchars($title ?? '') ?>
                        <?php endif; ?>
                    </h1>
                    <p class="muted"><?= htmlspecialchars($pageSubtitle ?? (($page ?? '') === 'dashboard' ? 'Pantau operasional gym hari ini.' : 'Kelola informasi operasional gym.')) ?></p>
                </div>
                <div class="top-actions navbar-actions">
                    <button class="icon-button notification" type="button" aria-label="Notifikasi"><svg class="icon" aria-hidden="true" focusable="false"><use href="#icon-bell"/></svg><i aria-hidden="true"></i></button>
                    <div class="profile-chip">
                        <div class="avatar" id="userAvatar">A</div>
                        <span id="userProfileName">Admin Gym</span>
                    </div>
                </div>
            </header>

            <main class="main-content">
                <?= $content ?? '' ?>
            </main>
        </div>
    </div>

    <div id="toast" class="toast" role="status"></div>
    <script src="../../assets/js/qrcode.min.js"></script>
    <script src="script.js"></script>
</body>
</html>
