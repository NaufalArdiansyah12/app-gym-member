<?php
// frontend/user/dashboard/index.php
$page = 'dashboard';
$title = 'Dashboard Member';
$pageEyebrow = 'DASHBOARD · ' . strtoupper(date('d M Y'));
$pageHeading = 'Selamat pagi, <span id="welcomeName">Gym Member</span>';
$pageSubtitle = 'Pantau status membership dan absensi gym Anda.';

ob_start();
?>
<!-- Confetti Canvas for Celebrations -->
<canvas id="confettiCanvas" class="confetti-canvas"></canvas>

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

<!-- Content Grid: Kartu Member Digital & QR Presensi (Left) & Check-in Hari Ini (Right) -->
<section class="content-grid">
    <article class="panel member-card-panel" id="memberCardPanel">
        <div class="panel-heading">
            <div>
                <h2>Kartu Member Digital</h2>
                <p class="muted">Tunjukkan QR Code ini kepada admin / resepsionis untuk presensi kedatangan.</p>
            </div>
            <span class="status green-bg" id="badgeAttendanceStatus">Aktif</span>
        </div>

        <!-- Banner jika sudah check-in hari ini -->
        <div id="alreadyCheckedInNotice" class="alert-box green-alert hidden">
            <div class="alert-icon-check">✔</div>
            <div>
                <b id="checkedInBannerTitle">Kehadiran Terkonfirmasi</b>
                <p id="checkedInBannerSubtitle">Anda sudah melakukan check-in hari ini.</p>
            </div>
        </div>

        <!-- Info jika belum check-in hari ini -->
        <div id="notCheckedInNotice" class="alert-box info-alert">
            <div class="alert-icon-info">ℹ</div>
            <div>
                <b>Check-in via Scan Admin</b>
                <p>Tunjukkan kartu digital atau kode member di bawah ini ke meja resepsionis saat tiba di gym.</p>
            </div>
        </div>

        <!-- Digital Membership Badge / Pass -->
        <div class="member-pass-card">
            <div class="pass-card-glow" aria-hidden="true"></div>
            <div class="pass-card-content">
                <div class="pass-header">
                    <div class="pass-brand">
                        <svg class="icon" aria-hidden="true" focusable="false"><use href="#icon-gym"/></svg>
                        <span>USER <strong>GYM</strong> PASS</span>
                    </div>
                    <span class="pass-chip-status" id="passCardStatus">MEMBER AKTIF</span>
                </div>

                <div class="pass-body">
                    <div class="pass-qr-wrap">
                        <div id="memberQrCode" class="pass-qr-canvas" aria-label="QR Code Presensi Member"></div>
                        <small class="pass-scan-hint">Scan untuk Presensi</small>
                    </div>
                    <div class="pass-meta">
                        <div class="pass-meta-group">
                            <span class="pass-label">NAMA MEMBER</span>
                            <strong class="pass-value" id="passMemberName">Gym Member</strong>
                        </div>
                        <div class="pass-meta-group">
                            <span class="pass-label">KODE MEMBER</span>
                            <div class="pass-code-row">
                                <span class="pass-code-badge" id="passMemberCode">GYM-0102</span>
                                <button type="button" id="btnCopyMemberCode" class="pass-copy-btn" title="Salin Kode Member">
                                    <svg class="icon" aria-hidden="true" focusable="false"><use href="#icon-receipt"/></svg>
                                    <span>Salin</span>
                                </button>
                            </div>
                        </div>
                        <div class="pass-meta-row">
                            <div class="pass-meta-group">
                                <span class="pass-label">PAKET AKTIF</span>
                                <span class="pass-subvalue" id="passPackageName">Paket Platinum</span>
                            </div>
                            <div class="pass-meta-group">
                                <span class="pass-label">MASA BERLAKU</span>
                                <span class="pass-subvalue" id="passExpiryDate">18 Nov 2026</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="pass-footer">
                    <span class="pass-footer-tip">
                        <svg class="icon" aria-hidden="true" focusable="false"><use href="#icon-check"/></svg>
                        Tunjukkan kartu digital ini saat tiba di gym
                    </span>
                    <span class="pass-footer-id" id="passFooterId">ID: #0102</span>
                </div>
            </div>
        </div>
    </article>

    <article class="panel attendance-panel">
        <div class="panel-heading">
            <div>
                <h2>Riwayat Check-in Terbaru</h2>
                <p class="muted">Catatan presensi terakhir Anda</p>
            </div>
            <a href="../kehadiran/" class="view-link">Lihat semua</a>
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
            <a href="../membership/" class="view-link">Lihat semua</a>
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
            <a href="#memberCardPanel" id="quickBtnMemberCard"><span class="orange-bg" aria-hidden="true"><svg class="icon" focusable="false"><use href="#icon-members"/></svg></span>Kartu Member Digital<svg class="icon action-chevron" aria-hidden="true" focusable="false"><use href="#icon-chevron"/></svg></a>
            <a href="../membership/"><span class="orange-bg" aria-hidden="true"><svg class="icon" focusable="false"><use href="#icon-package"/></svg></span>Sisa Waktu Membership<svg class="icon action-chevron" aria-hidden="true" focusable="false"><use href="#icon-chevron"/></svg></a>
            <a href="../kehadiran/"><span class="orange-bg" aria-hidden="true"><svg class="icon" focusable="false"><use href="#icon-receipt"/></svg></span>Riwayat Kehadiran<svg class="icon action-chevron" aria-hidden="true" focusable="false"><use href="#icon-chevron"/></svg></a>
        </div>
    </article>
</section>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';
