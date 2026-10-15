<?php
// frontend/admin/reports/index.php
$page = 'reports';
$title = 'Laporan';
$pageSubtitle = 'Analisis pendapatan, transaksi kasir, performa paket, dan aktivitas presensi.';

ob_start();
?>
<!-- Toolbar Filter Periode -->
<section class="panel filter-panel" aria-label="Filter periode laporan">
    <div class="filter-wrap">
        <div class="preset-group" role="group" aria-label="Pilihan rentang waktu">
            <button class="preset-btn" type="button" data-preset="today">Hari Ini</button>
            <button class="preset-btn" type="button" data-preset="7days">7 Hari</button>
            <button class="preset-btn active" type="button" data-preset="thismonth">Bulan Ini</button>
            <button class="preset-btn" type="button" data-preset="30days">30 Hari</button>
            <button class="preset-btn" type="button" data-preset="thisyear">Tahun Ini</button>
        </div>

        <form id="filterForm" class="date-filter-form">
            <div class="date-input-group">
                <label for="startDate">Mulai</label>
                <input type="date" id="startDate" name="start_date" required>
            </div>
            <span class="date-separator" aria-hidden="true">s/d</span>
            <div class="date-input-group">
                <label for="endDate">Selesai</label>
                <input type="date" id="endDate" name="end_date" required>
            </div>
            <button id="btnApplyFilter" class="primary-button btn-apply" type="submit">
                <svg class="icon icon-sm" aria-hidden="true" focusable="false"><use href="#icon-refresh"/></svg>
                Terapkan
            </button>
        </form>

        <div class="filter-actions">
            <button id="btnPrintReport" class="soft-button" type="button" title="Cetak laporan">
                <svg class="icon icon-sm" aria-hidden="true" focusable="false"><use href="#icon-printer"/></svg>
                Cetak
            </button>
        </div>
    </div>
</section>

<!-- Ringkasan Eksekutif Finansial (Stats Cards) -->
<section class="stats-grid" aria-label="Ringkasan finansial dan kasir">
    <article class="stat-card">
        <div class="stat-icon orange" aria-hidden="true">
            <svg class="icon" focusable="false"><use href="#icon-chart"/></svg>
        </div>
        <p>Total Omzet Periode</p>
        <strong id="statRevenue">Rp 0</strong>
        <small class="positive" id="statPeriodLabel">Memuat periode…</small>
    </article>

    <article class="stat-card">
        <div class="stat-icon green" aria-hidden="true">
            <svg class="icon" focusable="false"><use href="#icon-sparkle"/></svg>
        </div>
        <p>Rata-rata Transaksi</p>
        <strong id="statAvgTx">Rp 0</strong>
        <small class="positive" id="statTxSub">0 transaksi terbayar</small>
    </article>

    <article class="stat-card">
        <div class="stat-icon purple" aria-hidden="true">
            <svg class="icon" focusable="false"><use href="#icon-check"/></svg>
        </div>
        <p>Pemasukan Non-Tunai</p>
        <strong id="statNonCash">Rp 0</strong>
        <small class="positive" id="statNonCashSub">QRIS & Transfer</small>
    </article>

    <article class="stat-card">
        <div class="stat-icon yellow" aria-hidden="true">
            <svg class="icon" focusable="false"><use href="#icon-receipt"/></svg>
        </div>
        <p>Kas Tunai Fisik</p>
        <strong id="statCash">Rp 0</strong>
        <small id="statCashSub">Uang laci kasir</small>
    </article>
</section>

<!-- Grid Distribusi Pembayaran & Performa Paket -->
<section class="breakdown-grid">
    <!-- Panel Metode Pembayaran -->
    <article class="panel">
        <div class="panel-heading">
            <div>
                <h2>Metode Pembayaran</h2>
                <p class="muted">Distribusi penerimaan kasir pada periode ini.</p>
            </div>
        </div>
        <div class="method-breakdown" id="methodBreakdownList">
            <div class="loading-placeholder">Memuat data metode pembayaran…</div>
        </div>
    </article>

    <!-- Panel Performa Paket -->
    <article class="panel">
        <div class="panel-heading">
            <div>
                <h2>Performa Paket</h2>
                <p class="muted">Pendapatan dan jumlah langganan per paket.</p>
            </div>
        </div>
        <div class="package-breakdown-wrap">
            <table class="data-table package-table">
                <thead>
                    <tr>
                        <th scope="col">PAKET</th>
                        <th scope="col">DURASI</th>
                        <th scope="col">TERJUAL</th>
                        <th scope="col">TOTAL OMZET</th>
                    </tr>
                </thead>
                <tbody id="packageTableBody">
                    <tr>
                        <td colspan="4" class="empty-row">Memuat data paket…</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </article>
</section>

<!-- Panel Data Terperinci dengan Tab (Transaksi & Presensi) -->
<section class="panel page-panel report-detail-panel">
    <div class="panel-heading flex-between">
        <div>
            <h2>Rincian Aktivitas</h2>
            <p class="muted" id="tabSubtitle">Daftar transaksi kasir pada periode terpilih.</p>
        </div>
        <div class="tab-segment" role="tablist" aria-label="Pilihan tabel rincian">
            <button id="tabBtnTransactions" class="tab-btn active" role="tab" aria-selected="true" aria-controls="transactionsTableView" type="button">
                <svg class="icon icon-sm" aria-hidden="true" focusable="false"><use href="#icon-receipt"/></svg>
                <span>Transaksi</span>
                <span class="tab-badge" id="txBadge">0</span>
            </button>
            <button id="tabBtnAttendance" class="tab-btn" role="tab" aria-selected="false" aria-controls="attendanceTableView" type="button">
                <svg class="icon icon-sm" aria-hidden="true" focusable="false"><use href="#icon-check-in"/></svg>
                <span>Presensi</span>
                <span class="tab-badge" id="attBadge">0</span>
            </button>
        </div>
    </div>

    <!-- Toolbar Pencarian & Filter Khusus Tab -->
    <div class="toolbar">
        <label class="visually-hidden" for="reportSearch">Cari data</label>
        <input id="reportSearch" class="search-input" type="search" placeholder="Cari nama member, kode, invoice…">

        <!-- Filter Pembayaran (tampil saat tab transaksi aktif) -->
        <label class="visually-hidden" for="filterPaymentMethod">Filter metode pembayaran</label>
        <select id="filterPaymentMethod" class="filter-select">
            <option value="all">Semua Metode</option>
            <option value="TUNAI">Tunai</option>
            <option value="QRIS">QRIS</option>
            <option value="TRANSFER">Transfer</option>
        </select>

        <!-- Filter Gender (tampil saat tab presensi aktif) -->
        <label class="visually-hidden" for="filterGender">Filter gender</label>
        <select id="filterGender" class="filter-select" style="display: none;">
            <option value="all">Semua Gender</option>
            <option value="pria">Pria</option>
            <option value="wanita">Wanita</option>
        </select>
    </div>

    <!-- Tampilan Tabel Transaksi -->
    <div id="transactionsTableView" class="table-wrap" role="tabpanel" aria-labelledby="tabBtnTransactions">
        <table class="data-table">
            <thead>
                <tr>
                    <th scope="col">NO. INVOICE</th>
                    <th scope="col">MEMBER</th>
                    <th scope="col">PAKET</th>
                    <th scope="col">WAKTU</th>
                    <th scope="col">METODE</th>
                    <th scope="col">TOTAL</th>
                </tr>
            </thead>
            <tbody id="transactionRows">
                <tr>
                    <td colspan="6" class="empty-row">Memuat transaksi…</td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Tampilan Tabel Presensi -->
    <div id="attendanceTableView" class="table-wrap" role="tabpanel" aria-labelledby="tabBtnAttendance" hidden>
        <table class="data-table">
            <thead>
                <tr>
                    <th scope="col">WAKTU CHECK-IN</th>
                    <th scope="col">MEMBER</th>
                    <th scope="col">GENDER</th>
                    <th scope="col">STATUS</th>
                </tr>
            </thead>
            <tbody id="attendanceRows">
                <tr>
                    <td colspan="4" class="empty-row">Memuat riwayat presensi…</td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="table-footer">
        <span id="recordCount" aria-live="polite">Memuat data…</span>
        <span class="report-meta-info" id="reportMetaInfo">Total Keseluruhan Omzet: <b id="metaTotalRevenue">Rp 0</b></span>
    </div>
</section>

<!-- Template Cetak Laporan (Hanya aktif saat Print / Ctrl+P) -->
<section class="print-document" id="printDocument" aria-hidden="true">
    <div class="print-header">
        <div class="print-brand">
            <h2>KELOLAGYM</h2>
            <p>Laporan Resmi Keuangan & Operasional Gym</p>
        </div>
        <div class="print-meta">
            <p><b>Periode:</b> <span id="printPeriodRange">-</span></p>
            <p><b>Tanggal Cetak:</b> <span id="printGeneratedDate">-</span></p>
            <p><b>Petugas:</b> <span id="printAdminName">Admin</span></p>
        </div>
    </div>

    <div class="print-summary-box">
        <div class="print-summary-item">
            <span>Total Omzet Periode</span>
            <strong id="printRevenue">Rp 0</strong>
        </div>
        <div class="print-summary-item">
            <span>Rata-rata Transaksi</span>
            <strong id="printAvgTx">Rp 0</strong>
        </div>
        <div class="print-summary-item">
            <span>Pemasukan Non-Tunai</span>
            <strong id="printNonCash">Rp 0</strong>
        </div>
        <div class="print-summary-item">
            <span>Kas Tunai Fisik</span>
            <strong id="printCash">Rp 0</strong>
        </div>
    </div>

    <h3 class="print-section-title">Ringkasan Metode Pembayaran</h3>
    <table class="print-table" id="printMethodTable">
        <thead>
            <tr>
                <th>Metode</th>
                <th>Jumlah Transaksi</th>
                <th>Total Nominal</th>
            </tr>
        </thead>
        <tbody id="printMethodBody"></tbody>
    </table>

    <h3 class="print-section-title">Performa Paket Langganan</h3>
    <table class="print-table" id="printPackageTable">
        <thead>
            <tr>
                <th>Paket</th>
                <th>Durasi</th>
                <th>Harga Satuan</th>
                <th>Total Terjual</th>
                <th>Total Omzet</th>
            </tr>
        </thead>
        <tbody id="printPackageBody"></tbody>
    </table>

    <h3 class="print-section-title">Rincian Transaksi Pembayaran</h3>
    <table class="print-table" id="printTxTable">
        <thead>
            <tr>
                <th>Invoice</th>
                <th>Member</th>
                <th>Paket</th>
                <th>Waktu</th>
                <th>Metode</th>
                <th>Jumlah Bayar</th>
            </tr>
        </thead>
        <tbody id="printTxBody"></tbody>
    </table>

    <div class="print-footer">
        <p>Dicetak otomatis melalui Sistem Manajemen KelolaGym · Dokumen sah operasional gym.</p>
    </div>
</section>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';
