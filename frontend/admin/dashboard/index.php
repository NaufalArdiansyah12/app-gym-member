<?php
// frontend/admin/dashboard/index.php
$page = 'dashboard';
$title = 'Dashboard';
$pageSubtitle = 'Pantau operasional gym hari ini.';

ob_start();
?>
<section class="stats-grid" aria-label="Ringkasan gym">
    <article class="stat-card">
        <div class="stat-icon orange" aria-hidden="true"><svg class="icon" focusable="false"><use href="#icon-members"/></svg></div>
        <p>Total Member</p>
        <strong id="statTotalMembers">1,248</strong>
        <small class="positive">Semua terdaftar</small>
    </article>
    <article class="stat-card">
        <div class="stat-icon green" aria-hidden="true"><svg class="icon" focusable="false"><use href="#icon-check"/></svg></div>
        <p>Member Aktif</p>
        <strong id="statActiveMembers">986</strong>
        <small class="positive">Membership berjalan</small>
    </article>
    <article class="stat-card">
        <div class="stat-icon yellow" aria-hidden="true"><svg class="icon" focusable="false"><use href="#icon-warning"/></svg></div>
        <p>Akan Berakhir</p>
        <strong id="statExpiringMembers">42</strong>
        <small class="warning">Perlu perhatian ≤ 7 hari</small>
    </article>
    <article class="stat-card">
        <div class="stat-icon orange" aria-hidden="true"><svg class="icon" focusable="false"><use href="#icon-check-in"/></svg></div>
        <p>Check-in Hari Ini</p>
        <strong id="statTodayCheckins">187</strong>
        <small class="positive">Kedatangan hari ini</small>
    </article>
</section>

<section class="content-grid">
    <article class="panel chart-panel">
        <div class="panel-heading">
            <div>
                <h2>Pendapatan</h2>
                <p class="muted">Ringkasan pendapatan tahun 2026</p>
            </div>
            <label class="visually-hidden" for="revenuePeriod">Periode pendapatan</label>
            <select id="revenuePeriod">
                <option>7 bulan terakhir</option>
                <option>Tahun ini</option>
            </select>
        </div>
        <div class="chart-summary">
            <strong>Rp 48.560.000</strong>
            <span class="positive">↗ 14.8%</span>
        </div>
        <div class="chart">
            <div class="grid-lines" aria-hidden="true"><i></i><i></i><i></i><i></i></div>
            <svg viewBox="0 0 700 220" preserveAspectRatio="none" role="img" aria-label="Grafik pendapatan">
                <defs>
                    <linearGradient id="fill" x1="0" x2="0" y1="0" y2="1">
                        <stop offset="0" stop-color="#ff7a1a" stop-opacity=".4" />
                        <stop offset="1" stop-color="#ff7a1a" stop-opacity="0" />
                    </linearGradient>
                </defs>
                <path class="area" d="M0 180 C55 155 70 170 110 135 S180 150 220 105 S290 130 335 85 S400 112 450 65 S510 95 560 45 S625 70 700 18 L700 220 L0 220Z" />
                <path class="line" d="M0 180 C55 155 70 170 110 135 S180 150 220 105 S290 130 335 85 S400 112 450 65 S510 95 560 45 S625 70 700 18" />
                <g class="chart-points" aria-hidden="true">
                    <circle cx="110" cy="135" r="3.5" />
                    <circle cx="220" cy="105" r="3.5" />
                    <circle cx="335" cy="85" r="3.5" />
                    <circle cx="450" cy="65" r="3.5" />
                    <circle cx="560" cy="45" r="3.5" />
                    <circle cx="700" cy="18" r="3.5" />
                </g>
                <!-- ponytail: Grafik tetap ilustrasi statis; tambah titik nilai dan tooltip setelah API laporan tersedia. -->
            </svg>
            <div class="chart-labels" aria-hidden="true">
                <span>Apr</span><span>Mei</span><span>Jun</span><span>Jul</span><span>Agu</span><span>Sep</span><span>Okt</span>
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
            <div class="attendance-row">
                <div class="avatar peach" aria-hidden="true">AR</div>
                <div>
                    <b>Andi Rachman</b>
                    <small>GYM-2601 · Paket 3 Bulan</small>
                </div>
                <time datetime="08:42">08:42</time>
            </div>
            <div class="attendance-row">
                <div class="avatar blue" aria-hidden="true">SL</div>
                <div>
                    <b>Siti Lestari</b>
                    <small>GYM-2488 · Paket 1 Bulan</small>
                </div>
                <time datetime="08:35">08:35</time>
            </div>
            <div class="attendance-row">
                <div class="avatar violet" aria-hidden="true">BP</div>
                <div>
                    <b>Budi Pratama</b>
                    <small>GYM-2390 · Paket 6 Bulan</small>
                </div>
                <time datetime="08:21">08:21</time>
            </div>
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
                    <tr>
                        <td><b>Rizky Maulana</b><small>GYM-2188</small></td>
                        <td>1 Bulan</td>
                        <td>15 Okt 2026</td>
                        <td><span class="status warning-bg">2 hari lagi</span></td>
                    </tr>
                    <tr>
                        <td><b>Devi Anggraini</b><small>GYM-2241</small></td>
                        <td>3 Bulan</td>
                        <td>17 Okt 2026</td>
                        <td><span class="status warning-bg">4 hari lagi</span></td>
                    </tr>
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
