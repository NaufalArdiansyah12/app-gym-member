<?php
// frontend/admin/dashboard/index.php
$page = 'dashboard';
$title = 'Dashboard';
$pageSubtitle = 'Pantau operasional gym hari ini.';

$chartLabels = ['Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt'];
$chartSeries = [0, 0, 0, 0, 0, 0, 0];
$chartTotalFormatted = '0 Member';

try {
    require_once __DIR__ . '/../../../backend/config/database.php';
    require_once __DIR__ . '/../../../backend/controllers/admin/DashboardController.php';
    $db = getDB();
    $memberChartData = DashboardController::getMemberChart($db);
    $chartLabels = $memberChartData['labels'] ?? $chartLabels;
    $chartSeries = $memberChartData['series'] ?? $chartSeries;
    $chartTotalFormatted = $memberChartData['total_formatted'] ?? '0 Member';
} catch (\Throwable $e) {
    // ponytail: Fallback gracefully jika koneksi DB offline saat SSR.
}

$svgWidth = 700;
$svgHeight = 220;
$bottomPad = 195;
$usableH = 165;
$maxVal = max(!empty($chartSeries) ? max($chartSeries) : 1, 1);
$count = count($chartSeries);

$initPoints = [];
foreach ($chartSeries as $idx => $val) {
    $x = $count > 1 ? round(($idx / ($count - 1)) * $svgWidth) : round($svgWidth / 2);
    $y = round($bottomPad - ($val / $maxVal) * $usableH);
    $initPoints[] = ['x' => $x, 'y' => $y, 'val' => (int)$val, 'label' => $chartLabels[$idx] ?? ''];
}

$initPathD = '';
$initAreaD = '';
if (!empty($initPoints)) {
    $initPathD = "M {$initPoints[0]['x']} {$initPoints[0]['y']}";
    for ($i = 1; $i < count($initPoints); $i++) {
        $prev = $initPoints[$i - 1];
        $curr = $initPoints[$i];
        $cX = round($prev['x'] + ($curr['x'] - $prev['x']) / 2);
        $initPathD .= " C {$cX} {$prev['y']} {$cX} {$curr['y']} {$curr['x']} {$curr['y']}";
    }
    $initAreaD = "{$initPathD} L {$svgWidth} {$svgHeight} L 0 {$svgHeight} Z";
}

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
                <h2 id="chartHeading">Pendaftaran Member</h2>
                <p class="muted" id="chartSubheading">Tren member baru 7 bulan terakhir</p>
            </div>
            <label class="visually-hidden" for="chartMetricSelect">Pilihan grafik</label>
            <select id="chartMetricSelect">
                <option value="member" selected>Pendaftaran Member</option>
                <option value="revenue">Pendapatan</option>
            </select>
        </div>
        <div class="chart-summary">
            <strong id="chartSummaryValue"><?= htmlspecialchars($chartTotalFormatted) ?></strong>
            <span class="positive" id="chartSummaryBadge">7 bulan terakhir</span>
        </div>
        <div class="chart" id="dashboardChartContainer" data-init='<?= htmlspecialchars(json_encode($initPoints), ENT_QUOTES, 'UTF-8') ?>'>
            <div class="grid-lines" aria-hidden="true"><i></i><i></i><i></i><i></i></div>
            <div class="chart-tooltip" id="chartTooltip" role="tooltip" aria-hidden="true">
                <div class="tooltip-month" id="tooltipMonth"></div>
                <div class="tooltip-value" id="tooltipValue"></div>
            </div>
            <svg id="dashboardChartSvg" viewBox="0 0 700 220" preserveAspectRatio="none" role="img" aria-label="Grafik data dashboard">
                <defs>
                    <linearGradient id="fill" x1="0" x2="0" y1="0" y2="1">
                        <stop offset="0" stop-color="#ff7a1a" stop-opacity=".4" />
                        <stop offset="1" stop-color="#ff7a1a" stop-opacity="0" />
                    </linearGradient>
                </defs>
                <path class="area" id="chartArea" d="<?= $initAreaD ?>" fill="url(#fill)" />
                <path class="line" id="chartLine" d="<?= $initPathD ?>" fill="none" stroke="#ff7a1a" stroke-width="3" />
                <line id="hoverLine" x1="0" y1="15" x2="0" y2="215" stroke="rgba(255,122,26,0.35)" stroke-dasharray="4 4" stroke-width="2" style="display:none;" />
                <circle id="hoverPoint" cx="0" cy="0" r="5.5" fill="#ff7a1a" stroke="#ffffff" stroke-width="2.5" style="display:none; filter: drop-shadow(0 0 8px rgba(255,122,26,0.9));" />
            </svg>
            <div class="chart-labels" id="chartLabels" aria-hidden="true">
                <?php foreach ($chartLabels as $idx => $label): ?>
                    <span data-index="<?= $idx ?>"><?= htmlspecialchars($label) ?></span>
                <?php endforeach; ?>
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
            <a href="../cashier/"><span class="orange-bg" aria-hidden="true"><svg class="icon" focusable="false"><use href="#icon-refresh"/></svg></span>Perpanjang Paket<svg class="icon action-chevron" aria-hidden="true" focusable="false"><use href="#icon-chevron"/></svg></a>
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

            <!-- Aktivasi Paket Membership Sekalian -->
            <div class="form-group">
                <label for="memberPackage">Aktivasi Paket Membership (Opsional)</label>
                <select id="memberPackage" name="id_paket">
                    <option value="">-- Tanpa Paket (Registrasi Saja) --</option>
                </select>
            </div>

            <div id="packageActivationFields" style="display: none;">
                <div class="form-row">
                    <div class="form-group">
                        <label for="memberStartDate">Tanggal Mulai Aktif</label>
                        <input id="memberStartDate" name="tanggal_mulai" type="date">
                    </div>
                    <div class="form-group">
                        <label for="memberPaymentMethod">Metode Pembayaran</label>
                        <select id="memberPaymentMethod" name="metode_pembayaran">
                            <option value="tunai">Tunai</option>
                            <option value="qris">QRIS</option>
                            <option value="transfer">Transfer Bank</option>
                        </select>
                    </div>
                </div>
                <div id="packageInfoBadge" style="margin-top: 4px; padding: 10px 14px; font-size: 12px; background: rgba(255, 122, 26, 0.08); border: 1px solid rgba(255, 122, 26, 0.25); border-radius: 10px; color: #fed7aa;" hidden></div>
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
