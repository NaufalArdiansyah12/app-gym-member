<?php
// frontend/user/dashboard/index.php
$pageDate = date('d M Y');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Member | USER GYM</title>
    <link rel="stylesheet" href="style.css?v=<?= time(); ?>">
</head>
<body>
    <!-- SVG Icon Sprite identical to Admin Layout -->
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
            <symbol id="icon-menu" viewBox="0 0 24 24"><line x1="3" y1="12" x2="21" y2="12"></line><line x1="3" y1="6" x2="21" y2="6"></line><line x1="3" y1="18" x2="21" y2="18"></line></symbol>
            <!-- 5 Mobile Bottom Nav Icons (Matches Screenshot) -->
            <symbol id="icon-nav-home" viewBox="0 0 24 24"><path d="M3 10.5 12 3l9 7.5V20a1.5 1.5 0 0 1-1.5 1.5H15v-5a3 3 0 0 0-6 0v5H4.5A1.5 1.5 0 0 1 3 20v-9.5Z" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></symbol>
            <symbol id="icon-nav-dumbbell" viewBox="0 0 24 24"><path d="M6 8v8M18 8v8M3 10v4M21 10v4M6 12h12" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></symbol>
            <symbol id="icon-nav-fork-knife" viewBox="0 0 24 24"><path d="M18 3v6a3 3 0 0 1-3 3v9M5 3v5a2 2 0 0 0 2 2h0a2 2 0 0 0 2-2V3M7 10v11" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></symbol>
            <symbol id="icon-nav-chart" viewBox="0 0 24 24"><path d="M4 19h16M7 19v-4M12 19v-7M17 19V8M7 14l5-5 5-2" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/><path d="m15 7 3-1-1 3" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></symbol>
            <symbol id="icon-nav-user" viewBox="0 0 24 24"><circle cx="12" cy="7.5" r="3.5" fill="none" stroke="currentColor" stroke-width="2.2"/><rect x="5.5" y="15" width="13" height="5.5" rx="2.75" fill="none" stroke="currentColor" stroke-width="2.2"/></symbol>
        </defs>
    </svg>

    <!-- Confetti Canvas for Celebrations -->
    <canvas id="confettiCanvas" class="confetti-canvas"></canvas>

    <!-- Mobile Top Navigation Bar (Clean: Brand on left, User Avatar on right) -->
    <div class="mobile-nav-bar">
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
                <a class="brand" href="javascript:void(0)" id="brandLogoBtn">
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
                    <a class="nav-item active" href="#dashboard" data-view="view-dashboard">
                        <svg class="icon" aria-hidden="true" focusable="false"><use href="#icon-dashboard"/></svg>Dashboard
                    </a>
                    <a class="nav-item" href="#attendance" data-view="view-attendance">
                        <svg class="icon" aria-hidden="true" focusable="false"><use href="#icon-check-in"/></svg>Riwayat Kehadiran
                    </a>
                    <a class="nav-item" href="#membership" data-view="view-membership">
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
                    <p class="eyebrow" id="pageEyebrow">MEMBER AREA · <?= strtoupper(htmlspecialchars($pageDate)) ?></p>
                    <h1 id="pageHeading">
                        Selamat pagi, <span id="welcomeName">Gym Member</span>
                    </h1>
                    <p class="muted" id="pageSubtitle">Pantau status membership dan absensi gym Anda.</p>
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
                <!-- ============================================== -->
                <!-- 1. VIEW: DASHBOARD                             -->
                <!-- ============================================== -->
                <div id="view-dashboard" class="view-panel active">
                    <!-- Stats Grid: 4 Cards -->
                    <section class="stats-grid" aria-label="Ringkasan member">
                        <article class="stat-card">
                            <div class="stat-icon orange" aria-hidden="true">
                                <svg class="icon" focusable="false"><use href="#icon-package"/></svg>
                            </div>
                            <p>Sisa Waktu Membership</p>
                            <strong><span id="dashDaysRemaining">36</span> Hari</strong>
                            <small class="positive">↗ <span id="dashPackageName">Paket Platinum 2 Bulan</span></small>
                        </article>

                        <article class="stat-card">
                            <div class="stat-icon green" aria-hidden="true">
                                <svg class="icon" focusable="false"><use href="#icon-check"/></svg>
                            </div>
                            <p>Presensi Hari Ini</p>
                            <strong id="dashTodayCheckinStatus">Belum Hadir</strong>
                            <small class="positive" id="dashTodayCheckinTime">Belum ada catatan</small>
                        </article>

                        <article class="stat-card">
                            <div class="stat-icon yellow" aria-hidden="true">
                                <svg class="icon" focusable="false"><use href="#icon-warning"/></svg>
                            </div>
                            <p>Masa Berlaku Paket</p>
                            <strong id="dashMembershipExpiryShort">18 Nov 2026</strong>
                            <small class="warning">Masa aktif berjalan</small>
                        </article>

                        <article class="stat-card">
                            <div class="stat-icon orange" aria-hidden="true">
                                <svg class="icon" focusable="false"><use href="#icon-check-in"/></svg>
                            </div>
                            <p>Kehadiran Bulan Ini</p>
                            <strong><span id="dashMonthlyVisits">0</span> Sesi</strong>
                            <small class="positive">↗ Disiplin berolahraga</small>
                        </article>
                    </section>

                    <!-- Content Grid: Check-in Form (Left) & Check-in Hari Ini (Right) -->
                    <section class="content-grid">
                        <article class="panel">
                            <div class="panel-heading">
                                <div>
                                    <h2>Check-in Kehadiran Gym</h2>
                                    <p class="muted">Catat kehadiran Anda saat tiba di lokasi gym hari ini.</p>
                                </div>
                                <span class="status green-bg" id="badgeAttendanceStatus">Aktif</span>
                            </div>

                            <!-- Notification if already checked in -->
                            <div id="alreadyCheckedInNotice" class="alert-box green-alert hidden">
                                <div class="alert-icon-check">✔</div>
                                <div>
                                    <b id="checkedInBannerTitle">Kehadiran Terkonfirmasi</b>
                                    <p id="checkedInBannerSubtitle">Anda sudah melakukan check-in hari ini.</p>
                                </div>
                            </div>

                            <!-- Interactive Check-in Form -->
                            <form id="formCheckInDashboard" class="user-checkin-form">
                                <div class="form-row-custom">
                                    <div class="form-group-custom">
                                        <label for="dashSessionType">Sesi / Zona Latihan</label>
                                        <select id="dashSessionType" required>
                                            <option value="Gym Umum / Fitness Regular">Gym Umum / Fitness Regular</option>
                                            <option value="Free Weights & Barbell Area">Free Weights & Barbell Area</option>
                                            <option value="Cardio & Treadmill Zone">Cardio & Treadmill Zone</option>
                                            <option value="Kelas Yoga / Pilates">Kelas Yoga / Pilates</option>
                                            <option value="Kelas Zumba / Aerobik">Kelas Zumba / Aerobik</option>
                                        </select>
                                    </div>
                                    <div class="form-group-custom">
                                        <label for="dashCheckinNotes">Catatan / Target (Opsional)</label>
                                        <input type="text" id="dashCheckinNotes" placeholder="Contoh: Chest Day & Cardio">
                                    </div>
                                </div>
                                <button type="submit" id="btnSubmitDashboardCheckin" class="btn-checkin-primary">
                                    <svg class="icon" aria-hidden="true" focusable="false"><use href="#icon-check"/></svg>
                                    <span id="btnSubmitText">Konfirmasi Check-in Masuk</span>
                                </button>
                            </form>
                        </article>

                        <article class="panel attendance-panel">
                            <div class="panel-heading">
                                <div>
                                    <h2>Check-in Hari Ini</h2>
                                    <p class="muted">Kedatangan terbaru</p>
                                </div>
                                <a href="#attendance" class="view-link" data-view="view-attendance">Lihat semua</a>
                            </div>
                            <div class="attendance-list" id="dashRecentAttendanceList">
                                <!-- Populated dynamically by script.js -->
                            </div>
                        </article>
                    </section>

                    <!-- Bottom Grid: Riwayat Membership Table (Left) & Aksi Cepat (Right) -->
                    <section class="bottom-grid">
                        <article class="panel">
                            <div class="panel-heading">
                                <div>
                                    <h2>Status & Riwayat Membership</h2>
                                    <p class="muted">Informasi paket langganan dan sisa masa aktif</p>
                                </div>
                                <a href="#membership" class="view-link" data-view="view-membership">Lihat semua</a>
                            </div>
                            <div class="table-wrap" role="region" aria-label="Riwayat Membership" tabindex="0">
                                <table>
                                    <thead>
                                        <tr>
                                            <th scope="col">PAKET</th>
                                            <th scope="col">DURASI</th>
                                            <th scope="col">BERAKHIR</th>
                                            <th scope="col">STATUS</th>
                                        </tr>
                                    </thead>
                                    <tbody id="dashMembershipTableBody">
                                        <!-- Rendered by JS -->
                                    </tbody>
                                </table>
                            </div>
                        </article>

                        <article class="panel quick-panel">
                            <div class="panel-heading">
                                <div>
                                    <h2>Aksi Cepat</h2>
                                    <p class="muted">Akses fitur utama Anda</p>
                                </div>
                            </div>
                            <div class="quick-actions">
                                <a href="javascript:void(0)" id="quickBtnCheckIn"><span class="orange-bg" aria-hidden="true"><svg class="icon" focusable="false"><use href="#icon-check-in"/></svg></span>Check-in Sekarang<svg class="icon action-chevron" aria-hidden="true" focusable="false"><use href="#icon-chevron"/></svg></a>
                                <a href="#membership" class="view-link" data-view="view-membership"><span class="orange-bg" aria-hidden="true"><svg class="icon" focusable="false"><use href="#icon-package"/></svg></span>Sisa Waktu Membership<svg class="icon action-chevron" aria-hidden="true" focusable="false"><use href="#icon-chevron"/></svg></a>
                                <a href="#attendance" class="view-link" data-view="view-attendance"><span class="orange-bg" aria-hidden="true"><svg class="icon" focusable="false"><use href="#icon-receipt"/></svg></span>Riwayat Kehadiran<svg class="icon action-chevron" aria-hidden="true" focusable="false"><use href="#icon-chevron"/></svg></a>
                            </div>
                        </article>
                    </section>
                </div>

                <!-- ============================================== -->
                <!-- 2. VIEW: RIWAYAT KEHADIRAN                     -->
                <!-- ============================================== -->
                <div id="view-attendance" class="view-panel">
                    <article class="panel">
                        <div class="panel-heading">
                            <div>
                                <h2>Riwayat Seluruh Kehadiran</h2>
                                <p class="muted">Daftar lengkap sesi latihan gym yang telah Anda ikuti.</p>
                            </div>
                            <span class="status green-bg" id="totalAttendanceBadge">0 Total Hadir</span>
                        </div>
                        <div class="table-wrap" role="region" aria-label="Riwayat Kehadiran Lengkap" tabindex="0">
                            <table>
                                <thead>
                                    <tr>
                                        <th scope="col">TANGGAL & HARI</th>
                                        <th scope="col">JAM MASUK</th>
                                        <th scope="col">SESI / ZONA</th>
                                        <th scope="col">CATATAN TARGET</th>
                                        <th scope="col">STATUS</th>
                                    </tr>
                                </thead>
                                <tbody id="attendanceFullTableBody">
                                    <!-- Rendered by JS -->
                                </tbody>
                            </table>
                        </div>
                    </article>
                </div>

                <!-- ============================================== -->
                <!-- 3. VIEW: HALAMAN MEMBER (SISA WAKTU & PAKET)   -->
                <!-- ============================================== -->
                <div id="view-membership" class="view-panel">
                    <section class="stats-grid" style="margin-bottom: 16px;">
                        <article class="stat-card">
                            <div class="stat-icon orange" aria-hidden="true">
                                <svg class="icon" focusable="false"><use href="#icon-package"/></svg>
                            </div>
                            <p>Paket Aktif</p>
                            <strong id="memActiveName">Platinum</strong>
                            <small class="positive">2 Bulan Durasi</small>
                        </article>
                        <article class="stat-card">
                            <div class="stat-icon yellow" aria-hidden="true">
                                <svg class="icon" focusable="false"><use href="#icon-warning"/></svg>
                            </div>
                            <p>Sisa Hari Aktif</p>
                            <strong><span id="memActiveDays">36</span> Hari</strong>
                            <small class="warning">Masa aktif berjalan</small>
                        </article>
                        <article class="stat-card">
                            <div class="stat-icon green" aria-hidden="true">
                                <svg class="icon" focusable="false"><use href="#icon-check"/></svg>
                            </div>
                            <p>Status Langganan</p>
                            <strong>Aktif</strong>
                            <small class="positive">Akses seluruh fasilitas</small>
                        </article>
                        <article class="stat-card">
                            <div class="stat-icon orange" aria-hidden="true">
                                <svg class="icon" focusable="false"><use href="#icon-members"/></svg>
                            </div>
                            <p>Kode Member</p>
                            <strong id="memActiveCode">GYM-0102</strong>
                            <small class="positive">Terverifikasi</small>
                        </article>
                    </section>

                    <article class="panel">
                        <div class="panel-heading">
                            <div>
                                <h2>Daftar Riwayat Paket Langganan</h2>
                                <p class="muted">Informasi paket membership yang pernah Anda beli atau aktifkan.</p>
                            </div>
                        </div>
                        <div class="table-wrap" role="region" aria-label="Riwayat Semua Membership" tabindex="0">
                            <table>
                                <thead>
                                    <tr>
                                        <th scope="col">NAMA PAKET</th>
                                        <th scope="col">PERIODE AKTIF</th>
                                        <th scope="col">DURASI</th>
                                        <th scope="col">BIAYA</th>
                                        <th scope="col">METODE PEMBAYARAN</th>
                                        <th scope="col">STATUS</th>
                                    </tr>
                                </thead>
                                <tbody id="membershipFullTableBody">
                                    <!-- Rendered by JS -->
                                </tbody>
                            </table>
                        </div>
                    </article>
                </div>
            </main>
        </div>
    </div>

    <!-- Mobile Bottom Navigation Bar (3 Main Features: Dashboard, Kehadiran, Member) -->
    <nav class="mobile-bottom-nav" aria-label="Navigasi Menu Mobile">
        <div class="bottom-nav-inner">
            <!-- 1. Dashboard -->
            <button class="nav-circle-btn active" id="btnBottomHome" data-view="view-dashboard" data-btn="home" type="button" aria-label="Dashboard Utama">
                <svg class="nav-icon" aria-hidden="true" focusable="false"><use href="#icon-nav-home"/></svg>
            </button>

            <!-- 2. Riwayat Kehadiran -->
            <button class="nav-circle-btn" id="btnBottomAttendance" data-view="view-attendance" data-btn="attendance" type="button" aria-label="Riwayat Kehadiran">
                <svg class="nav-icon" aria-hidden="true" focusable="false"><use href="#icon-check-in"/></svg>
            </button>

            <!-- 3. Halaman Member (Sisa Waktu & Paket) -->
            <button class="nav-circle-btn" id="btnBottomMembership" data-view="view-membership" data-btn="membership" type="button" aria-label="Status & Sisa Waktu Membership">
                <svg class="nav-icon" aria-hidden="true" focusable="false"><use href="#icon-package"/></svg>
            </button>
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

    <script src="script.js?v=<?= time(); ?>"></script>
</body>
</html>
