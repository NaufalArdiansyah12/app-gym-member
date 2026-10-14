// frontend/admin/reports/script.js
const rawUser = localStorage.getItem('user');

// ponytail: Deteksi otomatis URL API backend untuk dev-server port 3000 vs Apache LAMPP.
const getApiUrl = (endpoint) => {
    return window.location.port === '3000'
        ? `http://127.0.0.1:8000/api/${endpoint}`
        : `../../../backend/public/index.php/api/${endpoint}`;
};

if (!rawUser) {
    window.location.replace('../../login/');
} else {
    let user;

    try {
        user = JSON.parse(rawUser);
    } catch {
        localStorage.removeItem('user');
        window.location.replace('../../login/');
    }

    if (!user || user.role !== 'admin') {
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
}

const filterPreset = document.getElementById('filterPreset');
const filterStartDate = document.getElementById('filterStartDate');
const filterEndDate = document.getElementById('filterEndDate');
const btnApplyFilter = document.getElementById('btnApplyFilter');
const toast = document.getElementById('toast');
let toastTimer;

function showToast(message) {
    if (!toast) return;
    toast.textContent = message;
    toast.classList.add('show');
    window.clearTimeout(toastTimer);
    toastTimer = window.setTimeout(() => toast.classList.remove('show'), 3000);
}

function escapeHtml(str) {
    return String(str ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function formatDateISO(date) {
    const y = date.getFullYear();
    const m = String(date.getMonth() + 1).padStart(2, '0');
    const d = String(date.getDate()).padStart(2, '0');
    return `${y}-${m}-${d}`;
}

// Set tanggal awal berdasarkan preset terpilih
function applyPresetDates(preset) {
    const today = new Date();
    let start = new Date();
    let end = new Date();

    if (preset === 'today') {
        start = today;
        end = today;
    } else if (preset === '7days') {
        start = new Date(today);
        start.setDate(today.getDate() - 6);
        end = today;
    } else if (preset === 'month') {
        start = new Date(today.getFullYear(), today.getMonth(), 1);
        end = today;
    } else if (preset === 'last_month') {
        start = new Date(today.getFullYear(), today.getMonth() - 1, 1);
        end = new Date(today.getFullYear(), today.getMonth(), 0);
    } else if (preset === 'year') {
        start = new Date(today.getFullYear(), 0, 1);
        end = today;
    }

    if (preset !== 'custom') {
        if (filterStartDate) filterStartDate.value = formatDateISO(start);
        if (filterEndDate) filterEndDate.value = formatDateISO(end);
    }
}

// ==========================================
// 1. MEMUAT DATA LAPORAN DARI BACKEND
// ==========================================
async function loadReports() {
    const start = filterStartDate?.value || '';
    const end = filterEndDate?.value || '';

    try {
        const res = await fetch(getApiUrl(`admin/reports?start_date=${start}&end_date=${end}`), {
            credentials: 'same-origin',
        });
        const json = await res.json();

        if (json.status !== 'success' || !json.data) {
            showToast('Gagal memuat data laporan');
            return;
        }

        const data = json.data;
        const summary = data.summary || {};

        // 1. Update KPI Cards
        const elRev = document.getElementById('kpiRevenue');
        const elRevSub = document.getElementById('kpiRevenueSub');
        const elTx = document.getElementById('kpiTransactions');
        const elAtt = document.getElementById('kpiCheckins');
        const elNew = document.getElementById('kpiNewMembers');

        if (elRev) elRev.textContent = summary.revenue_formatted || 'Rp 0';
        if (elRevSub) elRevSub.textContent = `${start} s/d ${end}`;
        if (elTx) elTx.textContent = Number(summary.transaction_count || 0).toLocaleString('id-ID');
        if (elAtt) elAtt.textContent = Number(summary.checkin_count || 0).toLocaleString('id-ID');
        if (elNew) elNew.textContent = Number(summary.new_members || 0).toLocaleString('id-ID');

        // 2. Tab 1: Keuangan & Kasir
        renderFinanceTab(data);

        // 3. Tab 2: Performa Paket
        renderPackagesTab(data);

        // 4. Tab 3: Log Presensi
        renderAttendanceTab(data);

    } catch (err) {
        console.warn('Gagal mengambil laporan:', err);
        showToast('Gagal terhubung ke server backend');
    }
}

function renderFinanceTab(data) {
    const transactions = data.transactions || [];
    const methodStats = data.method_stats || [];
    const summary = data.summary || {};

    // Breakdown metode pembayaran
    let cashSum = 0, cashCount = 0;
    let transferSum = 0, transferCount = 0;
    let qrisSum = 0, qrisCount = 0;

    methodStats.forEach(m => {
        const name = (m.metode_pembayaran || '').toLowerCase();
        const total = Number(m.total_nominal || 0);
        const count = Number(m.transaksi_count || 0);
        if (name === 'tunai') {
            cashSum = total;
            cashCount = count;
        } else if (name === 'transfer') {
            transferSum = total;
            transferCount = count;
        } else if (name === 'qris') {
            qrisSum = total;
            qrisCount = count;
        }
    });

    const chipTunai = document.getElementById('chipTunai');
    const chipTransfer = document.getElementById('chipTransfer');
    const chipQris = document.getElementById('chipQris');

    if (chipTunai) chipTunai.textContent = `Rp ${cashSum.toLocaleString('id-ID')} (${cashCount} trx)`;
    if (chipTransfer) chipTransfer.textContent = `Rp ${transferSum.toLocaleString('id-ID')} (${transferCount} trx)`;
    if (chipQris) chipQris.textContent = `Rp ${qrisSum.toLocaleString('id-ID')} (${qrisCount} trx)`;

    // Render Tabel Transaksi
    const body = document.getElementById('tableFinanceBody');
    const totalEl = document.getElementById('tableFinanceTotal');

    if (totalEl) totalEl.textContent = summary.revenue_formatted || 'Rp 0';

    if (body) {
        if (!Array.isArray(transactions) || transactions.length === 0) {
            body.innerHTML = '<tr><td colspan="6" class="table-empty">Tidak ada transaksi pada periode yang dipilih</td></tr>';
        } else {
            body.innerHTML = transactions.map(t => `
                <tr>
                    <td><b style="color: #ff9142; font-family: monospace;">${escapeHtml(t.invoice_code)}</b></td>
                    <td>${escapeHtml(t.waktu_format)}</td>
                    <td>
                        <b>${escapeHtml(t.member_name)}</b>
                        <small style="display:block; color:#9ca3af; font-family:monospace;">${escapeHtml(t.member_code)}</small>
                    </td>
                    <td>${escapeHtml(t.nama_paket)}</td>
                    <td><span class="badge-method">${escapeHtml(t.metode_pembayaran)}</span></td>
                    <td style="text-align: right; font-weight: 700; color: #4ade80;">${escapeHtml(t.jumlah_bayar_format)}</td>
                </tr>
            `).join('');
        }
    }
}

function renderPackagesTab(data) {
    const packages = data.packages || [];
    const body = document.getElementById('tablePackagesBody');

    if (body) {
        if (!Array.isArray(packages) || packages.length === 0) {
            body.innerHTML = '<tr><td colspan="5" class="table-empty">Belum ada data penjualan paket pada periode ini</td></tr>';
        } else {
            body.innerHTML = packages.map(p => `
                <tr>
                    <td><b>${escapeHtml(p.nama_paket)}</b></td>
                    <td>${escapeHtml(p.durasi_hari)} Hari</td>
                    <td style="text-align: right;">${escapeHtml(p.harga_format)}</td>
                    <td style="text-align: center;"><b style="color: #ff9142;">${Number(p.total_terjual).toLocaleString('id-ID')}</b> paket</td>
                    <td style="text-align: right; font-weight: 700; color: #4ade80;">${escapeHtml(p.total_pendapatan_format)}</td>
                </tr>
            `).join('');
        }
    }
}

function renderAttendanceTab(data) {
    const attendances = data.attendances || [];
    const body = document.getElementById('tableAttendanceBody');

    if (body) {
        if (!Array.isArray(attendances) || attendances.length === 0) {
            body.innerHTML = '<tr><td colspan="5" class="table-empty">Tidak ada log presensi member pada periode ini</td></tr>';
        } else {
            body.innerHTML = attendances.map(a => `
                <tr>
                    <td>${escapeHtml(a.tanggal)}</td>
                    <td><b style="color: #4ade80;">${escapeHtml(a.jam)}</b></td>
                    <td><span style="color:#ff9142; font-family:monospace; font-weight:600;">${escapeHtml(a.member_code)}</span></td>
                    <td><b>${escapeHtml(a.member_name)}</b></td>
                    <td><span class="status ${a.gender === 'wanita' ? 'pink-bg' : 'blue-bg'}">${escapeHtml(a.gender_label)}</span></td>
                </tr>
            `).join('');
        }
    }
}

// ==========================================
// 2. NAVIGASI TAB LAPORAN
// ==========================================
const tabButtons = document.querySelectorAll('.report-tab-btn');
const tabPanes = document.querySelectorAll('.report-tab-pane');

tabButtons.forEach(btn => {
    btn.addEventListener('click', () => {
        tabButtons.forEach(b => {
            b.classList.remove('active');
            b.setAttribute('aria-selected', 'false');
        });
        tabPanes.forEach(p => {
            p.classList.remove('active');
            p.hidden = true;
        });

        btn.classList.add('active');
        btn.setAttribute('aria-selected', 'true');
        const targetId = btn.dataset.tab;
        const targetPane = document.getElementById(targetId);
        if (targetPane) {
            targetPane.classList.add('active');
            targetPane.hidden = false;
        }
    });
});

// ==========================================
// 3. EVENT FILTER PERIODE
// ==========================================
filterPreset?.addEventListener('change', () => {
    const val = filterPreset.value;
    applyPresetDates(val);
    loadReports();
});

btnApplyFilter?.addEventListener('click', () => {
    loadReports();
});

// Inisialisasi awal (default Bulan Ini)
applyPresetDates('month');
loadReports();

