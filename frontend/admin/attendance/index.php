<?php
// frontend/admin/attendance/index.php
$page = 'attendance';
$title = 'Presensi';
$pageSubtitle = 'Catat kehadiran member dengan scan atau kode member.';

ob_start();
?>
<section class="stats-grid attendance-stats" aria-label="Ringkasan presensi">
    <article class="stat-card">
        <div class="stat-icon orange" aria-hidden="true"><svg class="icon"><use href="#icon-check-in"/></svg></div>
        <p>Check-in Hari Ini</p>
        <strong id="todayCheckins">187</strong>
        <small class="positive">↗ 5.4% dari kemarin</small>
    </article>
    <article class="stat-card">
        <div class="stat-icon green" aria-hidden="true"><svg class="icon"><use href="#icon-check"/></svg></div>
        <p>Member Aktif</p>
        <strong>986</strong>
        <small>Membership berjalan</small>
    </article>
    <article class="stat-card">
        <div class="stat-icon purple" aria-hidden="true">◷</div>
        <p>Jam Ramai</p>
        <strong>17:00–19:00</strong>
        <small>Periode tersibuk hari ini</small>
    </article>
</section>

<section class="attendance-layout">
    <article class="panel checkin-panel">
        <div class="panel-heading">
            <div>
                <h2>Catat Kehadiran</h2>
                <p class="muted">Scan QR atau masukkan kode member.</p>
            </div>
            <span class="live-badge"><i></i> Siap</span>
        </div>

        <div class="scanner-box" id="scannerBox">
            <video id="scannerVideo" autoplay muted playsinline hidden></video>
            <div class="scanner-corner top-left"></div>
            <div class="scanner-corner top-right"></div>
            <div class="scanner-center" aria-hidden="true">
                <svg class="scan-icon"><use href="#icon-check-in"/></svg>
                <span>Arahkan kamera ke QR member</span>
            </div>
            <div class="scanner-corner bottom-left"></div>
            <div class="scanner-corner bottom-right"></div>
            <div class="scan-line" aria-hidden="true"></div>
        </div>
        <p id="scannerNote" class="scanner-note">Kamera scanner belum diaktifkan</p>
        <button id="toggleScanner" class="soft-button scanner-button" type="button">Aktifkan kamera</button>

        <div class="divider"><span>atau masukkan manual</span></div>
        <form id="attendanceForm" class="manual-form">
            <label for="memberCode">Kode Member</label>
            <div class="input-action">
                <input id="memberCode" name="member_code" type="text" placeholder="Contoh: GYM-2601" autocomplete="off" required>
                <button class="primary-button" type="submit">Check-in</button>
            </div>
            <small class="form-hint">Masukkan kode yang tertera pada kartu member.</small>
        </form>
        <div id="checkinResult" class="checkin-result" role="status" aria-live="polite" hidden></div>
    </article>

    <article class="panel recent-panel">
        <div class="panel-heading">
            <div>
                <h2>Presensi Terbaru</h2>
                <p class="muted">Member yang check-in hari ini.</p>
            </div>
            <button class="soft-button demo-action" type="button">Lihat semua</button>
        </div>
        <div id="attendanceList" class="attendance-list">
            <div class="attendance-row">
                <div class="avatar peach">AR</div>
                <div><b>Andi Rachman</b><small>GYM-2601 · Paket 3 Bulan</small></div>
                <time>08:42</time>
            </div>
            <div class="attendance-row">
                <div class="avatar blue">SL</div>
                <div><b>Siti Lestari</b><small>GYM-2488 · Paket 1 Bulan</small></div>
                <time>08:35</time>
            </div>
            <div class="attendance-row">
                <div class="avatar green">BP</div>
                <div><b>Budi Pratama</b><small>GYM-2390 · Paket 6 Bulan</small></div>
                <time>08:21</time>
            </div>
            <div class="attendance-row">
                <div class="avatar purple">NA</div>
                <div><b>Nadia Anjani</b><small>GYM-2274 · Paket 1 Bulan</small></div>
                <time>08:04</time>
            </div>
        </div>
    </article>
</section>
<script src="https://cdn.jsdelivr.net/npm/jsqr@1.4.0/dist/jsQR.min.js"></script>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';
