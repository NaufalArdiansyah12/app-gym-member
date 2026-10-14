<?php
// frontend/admin/dashboard/index.php
$page = 'dashboard';
$title = 'Dashboard';
$pageSubtitle = 'Pantau operasional gym hari ini.';

ob_start();
?>
<!-- Real-Time Status Bar -->
<div class="dashboard-live-bar">
    <div class="live-status-pill">
        <span class="live-dot" aria-hidden="true"></span>
        <span class="live-label">LIVE DATA</span>
        <span class="live-sep">|</span>
        <span id="liveTimestamp" class="live-timestamp">Menghubungkan ke server...</span>
    </div>
    <div class="live-controls">
        <button id="btnRefreshDashboard" class="refresh-pill-btn" type="button" title="Segarkan Data Sekarang">
            <svg class="icon icon-sm" aria-hidden="true" focusable="false"><use href="#icon-refresh"/></svg>
            <span id="btnRefreshText">Segarkan</span>
        </button>
    </div>
</div>

<section class="stats-grid" aria-label="Ringkasan gym">
    <article class="stat-card">
        <div class="stat-icon orange" aria-hidden="true"><svg class="icon" focusable="false"><use href="#icon-members"/></svg></div>
        <p>Total Member</p>
        <strong id="statTotalMembers">-</strong>
        <small class="positive">Semua terdaftar</small>
    </article>
    <article class="stat-card">
        <div class="stat-icon green" aria-hidden="true"><svg class="icon" focusable="false"><use href="#icon-check"/></svg></div>
        <p>Member Aktif</p>
        <strong id="statActiveMembers">-</strong>
        <small class="positive">Membership berjalan</small>
    </article>
    <article class="stat-card">
        <div class="stat-icon yellow" aria-hidden="true"><svg class="icon" focusable="false"><use href="#icon-warning"/></svg></div>
        <p>Akan Berakhir</p>
        <strong id="statExpiringMembers">-</strong>
        <small class="warning">Perlu perhatian ≤ 7 hari</small>
    </article>
    <article class="stat-card">
        <div class="stat-icon orange" aria-hidden="true"><svg class="icon" focusable="false"><use href="#icon-check-in"/></svg></div>
        <p>Check-in Hari Ini</p>
        <strong id="statTodayCheckins">-</strong>
        <small class="positive">Kedatangan hari ini</small>
    </article>
</section>

<section class="content-grid">
    <article class="panel chart-panel">
        <div class="panel-heading">
            <div>
                <h2>Pendapatan</h2>
                <p class="muted" id="chartPeriodSubtitle">Ringkasan pendapatan 7 bulan terakhir</p>
            </div>
            <label class="visually-hidden" for="revenuePeriod">Periode pendapatan</label>
            <select id="revenuePeriod" class="chart-period-select">
                <option value="7months" selected>7 bulan terakhir</option>
                <option value="year">Tahun ini</option>
            </select>
        </div>
        <div class="chart-summary">
            <strong id="statRevenueAmount">Rp 0</strong>
            <span id="statRevenueGrowth" class="positive">Memuat data…</span>
        </div>
        <div class="chart">
            <div class="grid-lines" aria-hidden="true"><i></i><i></i><i></i><i></i></div>
            <svg id="revenueChartSvg" viewBox="0 0 700 220" preserveAspectRatio="none" role="img" aria-label="Grafik pendapatan real-time">
                <defs>
                    <linearGradient id="fill" x1="0" x2="0" y1="0" y2="1">
                        <stop offset="0" stop-color="#ff7a1a" stop-opacity=".4" />
                        <stop offset="1" stop-color="#ff7a1a" stop-opacity="0" />
                    </linearGradient>
                </defs>
                <path id="chartArea" class="area" d="M0 220 L700 220 Z" />
                <path id="chartLine" class="line" d="M0 220 L700 220" />
                <g id="chartPoints" class="chart-points" aria-hidden="true"></g>
            </svg>
            <div id="chartLabels" class="chart-labels" aria-hidden="true">
                <!-- Label bulan dinamis dari database -->
            </div>
        </div>
    </article>

    <article class="panel attendance-panel">
        <div class="panel-heading">
            <div>
                <h2>Check-in Hari Ini</h2>
                <p class="muted">Kedatangan terbaru</p>
            </div>
            <a href="../attendance/">Lihat semua</a>
        </div>
        <div class="attendance-list" id="attendanceList">
            <p class="muted" style="padding: 1.5rem; text-align: center;">Memuat presensi hari ini…</p>
        </div>
    </article>
</section>

<section class="bottom-grid">
    <article class="panel">
        <div class="panel-heading">
            <div>
                <h2>Member Akan Berakhir</h2>
                <p class="muted">Masa aktif berakhir dalam 7 hari</p>
            </div>
            <a href="../members/">Lihat semua</a>
        </div>
        <div class="table-wrap" role="region" aria-label="Member yang akan berakhir, geser untuk melihat semua kolom" tabindex="0">
            <table>
                <thead>
                    <tr>
                        <th scope="col">MEMBER</th>
                        <th scope="col">PAKET</th>
                        <th scope="col">BERAKHIR</th>
                        <th scope="col">STATUS</th>
                    </tr>
                </thead>
                <tbody id="expiringTableBody">
                    <tr><td colspan="4" style="text-align: center; color: #9ca3af; padding: 1.5rem;">Memuat data…</td></tr>
                </tbody>
            </table>
        </div>
    </article>

    <article class="panel quick-panel">
        <div class="panel-heading">
            <div>
                <h2>Aksi Cepat</h2>
                <p class="muted">Akses fitur utama</p>
            </div>
        </div>
        <div class="quick-actions">
            <a href="../members/" id="btnOpenAddMember"><span class="orange-bg" aria-hidden="true"><svg class="icon" focusable="false"><use href="#icon-members"/></svg></span>Tambah Member<svg class="icon action-chevron" aria-hidden="true" focusable="false"><use href="#icon-chevron"/></svg></a>
            <a href="../cashier/"><span class="orange-bg" aria-hidden="true"><svg class="icon" focusable="false"><use href="#icon-receipt"/></svg></span>Transaksi Baru<svg class="icon action-chevron" aria-hidden="true" focusable="false"><use href="#icon-chevron"/></svg></a>
            <a href="../attendance/"><span class="orange-bg" aria-hidden="true"><svg class="icon" focusable="false"><use href="#icon-check-in"/></svg></span>Check-in Member<svg class="icon action-chevron" aria-hidden="true" focusable="false"><use href="#icon-chevron"/></svg></a>
        </div>
    </article>
</section>

<div id="modalAddMember" class="modal-overlay" aria-hidden="true">
    <div class="modal-dialog" role="dialog" aria-modal="true" aria-labelledby="modalTitle">
        <div class="modal-header">
            <div>
                <h2 id="modalTitle">Tambah Member Baru</h2>
                <p class="muted">Isi formulir untuk mendaftarkan member baru</p>
            </div>
            <button id="btnCloseModal" class="icon-button modal-close-btn" type="button" aria-label="Tutup modal">
                <svg class="icon" aria-hidden="true" focusable="false"><use href="#icon-close"/></svg>
            </button>
        </div>
        <form id="formAddMember" class="modal-form">
            <div class="form-group">
                <label for="memberName">Nama Lengkap</label>
                <input id="memberName" name="nama_lengkap" type="text" placeholder="Contoh: Budi Pratama" required>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="memberPhone">Nomor WhatsApp / HP</label>
                    <input id="memberPhone" name="nomor_telepon" type="tel" placeholder="08xxxxxxxxxx" required>
                </div>
                <div class="form-group">
                    <label for="memberGender">Jenis Kelamin</label>
                    <select id="memberGender" name="jenis_kelamin" required>
                        <option value="pria">Pria</option>
                        <option value="wanita">Wanita</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label for="memberEmergency">Kontak Darurat (Opsional)</label>
                <input id="memberEmergency" name="kontak_darurat" type="tel" placeholder="Nomor kerabat / keluarga">
            </div>

            <div class="modal-actions">
                <button id="btnCancelModal" class="btn-cancel" type="button">Batal</button>
                <button class="btn-submit" type="submit">Simpan Member</button>
            </div>
        </form>
    </div>
</div>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';
