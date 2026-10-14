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
        <strong id="todayCheckins">0</strong>
        <small class="positive">Presensi berjalan</small>
    </article>
    <article class="stat-card">
        <div class="stat-icon green" aria-hidden="true"><svg class="icon"><use href="#icon-check"/></svg></div>
        <p>Member Aktif</p>
        <strong>Member</strong>
        <small>Membership terdaftar</small>
    </article>
    <article class="stat-card">
        <div class="stat-icon purple" aria-hidden="true">◷</div>
        <p>Jam Ramai</p>
        <strong>17:00–19:00</strong>
        <small>Periode operasional</small>
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
            <div class="scanner-center" id="scannerCenter" aria-hidden="true">
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
                <input id="memberCode" name="member_code" type="text" placeholder="Contoh: GYM001 atau GYM-..." autocomplete="off" required>
                <button class="primary-button" type="submit">Check-in</button>
            </div>
            <small class="form-hint">Masukkan kode yang tertera pada kartu / QR digital member.</small>
        </form>
        <div id="checkinResult" class="checkin-result" role="status" aria-live="polite" hidden></div>
    </article>

    <article class="panel recent-panel">
        <div class="panel-heading">
            <div>
                <h2>Presensi Terbaru</h2>
                <p class="muted">Member yang check-in hari ini.</p>
            </div>
        </div>
        <div id="attendanceList" class="attendance-list">
            <p class="muted" style="padding: 1rem; text-align: center;">Memuat presensi hari ini...</p>
        </div>
    </article>
</section>

<!-- Modal Konfirmasi Presensi Berhasil -->
<div id="modalAttendanceSuccess" class="modal-overlay" aria-hidden="true">
    <div class="modal-dialog modal-attendance-dialog" role="dialog" aria-modal="true" aria-labelledby="modalAttTitle">
        <div class="attendance-success-header">
            <div class="success-icon-badge" aria-hidden="true">
                <svg class="icon" focusable="false"><use href="#icon-check"/></svg>
            </div>
            <h2 id="modalAttTitle">Presensi Berhasil!</h2>
            <p class="muted">Kehadiran member telah tercatat di sistem.</p>
        </div>

        <div class="attendance-card-preview">
            <div class="attendance-member-identity">
                <div class="avatar orange large-avatar" id="modalAttAvatar">M</div>
                <div>
                    <h3 id="modalAttName">Nama Member</h3>
                    <span class="member-code-tag" id="modalAttCode">GYM001</span>
                </div>
            </div>

            <div class="attendance-details-list">
                <div class="attendance-detail-item">
                    <span class="muted">Waktu Check-in</span>
                    <strong id="modalAttTime">-</strong>
                </div>
                <div class="attendance-detail-item">
                    <span class="muted">Status Membership</span>
                    <span id="modalAttStatus" class="status success-bg">Aktif</span>
                </div>
                <div class="attendance-detail-item" id="modalAttExpiresRow">
                    <span class="muted">Masa Aktif Hingga</span>
                    <strong id="modalAttExpires">-</strong>
                </div>
            </div>
        </div>

        <div class="attendance-modal-actions">
            <button id="btnCloseAttModal" class="primary-button btn-full" type="button">OK / Lanjutkan Scan</button>
            <div class="modal-autoclose-hint">
                Modal menutup otomatis dalam <span id="autoCloseSeconds">4</span> detik
            </div>
        </div>
    </div>
</div>

<!-- Modal Peringatan Sudah Presensi Hari Ini -->
<div id="modalAttendanceWarning" class="modal-overlay" aria-hidden="true">
    <div class="modal-dialog modal-attendance-dialog modal-warning-dialog" role="dialog" aria-modal="true" aria-labelledby="modalAttWarnTitle">
        <div class="attendance-success-header">
            <div class="warning-icon-badge" aria-hidden="true">
                <svg class="icon" focusable="false"><use href="#icon-warning"/></svg>
            </div>
            <h2 id="modalAttWarnTitle">Sudah Presensi Hari Ini!</h2>
            <p class="muted">Member ini telah tercatat hadir pada hari ini.</p>
        </div>

        <div class="attendance-card-preview">
            <div class="attendance-member-identity">
                <div class="avatar orange large-avatar" id="modalAttWarnAvatar">M</div>
                <div>
                    <h3 id="modalAttWarnName">Nama Member</h3>
                    <span class="member-code-tag" id="modalAttWarnCode">GYM001</span>
                </div>
            </div>

            <div class="attendance-details-list">
                <div class="attendance-detail-item">
                    <span class="muted">Status Presensi</span>
                    <span class="status warning-bg">Sudah Hadir</span>
                </div>
                <div class="attendance-detail-item">
                    <span class="muted">Waktu Presensi Hari Ini</span>
                    <strong id="modalAttWarnTime" style="color: #fbbf24;">-</strong>
                </div>
                <div class="attendance-detail-item" id="modalAttWarnExpiresRow">
                    <span class="muted">Masa Aktif Paket</span>
                    <strong id="modalAttWarnExpires">-</strong>
                </div>
            </div>
        </div>

        <div class="attendance-modal-actions">
            <button id="btnCloseAttWarnModal" class="primary-button btn-warning-action btn-full" type="button">Tutup / Lanjutkan Scan</button>
            <div class="modal-autoclose-hint">
                Modal menutup otomatis dalam <span id="autoCloseWarnSeconds">4</span> detik
            </div>
        </div>
    </div>
</div>

<script src="../../assets/js/jsqr.min.js"></script>
<script>
if (typeof window.jsQR === 'undefined') {
    document.write('<script src="https://cdn.jsdelivr.net/npm/jsqr@1.4.0/dist/jsQR.min.js"><\/script>');
}
</script>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';
