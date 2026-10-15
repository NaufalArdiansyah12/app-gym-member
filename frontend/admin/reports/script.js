// frontend/admin/reports/script.js
const rawUser = localStorage.getItem('user');
let user;

try {
    user = rawUser ? JSON.parse(rawUser) : null;
} catch {
    localStorage.removeItem('user');
}

if (!user) {
    window.location.replace('../../login/');
} else if (user.role !== 'admin') {
    window.location.replace('../../user/dashboard/');
} else {
    const firstName = String(user.name || 'Admin').trim().split(/\s+/)[0];
    const userName = document.getElementById('userName');
    const profileName = document.getElementById('userProfileName');
    const avatar = document.getElementById('userAvatar');

    if (userName) userName.textContent = firstName;
    if (profileName) profileName.textContent = user.name || 'Admin Gym';
    if (avatar) avatar.textContent = firstName.charAt(0).toUpperCase();

    document.getElementById('btnLogout')?.addEventListener('click', () => {
        localStorage.removeItem('user');
        window.location.replace('../../login/');
    });
}

// ponytail: deteksi otomatis URL backend (PHP dev-server port 8000 vs Apache LAMPP subfolder).
const API_URL = window.location.port === '3000'
    ? 'http://127.0.0.1:8000/api/admin/reports'
    : '../../../backend/public/index.php/api/admin/reports';

// State
let reportData = null;
let currentTab = 'transactions';

// DOM Elements
const filterForm = document.getElementById('filterForm');
const startDateInput = document.getElementById('startDate');
const endDateInput = document.getElementById('endDate');
const presetButtons = document.querySelectorAll('.preset-btn');
const btnPrintReport = document.getElementById('btnPrintReport');

const statRevenue = document.getElementById('statRevenue');
const statPeriodLabel = document.getElementById('statPeriodLabel');
const statAvgTx = document.getElementById('statAvgTx');
const statTxSub = document.getElementById('statTxSub');
const statNonCash = document.getElementById('statNonCash');
const statNonCashSub = document.getElementById('statNonCashSub');
const statCash = document.getElementById('statCash');
const statCashSub = document.getElementById('statCashSub');
const metaTotalRevenue = document.getElementById('metaTotalRevenue');

const methodBreakdownList = document.getElementById('methodBreakdownList');
const packageTableBody = document.getElementById('packageTableBody');

const tabBtnTransactions = document.getElementById('tabBtnTransactions');
const tabBtnAttendance = document.getElementById('tabBtnAttendance');
const tabSubtitle = document.getElementById('tabSubtitle');
const txBadge = document.getElementById('txBadge');
const attBadge = document.getElementById('attBadge');

const reportSearch = document.getElementById('reportSearch');
const filterPaymentMethod = document.getElementById('filterPaymentMethod');
const filterGender = document.getElementById('filterGender');

const transactionsTableView = document.getElementById('transactionsTableView');
const attendanceTableView = document.getElementById('attendanceTableView');
const transactionRows = document.getElementById('transactionRows');
const attendanceRows = document.getElementById('attendanceRows');
const recordCount = document.getElementById('recordCount');
const toastEl = document.getElementById('toast');

let toastTimer;
function showToast(message) {
    if (!toastEl) return;
    toastEl.textContent = message;
    toastEl.classList.add('show');
    window.clearTimeout(toastTimer);
    toastTimer = window.setTimeout(() => toastEl.classList.remove('show'), 3000);
}

const formatRupiah = (val) => 'Rp ' + new Intl.NumberFormat('id-ID').format(Number(val) || 0);

function formatLocalDate(d) {
    const year = d.getFullYear();
    const month = String(d.getMonth() + 1).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
}

function formatIndoDate(dateStr) {
    if (!dateStr) return '-';
    const parts = dateStr.split('-');
    if (parts.length !== 3) return dateStr;
    const months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    const d = parseInt(parts[2], 10);
    const m = months[parseInt(parts[1], 10) - 1] || parts[1];
    const y = parts[0];
    return `${d} ${m} ${y}`;
}

// Inisialisasi tanggal default (Bulan Ini)
function initDefaultDates() {
    const now = new Date();
    const firstDay = new Date(now.getFullYear(), now.getMonth(), 1);
    if (startDateInput) startDateInput.value = formatLocalDate(firstDay);
    if (endDateInput) endDateInput.value = formatLocalDate(now);
}

// Preset handler
presetButtons.forEach(btn => {
    btn.addEventListener('click', () => {
        presetButtons.forEach(b => b.classList.remove('active'));
        btn.classList.add('active');

        const preset = btn.dataset.preset;
        const now = new Date();
        let start = new Date();

        if (preset === 'today') {
            start = new Date();
        } else if (preset === '7days') {
            start.setDate(now.getDate() - 6);
        } else if (preset === 'thismonth') {
            start = new Date(now.getFullYear(), now.getMonth(), 1);
        } else if (preset === '30days') {
            start.setDate(now.getDate() - 29);
        } else if (preset === 'thisyear') {
            start = new Date(now.getFullYear(), 0, 1);
        }

        if (startDateInput) startDateInput.value = formatLocalDate(start);
        if (endDateInput) endDateInput.value = formatLocalDate(now);
        loadReport();
    });
});

filterForm?.addEventListener('submit', (e) => {
    e.preventDefault();
    presetButtons.forEach(b => b.classList.remove('active'));
    loadReport();
});

// Load data dari API
async function loadReport() {
    const startDate = startDateInput ? startDateInput.value : '';
    const endDate = endDateInput ? endDateInput.value : '';

    if (!startDate || !endDate) {
        showToast('Pilih rentang tanggal terlebih dahulu.');
        return;
    }

    if (startDate > endDate) {
        showToast('Tanggal awal tidak boleh melebihi tanggal akhir.');
        return;
    }

    statPeriodLabel.textContent = 'Memuat data…';
    transactionRows.innerHTML = '<tr><td colspan="6" class="empty-row">Memuat transaksi…</td></tr>';
    attendanceRows.innerHTML = '<tr><td colspan="4" class="empty-row">Memuat riwayat presensi…</td></tr>';
    methodBreakdownList.innerHTML = '<div class="loading-placeholder">Memuat data metode pembayaran…</div>';
    packageTableBody.innerHTML = '<tr><td colspan="4" class="empty-row">Memuat data paket…</td></tr>';

    try {
        const res = await fetch(`${API_URL}?start_date=${encodeURIComponent(startDate)}&end_date=${encodeURIComponent(endDate)}`, {
            headers: {
                'Accept': 'application/json',
                'X-User-Role': user.role || 'admin'
            }
        });

        if (!res.ok) {
            throw new Error(`HTTP error ${res.status}`);
        }

        const json = await res.json();
        if (json.status !== 'success' || !json.data) {
            throw new Error(json.error || 'Gagal memuat laporan');
        }

        reportData = json.data;
        renderAll();
    } catch (err) {
        console.error('Error fetching report:', err);
        showToast('Gagal memuat data laporan dari server.');
        statPeriodLabel.textContent = 'Gagal memuat';
        transactionRows.innerHTML = '<tr><td colspan="6" class="empty-row">Gagal memuat data transaksi.</td></tr>';
        attendanceRows.innerHTML = '<tr><td colspan="4" class="empty-row">Gagal memuat data presensi.</td></tr>';
        methodBreakdownList.innerHTML = '<div class="loading-placeholder">Gagal memuat metode pembayaran.</div>';
        packageTableBody.innerHTML = '<tr><td colspan="4" class="empty-row">Gagal memuat paket.</td></tr>';
    }
}

function renderAll() {
    if (!reportData) return;

    const summary = reportData.summary || {};
    const period = reportData.period || {};

    // 1. Stats Cards
    const totalRev = Number(summary.revenue || 0);
    const txCount = Number(summary.transaction_count || 0);
    const avgTx = Number(summary.average_transaction ?? (txCount > 0 ? Math.round(totalRev / txCount) : 0));
    const cashTotal = Number(summary.cash_total ?? 0);
    const nonCashTotal = Number(summary.non_cash_total ?? 0);

    const cashPercent = totalRev > 0 ? Math.round((cashTotal / totalRev) * 100) : 0;
    const nonCashPercent = totalRev > 0 ? Math.round((nonCashTotal / totalRev) * 100) : 0;

    statRevenue.textContent = summary.revenue_formatted || formatRupiah(totalRev);
    statPeriodLabel.textContent = `${formatIndoDate(period.start_date)} - ${formatIndoDate(period.end_date)}`;

    if (statAvgTx) statAvgTx.textContent = summary.average_transaction_formatted || formatRupiah(avgTx);
    if (statTxSub) statTxSub.textContent = `${txCount} transaksi berhasil`;

    if (statNonCash) statNonCash.textContent = summary.non_cash_total_formatted || formatRupiah(nonCashTotal);
    if (statNonCashSub) statNonCashSub.textContent = `QRIS & Transfer (${nonCashPercent}%)`;

    if (statCash) statCash.textContent = summary.cash_total_formatted || formatRupiah(cashTotal);
    if (statCashSub) statCashSub.textContent = `Uang laci kasir (${cashPercent}%)`;

    if (metaTotalRevenue) {
        metaTotalRevenue.textContent = reportData.total_revenue_formatted || formatRupiah(reportData.total_revenue || 0);
    }

    // 2. Badges
    const transactions = reportData.transactions || [];
    const attendances = reportData.attendances || [];
    txBadge.textContent = transactions.length;
    attBadge.textContent = attendances.length;

    // 3. Breakdown Metode
    renderMethodBreakdown(reportData.method_stats || [], summary.revenue || 0);

    // 4. Performa Paket
    renderPackages(reportData.packages || []);

    // 5. Active Tab Records
    renderTabContent();
}

function renderMethodBreakdown(methodStats, totalRevenue) {
    if (!methodStats || methodStats.length === 0) {
        methodBreakdownList.innerHTML = '<div class="loading-placeholder">Belum ada transaksi pada periode ini.</div>';
        return;
    }

    const maxNominal = totalRevenue > 0 ? totalRevenue : methodStats.reduce((acc, m) => acc + Number(m.total_nominal || 0), 0) || 1;

    let html = '';
    methodStats.forEach(item => {
        const method = String(item.metode_pembayaran || '').toLowerCase();
        const count = Number(item.transaksi_count || 0);
        const nominal = Number(item.total_nominal || 0);
        const percent = Math.min(100, Math.round((nominal / maxNominal) * 100));

        let tagClass = 'method-tunai';
        let progressClass = 'progress-tunai';
        let label = 'Tunai';

        if (method === 'qris') {
            tagClass = 'method-qris';
            progressClass = 'progress-qris';
            label = 'QRIS';
        } else if (method === 'transfer') {
            tagClass = 'method-transfer';
            progressClass = 'progress-transfer';
            label = 'Transfer Bank';
        }

        html += `
            <div class="method-item">
                <div class="method-info-row">
                    <div class="method-label-wrap">
                        <span class="method-tag ${tagClass}">${label}</span>
                        <span class="method-counts">${count} transaksi (${percent}%)</span>
                    </div>
                    <span class="method-amount">${formatRupiah(nominal)}</span>
                </div>
                <div class="progress-track" aria-hidden="true">
                    <div class="progress-bar ${progressClass}" style="width: ${percent}%;"></div>
                </div>
            </div>
        `;
    });

    methodBreakdownList.innerHTML = html;
}

function renderPackages(packages) {
    if (!packages || packages.length === 0) {
        packageTableBody.innerHTML = '<tr><td colspan="4" class="empty-row">Belum ada data paket langganan.</td></tr>';
        return;
    }

    let html = '';
    packages.forEach(pkg => {
        const terjual = Number(pkg.total_terjual || 0);
        const pendapatan = Number(pkg.total_pendapatan || 0);

        html += `
            <tr>
                <td>
                    <b>${escapeHtml(pkg.nama_paket || '-')}</b>
                    <small>Harga: ${pkg.harga_format || formatRupiah(pkg.harga || 0)}</small>
                </td>
                <td>
                    <span class="package-badge">${pkg.durasi_hari || 0} Hari</span>
                </td>
                <td><b>${terjual}</b> kali</td>
                <td><b>${pkg.total_pendapatan_format || formatRupiah(pendapatan)}</b></td>
            </tr>
        `;
    });

    packageTableBody.innerHTML = html;
}

// Filter & Render Content Tab
function renderTabContent() {
    if (!reportData) return;

    const q = (reportSearch.value || '').toLowerCase().trim();

    if (currentTab === 'transactions') {
        const payFilter = filterPaymentMethod.value;
        const allTx = reportData.transactions || [];

        const filtered = allTx.filter(tx => {
            const matchSearch = !q ||
                (tx.invoice_code && tx.invoice_code.toLowerCase().includes(q)) ||
                (tx.member_name && tx.member_name.toLowerCase().includes(q)) ||
                (tx.member_code && tx.member_code.toLowerCase().includes(q)) ||
                (tx.member_phone && tx.member_phone.toLowerCase().includes(q)) ||
                (tx.nama_paket && tx.nama_paket.toLowerCase().includes(q));

            const matchMethod = payFilter === 'all' || String(tx.metode_pembayaran).toUpperCase() === payFilter;

            return matchSearch && matchMethod;
        });

        if (filtered.length === 0) {
            transactionRows.innerHTML = '<tr><td colspan="6" class="empty-row">Tidak ada transaksi yang cocok dengan filter.</td></tr>';
        } else {
            let html = '';
            filtered.forEach(tx => {
                const method = String(tx.metode_pembayaran || '').toLowerCase();
                let methodClass = 'method-tunai';
                if (method === 'qris') methodClass = 'method-qris';
                if (method === 'transfer') methodClass = 'method-transfer';

                html += `
                    <tr>
                        <td><b>${escapeHtml(tx.invoice_code || '-')}</b></td>
                        <td>
                            <b>${escapeHtml(tx.member_name || '-')}</b>
                            <small>${escapeHtml(tx.member_code || '-')} · ${escapeHtml(tx.member_phone || '-')}</small>
                        </td>
                        <td>
                            <b>${escapeHtml(tx.nama_paket || '-')}</b>
                            <small>${tx.durasi_hari || 0} Hari</small>
                        </td>
                        <td>${escapeHtml(tx.waktu_format || tx.dibayar_pada || '-')}</td>
                        <td><span class="method-tag ${methodClass}">${escapeHtml(tx.metode_pembayaran || '-')}</span></td>
                        <td><b>${tx.jumlah_bayar_format || formatRupiah(tx.jumlah_bayar || 0)}</b></td>
                    </tr>
                `;
            });
            transactionRows.innerHTML = html;
        }

        recordCount.textContent = `Menampilkan ${filtered.length} dari ${allTx.length} transaksi`;
    } else {
        const genderVal = filterGender.value;
        const allAtt = reportData.attendances || [];

        const filtered = allAtt.filter(att => {
            const matchSearch = !q ||
                (att.member_name && att.member_name.toLowerCase().includes(q)) ||
                (att.member_code && att.member_code.toLowerCase().includes(q));

            const matchGender = genderVal === 'all' || String(att.gender).toLowerCase() === genderVal;

            return matchSearch && matchGender;
        });

        if (filtered.length === 0) {
            attendanceRows.innerHTML = '<tr><td colspan="4" class="empty-row">Tidak ada riwayat presensi yang cocok dengan filter.</td></tr>';
        } else {
            let html = '';
            filtered.forEach(att => {
                const isFemale = String(att.gender || '').toLowerCase() === 'wanita';
                const genderClass = isFemale ? 'gender-wanita' : 'gender-pria';
                const genderLabel = isFemale ? 'Wanita' : 'Pria';
                const initial = (att.member_name || 'M').charAt(0).toUpperCase();

                html += `
                    <tr>
                        <td>
                            <b>${escapeHtml(att.jam || '')}</b>
                            <small>${escapeHtml(att.tanggal || att.waktu_presensi || '')}</small>
                        </td>
                        <td>
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <div class="avatar" style="width: 28px; height: 28px; font-size: 11px;">${initial}</div>
                                <div>
                                    <b>${escapeHtml(att.member_name || '-')}</b>
                                    <small>${escapeHtml(att.member_code || '-')}</small>
                                </div>
                            </div>
                        </td>
                        <td><span class="gender-badge ${genderClass}">${genderLabel}</span></td>
                        <td>
                            <span class="status-checkin-badge">
                                <svg class="icon icon-sm" style="width: 14px; height: 14px;" focusable="false"><use href="#icon-check"/></svg>
                                Check-in Berhasil
                            </span>
                        </td>
                    </tr>
                `;
            });
            attendanceRows.innerHTML = html;
        }

        recordCount.textContent = `Menampilkan ${filtered.length} dari ${allAtt.length} riwayat presensi`;
    }
}

// Tab Switching
tabBtnTransactions.addEventListener('click', () => {
    currentTab = 'transactions';
    tabBtnTransactions.classList.add('active');
    tabBtnTransactions.setAttribute('aria-selected', 'true');
    tabBtnAttendance.classList.remove('active');
    tabBtnAttendance.setAttribute('aria-selected', 'false');

    transactionsTableView.hidden = false;
    attendanceTableView.hidden = true;
    filterPaymentMethod.style.display = 'inline-block';
    filterGender.style.display = 'none';
    tabSubtitle.textContent = 'Daftar transaksi kasir pada periode terpilih.';

    renderTabContent();
});

tabBtnAttendance.addEventListener('click', () => {
    currentTab = 'attendance';
    tabBtnAttendance.classList.add('active');
    tabBtnAttendance.setAttribute('aria-selected', 'true');
    tabBtnTransactions.classList.remove('active');
    tabBtnTransactions.setAttribute('aria-selected', 'false');

    transactionsTableView.hidden = true;
    attendanceTableView.hidden = false;
    filterPaymentMethod.style.display = 'none';
    filterGender.style.display = 'inline-block';
    tabSubtitle.textContent = 'Daftar kedatangan dan presensi member pada periode terpilih.';

    renderTabContent();
});

// Search & Filter Listeners
reportSearch.addEventListener('input', renderTabContent);
filterPaymentMethod.addEventListener('change', renderTabContent);
filterGender.addEventListener('change', renderTabContent);

// Helper Populasi Dokumen Cetak / PDF
function populatePrintDocument() {
    if (!reportData) return false;

    const period = reportData.period || {};
    const summary = reportData.summary || {};

    // Header Meta
    const elPeriod = document.getElementById('printPeriodRange');
    const elDate = document.getElementById('printGeneratedDate');
    const elAdmin = document.getElementById('printAdminName');
    if (elPeriod) elPeriod.textContent = `${formatIndoDate(period.start_date)} s/d ${formatIndoDate(period.end_date)}`;
    if (elDate) elDate.textContent = new Date().toLocaleString('id-ID', { dateStyle: 'medium', timeStyle: 'short' });
    if (elAdmin) elAdmin.textContent = user.name || 'Admin';

    const totalRev = Number(summary.revenue || 0);
    const txCount = Number(summary.transaction_count || 0);
    const avgTx = Number(summary.average_transaction ?? (txCount > 0 ? Math.round(totalRev / txCount) : 0));
    const cashTotal = Number(summary.cash_total ?? 0);
    const nonCashTotal = Number(summary.non_cash_total ?? 0);
    const cashPercent = totalRev > 0 ? Math.round((cashTotal / totalRev) * 100) : 0;
    const nonCashPercent = totalRev > 0 ? Math.round((nonCashTotal / totalRev) * 100) : 0;

    const elRev = document.getElementById('printRevenue');
    const elAvg = document.getElementById('printAvgTx');
    const elNonCash = document.getElementById('printNonCash');
    const elCash = document.getElementById('printCash');
    if (elRev) elRev.textContent = summary.revenue_formatted || formatRupiah(totalRev);
    if (elAvg) elAvg.textContent = summary.average_transaction_formatted || formatRupiah(avgTx);
    if (elNonCash) elNonCash.textContent = (summary.non_cash_total_formatted || formatRupiah(nonCashTotal)) + ` (${nonCashPercent}%)`;
    if (elCash) elCash.textContent = (summary.cash_total_formatted || formatRupiah(cashTotal)) + ` (${cashPercent}%)`;

    // Tabel Metode Pembayaran
    const methodBody = document.getElementById('printMethodBody');
    if (methodBody) {
        const methodStats = reportData.method_stats || [];
        methodBody.innerHTML = methodStats.map(m => `
            <tr>
                <td>${escapeHtml(String(m.metode_pembayaran).toUpperCase())}</td>
                <td>${m.transaksi_count || 0} Transaksi</td>
                <td>${formatRupiah(m.total_nominal || 0)}</td>
            </tr>
        `).join('') || '<tr><td colspan="3">-</td></tr>';
    }

    // Tabel Paket
    const pkgBody = document.getElementById('printPackageBody');
    if (pkgBody) {
        const packages = reportData.packages || [];
        pkgBody.innerHTML = packages.map(p => `
            <tr>
                <td>${escapeHtml(p.nama_paket || '-')}</td>
                <td>${p.durasi_hari || 0} Hari</td>
                <td>${p.harga_format || formatRupiah(p.harga || 0)}</td>
                <td>${p.total_terjual || 0}</td>
                <td>${p.total_pendapatan_format || formatRupiah(p.total_pendapatan || 0)}</td>
            </tr>
        `).join('') || '<tr><td colspan="5">-</td></tr>';
    }

    // Tabel Transaksi
    const txBody = document.getElementById('printTxBody');
    if (txBody) {
        const txs = reportData.transactions || [];
        txBody.innerHTML = txs.map(t => `
            <tr>
                <td>${escapeHtml(t.invoice_code || '-')}</td>
                <td>${escapeHtml(t.member_name || '-')} (${escapeHtml(t.member_code || '-')})</td>
                <td>${escapeHtml(t.nama_paket || '-')}</td>
                <td>${escapeHtml(t.waktu_format || t.dibayar_pada || '-')}</td>
                <td>${escapeHtml(t.metode_pembayaran || '-')}</td>
                <td>${t.jumlah_bayar_format || formatRupiah(t.jumlah_bayar || 0)}</td>
            </tr>
        `).join('') || '<tr><td colspan="6">Tidak ada transaksi pada periode ini.</td></tr>';
    }

    return true;
}

btnPrintReport?.addEventListener('click', () => {
    if (!reportData) {
        showToast('Data laporan belum siap dicetak.');
        return;
    }
    populatePrintDocument();
    window.print();
});

function escapeHtml(str) {
    if (typeof str !== 'string') return String(str ?? '');
    return str
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

// Inisialisasi awal saat halaman dimuat
initDefaultDates();
loadReport();
