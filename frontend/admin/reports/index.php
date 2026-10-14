<?php
// frontend/admin/reports/index.php
$page = 'reports';
$title = 'Laporan';
$pageSubtitle = 'Ringkasan performa keuangan, penjualan paket, dan presensi member.';

ob_start();
?>
<!-- Toolbar Filter Periode Laporan -->
<section class="panel filter-panel">
    <div class="filter-toolbar">
        <div class="filter-group">
            <label for="filterPreset" class="filter-label">Rentang Waktu</label>
            <select id="filterPreset" class="filter-select">
                <option value="month" selected>Bulan Ini</option>
                <option value="last_month">Bulan Lalu</option>
                <option value="today">Hari Ini</option>
                <option value="7days">7 Hari Terakhir</option>
                <option value="year">Tahun Ini</option>
                <option value="custom">Kustom Tanggal</option>
            </select>
        </div>

        <div class="filter-group date-inputs" id="dateInputsWrap">
            <div class="date-item">
                <label for="filterStartDate" class="filter-label">Dari Tanggal</label>
                <input type="date" id="filterStartDate" class="filter-date">
            </div>
            <div class="date-item">
                <label for="filterEndDate" class="filter-label">Sampai Tanggal</label>
                <input type="date" id="filterEndDate" class="filter-date">
            </div>
        </div>

        <div class="filter-actions">
            <button id="btnApplyFilter" class="primary-button" type="button">
                <svg class="icon" aria-hidden="true" focusable="false" style="width:14px; height:14px; vertical-align:middle; margin-right:4px;"><use href="#icon-refresh"/></svg>Terapkan Filter
            </button>
        </div>
    </div>
</section>

<!-- Kartu Ringkasan KPI Eksekutif -->
<section class="stats-grid" aria-label="Ringkasan performa">
    <article class="stat-card">
        <div class="stat-icon green" aria-hidden="true">Rp</div>
        <p>Total Pendapatan</p>
        <strong id="kpiRevenue">Rp 0</strong>
        <small id="kpiRevenueSub">Pendapatan periode</small>
    </article>
    <article class="stat-card">
        <div class="stat-icon orange" aria-hidden="true">☷</div>
        <p>Total Transaksi</p>
        <strong id="kpiTransactions">0</strong>
        <small>Transaksi kasir</small>
    </article>
    <article class="stat-card">
        <div class="stat-icon blue" aria-hidden="true" style="color: #60a5fa;">✓</div>
        <p>Total Kunjungan</p>
        <strong id="kpiCheckins">0</strong>
        <small>Presensi check-in</small>
    </article>
    <article class="stat-card">
        <div class="stat-icon purple" aria-hidden="true">♙</div>
        <p>Member Baru</p>
        <strong id="kpiNewMembers">0</strong>
        <small>Terdaftar di periode</small>
    </article>
</section>

<!-- Panel Utama Laporan dengan Tab Navigasi -->
<section class="panel page-panel">
    <div class="reports-header-tabs">
        <div class="reports-tab-nav" role="tablist">
            <button class="report-tab-btn active" role="tab" data-tab="tab-finance" aria-selected="true">
                💰 Laporan Keuangan &amp; Kasir
            </button>
            <button class="report-tab-btn" role="tab" data-tab="tab-packages" aria-selected="false">
                📦 Performa Paket Langganan
            </button>
            <button class="report-tab-btn" role="tab" data-tab="tab-attendance" aria-selected="false">
                📋 Log Presensi Member
            </button>
        </div>
    </div>

    <!-- TAB 1: LAPORAN KEUANGAN & KASIR -->
    <div class="report-tab-pane active" id="tab-finance" role="tabpanel">
        <div class="section-sub-header">
            <div>
                <h3>Rincian Pembayaran &amp; Kasir</h3>
                <p class="muted">Daftar transaksi pembayaran paket yang berhasil diproses.</p>
            </div>
            <!-- Ringkasan Mini Metode Bayar -->
            <div class="method-chips" id="methodChips">
                <span class="method-chip"><b>Tunai:</b> <span id="chipTunai">Rp 0 (0 trx)</span></span>
                <span class="method-chip"><b>Transfer:</b> <span id="chipTransfer">Rp 0 (0 trx)</span></span>
                <span class="method-chip"><b>QRIS:</b> <span id="chipQris">Rp 0 (0 trx)</span></span>
            </div>
        </div>

        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th scope="col">NO. INVOICE</th>
                        <th scope="col">WAKTU</th>
                        <th scope="col">MEMBER</th>
                        <th scope="col">PAKET</th>
                        <th scope="col">METODE</th>
                        <th scope="col" style="text-align: right;">TOTAL BAYAR</th>
                    </tr>
                </thead>
                <tbody id="tableFinanceBody">
                    <tr><td colspan="6" class="table-empty">Memuat data laporan keuangan…</td></tr>
                </tbody>
                <tfoot>
                    <tr class="table-total-row">
                        <td colspan="5" style="text-align: right; font-weight: 700;">TOTAL PENDAPATAN PERIODE INI:</td>
                        <td id="tableFinanceTotal" style="text-align: right; font-weight: 700; color: #4ade80;">Rp 0</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <!-- TAB 2: LAPORAN PERFORMA PAKET -->
    <div class="report-tab-pane" id="tab-packages" role="tabpanel" hidden>
        <div class="section-sub-header">
            <div>
                <h3>Penjualan &amp; Popularitas Paket</h3>
                <p class="muted">Statistik jumlah langganan dan total omzet yang dihasilkan setiap paket.</p>
            </div>
        </div>

        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th scope="col">NAMA PAKET</th>
                        <th scope="col">DURASI</th>
                        <th scope="col" style="text-align: right;">HARGA SATUAN</th>
                        <th scope="col" style="text-align: center;">TOTAL TERJUAL</th>
                        <th scope="col" style="text-align: right;">TOTAL PENDAPATAN</th>
                    </tr>
                </thead>
                <tbody id="tablePackagesBody">
                    <tr><td colspan="5" class="table-empty">Memuat data performa paket…</td></tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- TAB 3: LAPORAN LOG PRESENSI -->
    <div class="report-tab-pane" id="tab-attendance" role="tabpanel" hidden>
        <div class="section-sub-header">
            <div>
                <h3>Log Kunjungan Member</h3>
                <p class="muted">Riwayat kedatangan dan check-in member pada periode terpilih.</p>
            </div>
        </div>

        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th scope="col">TANGGAL</th>
                        <th scope="col">JAM MASUK</th>
                        <th scope="col">KODE MEMBER</th>
                        <th scope="col">NAMA MEMBER</th>
                        <th scope="col">GENDER</th>
                    </tr>
                </thead>
                <tbody id="tableAttendanceBody">
                    <tr><td colspan="5" class="table-empty">Memuat log presensi member…</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</section>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';

