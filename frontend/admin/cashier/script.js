// frontend/admin/cashier/script.js
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
    ? 'http://127.0.0.1:8000/api/admin/cashier'
    : '../../../backend/public/index.php/api/admin/cashier';

// DOM Elements
const modalAddTransaction = document.getElementById('modalAddTransaction');
const btnOpenAddTransaction = document.getElementById('btnOpenAddTransaction');
const btnCloseModal = document.getElementById('btnCloseModal');
const btnCancelModal = document.getElementById('btnCancelModal');
const formAddTransaction = document.getElementById('formAddTransaction');

const labelTypeBaru = document.getElementById('labelTypeBaru');
const labelTypePerpanjang = document.getElementById('labelTypePerpanjang');
const radioTypeBaru = labelTypeBaru?.querySelector('input');
const radioTypePerpanjang = labelTypePerpanjang?.querySelector('input');
const summaryTypeLabel = document.getElementById('summaryTypeLabel');
const previewPeriod = document.getElementById('previewPeriod');

const memberSearchInput = document.getElementById('memberSearchInput');
const selectedUserId = document.getElementById('selectedUserId');
const memberSearchResults = document.getElementById('memberSearchResults');

const packageSelect = document.getElementById('packageSelect');
const packageCustomSelect = document.getElementById('packageCustomSelect');
const packageSelectTrigger = document.getElementById('packageSelectTrigger');
const packageTriggerLabel = document.getElementById('packageTriggerLabel');
const packageSelectMenu = document.getElementById('packageSelectMenu');

const paymentMethod = document.getElementById('paymentMethod');
const amountPaidInput = document.getElementById('amountPaid');
const memberInfo = document.getElementById('memberInfo');
const packageInfo = document.getElementById('packageInfo');
const packagePriceEl = document.getElementById('packagePrice');
const changeRow = document.getElementById('changeRow');
const estimatedChangeEl = document.getElementById('estimatedChange');
const formMessage = document.getElementById('formMessage');
const btnSubmitTransaction = document.getElementById('btnSubmitTransaction');

const searchInput = document.getElementById('transactionSearch');
const paymentFilter = document.getElementById('paymentFilter');
const transactionRows = document.getElementById('transactionRows');
const transactionCount = document.getElementById('transactionCount');

const modalReceipt = document.getElementById('modalReceipt');
const btnCloseReceipt = document.getElementById('btnCloseReceipt');
const btnDoneReceipt = document.getElementById('btnDoneReceipt');
const btnPrintReceipt = document.getElementById('btnPrintReceipt');
const receiptContent = document.getElementById('receiptContent');

// State
let packages = [];
let members = [];
let transactions = [];

const formatRupiah = (val) => 'Rp ' + new Intl.NumberFormat('id-ID').format(Number(val) || 0);

const escapeHTML = (str) => String(str ?? '').replace(/[&<>"']/g, (c) => ({
    '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
}[c]));

function debounce(fn, delay = 250) {
    let timer;
    return (...args) => {
        clearTimeout(timer);
        timer = setTimeout(() => fn(...args), delay);
    };
}

function getTodayString() {
    return new Date().toISOString().slice(0, 10);
}

function addDaysToDate(dateStr, days) {
    const [year, month, day] = dateStr.split('-').map(Number);
    const d = new Date(Date.UTC(year, month - 1, day));
    d.setUTCDate(d.getUTCDate() + Number(days));
    return d.toISOString().slice(0, 10);
}

async function apiRequest(endpoint, options = {}) {
    const res = await fetch(endpoint, options);
    const data = await res.json();
    if (!res.ok) {
        throw new Error(data.error || 'Terjadi kesalahan sistem.');
    }
    return data;
}

// Modal Control
function openModal(modal, focusTarget) {
    if (!modal) return;
    modal.classList.add('is-active');
    modal.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';
    window.setTimeout(() => focusTarget?.focus(), 80);
}

function closeModal(modal, restoreTarget) {
    if (!modal) return;
    modal.classList.remove('is-active');
    modal.setAttribute('aria-hidden', 'true');
    document.body.style.overflow = '';
    closePackageMenu();
    if (memberSearchResults) memberSearchResults.hidden = true;
    restoreTarget?.focus();
}

btnOpenAddTransaction?.addEventListener('click', () => {
    formMessage.textContent = '';
    formMessage.className = 'form-message';
    selectedUserId.value = '';
    memberSearchInput.value = '';
    selectPackage(null);
    if (memberSearchResults) memberSearchResults.hidden = true;
    updateMemberDetails();
    openModal(modalAddTransaction, memberSearchInput);
});

btnCloseModal?.addEventListener('click', () => closeModal(modalAddTransaction, btnOpenAddTransaction));
btnCancelModal?.addEventListener('click', () => closeModal(modalAddTransaction, btnOpenAddTransaction));
modalAddTransaction?.addEventListener('click', (e) => {
    if (e.target === modalAddTransaction) closeModal(modalAddTransaction, btnOpenAddTransaction);
});

btnCloseReceipt?.addEventListener('click', () => closeModal(modalReceipt, btnOpenAddTransaction));
btnDoneReceipt?.addEventListener('click', () => closeModal(modalReceipt, btnOpenAddTransaction));
modalReceipt?.addEventListener('click', (e) => {
    if (e.target === modalReceipt) closeModal(modalReceipt, btnOpenAddTransaction);
});

document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        if (!packageSelectMenu.hidden) {
            closePackageMenu();
            return;
        }
        if (memberSearchResults && !memberSearchResults.hidden) {
            memberSearchResults.hidden = true;
            return;
        }
        if (modalAddTransaction?.classList.contains('is-active')) {
            closeModal(modalAddTransaction, btnOpenAddTransaction);
        } else if (modalReceipt?.classList.contains('is-active')) {
            closeModal(modalReceipt, btnOpenAddTransaction);
        }
    }
});

btnPrintReceipt?.addEventListener('click', () => window.print());

// Custom Liquid Glass Dropdown (Pilih Paket)
function renderPackageSelectMenu() {
    if (!packages.length) {
        packageSelectMenu.innerHTML = '<div class="custom-select-option" style="color:#9ca3af;">Memuat paket…</div>';
        return;
    }

    packageSelectMenu.innerHTML = packages.map((p) => {
        const isSelected = String(p.id) === packageSelect.value;
        return `
            <div class="custom-select-option ${isSelected ? 'is-selected' : ''}" data-id="${p.id}" role="option" aria-selected="${isSelected}">
                <div class="option-left">
                    <span class="option-title">${escapeHTML(p.nama_paket)}</span>
                    <span class="option-duration">${Number(p.durasi_hari)} Hari</span>
                </div>
                <span class="option-price">${formatRupiah(p.harga)}</span>
            </div>
        `;
    }).join('');

    packageSelectMenu.querySelectorAll('.custom-select-option').forEach((opt) => {
        opt.addEventListener('click', (e) => {
            e.stopPropagation();
            const id = Number(opt.dataset.id);
            selectPackage(id);
        });
    });
}

function selectPackage(id) {
    const selected = packages.find((p) => Number(p.id) === Number(id));
    if (selected) {
        packageSelect.value = String(selected.id);
        packageTriggerLabel.textContent = `${selected.nama_paket} — ${formatRupiah(selected.harga)}`;
    } else {
        packageSelect.value = '';
        packageTriggerLabel.textContent = '— Pilih Paket Langganan —';
    }

    packageSelectMenu.querySelectorAll('.custom-select-option').forEach((opt) => {
        const isCurrent = Number(opt.dataset.id) === Number(id);
        opt.classList.toggle('is-selected', isCurrent);
        opt.setAttribute('aria-selected', isCurrent ? 'true' : 'false');
    });

    closePackageMenu();
    updatePackageDetails();
}

function openPackageMenu() {
    packageSelectMenu.hidden = false;
    packageSelectTrigger.classList.add('is-open');
    packageSelectTrigger.setAttribute('aria-expanded', 'true');
}

function closePackageMenu() {
    packageSelectMenu.hidden = true;
    packageSelectTrigger.classList.remove('is-open');
    packageSelectTrigger.setAttribute('aria-expanded', 'false');
}

function togglePackageMenu() {
    if (packageSelectMenu.hidden) {
        openPackageMenu();
    } else {
        closePackageMenu();
    }
}

packageSelectTrigger?.addEventListener('click', (e) => {
    e.stopPropagation();
    if (memberSearchResults) memberSearchResults.hidden = true;
    togglePackageMenu();
});

document.addEventListener('click', (e) => {
    if (!e.target.closest('#packageCustomSelect')) {
        closePackageMenu();
    }
});

// Transaction Type Selection
function getSelectedTransactionType() {
    return radioTypePerpanjang?.checked ? 'perpanjang' : 'baru';
}

function setTransactionType(type) {
    if (type === 'perpanjang') {
        if (radioTypePerpanjang) radioTypePerpanjang.checked = true;
        labelTypePerpanjang?.classList.add('is-active');
        labelTypeBaru?.classList.remove('is-active');
        if (summaryTypeLabel) summaryTypeLabel.textContent = 'Perpanjang Paket';
    } else {
        if (radioTypeBaru) radioTypeBaru.checked = true;
        labelTypeBaru?.classList.add('is-active');
        labelTypePerpanjang?.classList.remove('is-active');
        if (summaryTypeLabel) summaryTypeLabel.textContent = 'Beli Paket Baru';
    }
    updateEstimatedPeriod();
}

labelTypeBaru?.addEventListener('click', () => setTransactionType('baru'));
labelTypePerpanjang?.addEventListener('click', () => setTransactionType('perpanjang'));

// Estimated Period Calculation
function updateEstimatedPeriod() {
    const selectedMember = members.find((m) => String(m.id) === selectedUserId.value);
    const selectedPkg = packages.find((p) => String(p.id) === packageSelect.value);
    const type = getSelectedTransactionType();
    const today = getTodayString();

    if (!selectedPkg) {
        if (previewPeriod) previewPeriod.textContent = '-';
        return;
    }

    const duration = Number(selectedPkg.durasi_hari) || 0;
    let startDate = today;

    if (type === 'perpanjang' && selectedMember?.membership_expires_at && selectedMember.membership_expires_at >= today) {
        startDate = addDaysToDate(selectedMember.membership_expires_at, 1);
    }

    const endDate = addDaysToDate(startDate, duration);
    if (previewPeriod) {
        previewPeriod.textContent = `${startDate} s/d ${endDate} (${duration} Hari)`;
    }
}

// Member Search Autocomplete with Debounce
function renderMemberSearchResults(list) {
    if (!list.length) {
        memberSearchResults.innerHTML = '<div class="search-result-empty">Member tidak ditemukan</div>';
        memberSearchResults.hidden = false;
        return;
    }

    memberSearchResults.innerHTML = list.map((m) => `
        <div class="search-result-item" data-id="${m.id}">
            <div>
                <div class="item-title">${escapeHTML(m.name)}</div>
                <div class="item-sub">${escapeHTML(m.member_code || 'Tanpa Kode')} · ${escapeHTML(m.phone || '-')}</div>
            </div>
            <span class="method-tag method-${m.membership_status === 'active' ? 'tunai' : 'qris'}">${escapeHTML(m.membership_status)}</span>
        </div>
    `).join('');

    memberSearchResults.querySelectorAll('.search-result-item').forEach((item) => {
        item.addEventListener('click', () => {
            const id = Number(item.dataset.id);
            const found = members.find((m) => Number(m.id) === id);
            if (found) {
                selectMember(found);
            }
        });
    });

    memberSearchResults.hidden = false;
}

function selectMember(m) {
    selectedUserId.value = String(m.id);
    memberSearchInput.value = `${m.name} (${m.member_code || 'GYM-NEW'})`;
    memberSearchResults.hidden = true;
    updateMemberDetails();
}

const performMemberSearch = debounce(async (query) => {
    const q = query.trim().toLowerCase();
    if (!q) {
        memberSearchResults.hidden = true;
        memberSearchResults.innerHTML = '';
        return;
    }

    const matched = members.filter((m) =>
        (m.name || '').toLowerCase().includes(q) ||
        (m.member_code || '').toLowerCase().includes(q) ||
        (m.phone || '').includes(q)
    );

    if (matched.length > 0) {
        renderMemberSearchResults(matched);
        return;
    }

    try {
        const res = await apiRequest(`${API_URL}/members?q=${encodeURIComponent(q)}`);
        renderMemberSearchResults(res.data || []);
    } catch {
        renderMemberSearchResults([]);
    }
}, 250);

memberSearchInput?.addEventListener('input', (e) => {
    selectedUserId.value = '';
    updateMemberDetails();
    performMemberSearch(e.target.value);
});

memberSearchInput?.addEventListener('focus', () => {
    if (memberSearchInput.value.trim() && !selectedUserId.value) {
        performMemberSearch(memberSearchInput.value);
    }
});

document.addEventListener('click', (e) => {
    if (!e.target.closest('.member-search-box')) {
        if (memberSearchResults) memberSearchResults.hidden = true;
    }
});

// Form Updates
function updateMemberDetails() {
    const selected = members.find((m) => String(m.id) === selectedUserId.value);
    if (!selected) {
        memberInfo.hidden = true;
        memberInfo.innerHTML = '';
        updateEstimatedPeriod();
        return;
    }

    const today = getTodayString();
    const hasActivePlan = selected.membership_status === 'active' && selected.membership_expires_at && selected.membership_expires_at >= today;

    // Otomatis arahkan ke perpanjang jika member masih aktif, atau beli baru jika expired/baru
    if (hasActivePlan) {
        setTransactionType('perpanjang');
    } else {
        setTransactionType('baru');
    }

    const expiryText = selected.membership_expires_at
        ? `Masa aktif s/d: <strong>${escapeHTML(selected.membership_expires_at)}</strong>`
        : 'Belum ada membership aktif';

    const infoIcon = '<svg class="icon icon-inline" style="display:inline-block; vertical-align:-2px; width:14px; height:14px; margin-right:4px;"><use href="#icon-info"/></svg>';
    const recommendation = hasActivePlan
        ? `${infoIcon}Member aktif: Pilihan <strong>Perpanjang Paket</strong> otomatis melanjutkan tanggal aktif tanpa potong sisa hari.`
        : `${infoIcon}Member baru / expired: Paket akan aktif mulai hari ini.`;

    memberInfo.hidden = false;
    memberInfo.innerHTML = `
        <strong>${escapeHTML(selected.name)}</strong> · ${escapeHTML(selected.member_code || 'GYM-NEW')}<br>
        Status: <span class="method-tag method-${hasActivePlan ? 'tunai' : 'qris'}">${escapeHTML(selected.membership_status)}</span> · ${expiryText}<br>
        <small style="color: #93c5fd; display: inline-block; margin-top: 4px;">${recommendation}</small>
    `;

    updateEstimatedPeriod();
}

function updatePackageDetails() {
    const selected = packages.find((p) => String(p.id) === packageSelect.value);
    if (!selected) {
        packageInfo.hidden = true;
        packageInfo.innerHTML = '';
        packagePriceEl.textContent = 'Rp 0';
        amountPaidInput.value = '';
        amountPaidInput.min = '0';
        updateChange();
        updateEstimatedPeriod();
        return;
    }

    const price = Number(selected.harga) || 0;
    packageInfo.hidden = false;
    packageInfo.innerHTML = `
        <strong>${escapeHTML(selected.nama_paket)}</strong> · Durasi: ${Number(selected.durasi_hari)} Hari<br>
        Harga: <strong>${formatRupiah(price)}</strong>
    `;

    packagePriceEl.textContent = formatRupiah(price);
    amountPaidInput.min = String(price);
    amountPaidInput.value = String(price);
    updateChange();
    updateEstimatedPeriod();
}

function updateChange() {
    const selected = packages.find((p) => String(p.id) === packageSelect.value);
    const price = selected ? Number(selected.harga) : 0;
    const paid = Number(amountPaidInput.value) || 0;
    const change = Math.max(0, paid - price);

    if (paid > price && price > 0) {
        changeRow.hidden = false;
        estimatedChangeEl.textContent = formatRupiah(change);
    } else {
        changeRow.hidden = true;
    }
}

amountPaidInput?.addEventListener('input', updateChange);

// Render Functions
function renderSummary(summary = {}) {
    document.getElementById('todayCount').textContent = summary.today_count ?? 0;
    document.getElementById('todayRevenue').textContent = formatRupiah(summary.today_revenue ?? 0);
    document.getElementById('cashTotal').textContent = formatRupiah(summary.cash_total ?? 0);
    document.getElementById('nonCashTotal').textContent = formatRupiah(summary.non_cash_total ?? 0);
}

function renderTransactions() {
    const query = (searchInput?.value || '').trim().toLowerCase();
    const filter = paymentFilter?.value || 'all';

    const filtered = transactions.filter((t) => {
        const matchesQuery = !query ||
            (t.member_name || '').toLowerCase().includes(query) ||
            (t.member_code || '').toLowerCase().includes(query) ||
            (t.nama_paket || '').toLowerCase().includes(query);

        const matchesFilter = filter === 'all' || (t.metode_pembayaran || '').toLowerCase() === filter;
        return matchesQuery && matchesFilter;
    });

    transactionCount.textContent = `Menampilkan ${filtered.length} dari ${transactions.length} transaksi`;

    if (!filtered.length) {
        transactionRows.innerHTML = '<tr><td colspan="7" class="empty-row">Tidak ada transaksi ditemukan.</td></tr>';
        return;
    }

    transactionRows.innerHTML = filtered.map((t) => {
        const invoiceCode = 'TRX-' + (t.dibayar_pada ? t.dibayar_pada.slice(0, 10).replaceAll('-', '') : '') + '-' + String(t.id).padStart(4, '0');
        const formattedDate = t.dibayar_pada
            ? new Date(t.dibayar_pada.replace(' ', 'T')).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' })
            : '-';

        return `
            <tr>
                <td><b>${invoiceCode}</b></td>
                <td>
                    <b>${escapeHTML(t.member_name)}</b>
                    <small>${escapeHTML(t.member_code || '-')}</small>
                </td>
                <td>
                    <b>${escapeHTML(t.nama_paket)}</b>
                    <small>${Number(t.durasi_hari || 0)} Hari</small>
                </td>
                <td>${formattedDate}</td>
                <td>
                    <span class="method-tag method-${escapeHTML(t.metode_pembayaran)}">${escapeHTML(t.metode_pembayaran)}</span>
                </td>
                <td><b>${formatRupiah(t.jumlah_bayar)}</b></td>
                <td>
                    <button class="btn-icon-receipt" type="button" data-trx-id="${t.id}" title="Lihat Struk">Struk</button>
                </td>
            </tr>
        `;
    }).join('');

    // Attach click listeners to view receipt buttons
    transactionRows.querySelectorAll('.btn-icon-receipt').forEach((btn) => {
        btn.addEventListener('click', () => {
            const trxId = Number(btn.dataset.trxId);
            const found = transactions.find((t) => Number(t.id) === trxId);
            if (found) {
                openReceiptModal({
                    invoice_code: 'TRX-' + (found.dibayar_pada ? found.dibayar_pada.slice(0, 10).replaceAll('-', '') : '') + '-' + String(found.id).padStart(4, '0'),
                    member: {
                        name: found.member_name,
                        member_code: found.member_code,
                    },
                    paket: {
                        nama_paket: found.nama_paket,
                        durasi_hari: found.durasi_hari,
                        harga: found.harga_paket,
                    },
                    tipe_transaksi: 'Transaksi Membership',
                    tanggal_mulai: found.tanggal_mulai,
                    tanggal_selesai: found.tanggal_selesai,
                    metode_pembayaran: found.metode_pembayaran,
                    jumlah_bayar: found.jumlah_bayar,
                    kembalian: Math.max(0, Number(found.jumlah_bayar) - Number(found.harga_paket)),
                    dibayar_pada: found.dibayar_pada,
                });
            }
        });
    });
}

function openReceiptModal(data) {
    const formattedDate = data.dibayar_pada
        ? new Date(data.dibayar_pada.replace(' ', 'T')).toLocaleString('id-ID', { dateStyle: 'medium', timeStyle: 'short' })
        : new Date().toLocaleString('id-ID');

    receiptContent.innerHTML = `
        <div class="receipt-line"><span>No. Invoice</span><strong>${escapeHTML(data.invoice_code)}</strong></div>
        <div class="receipt-line"><span>Tipe Transaksi</span><strong>${escapeHTML(data.tipe_transaksi || 'Transaksi Membership')}</strong></div>
        <div class="receipt-line"><span>Waktu Transaksi</span><span>${formattedDate}</span></div>
        <div class="receipt-divider"></div>
        <div class="receipt-line"><span>Nama Member</span><strong>${escapeHTML(data.member.name)}</strong></div>
        <div class="receipt-line"><span>Kode Member</span><span>${escapeHTML(data.member.member_code || '-')}</span></div>
        <div class="receipt-divider"></div>
        <div class="receipt-line"><span>Paket Membership</span><strong>${escapeHTML(data.paket.nama_paket)}</strong></div>
        <div class="receipt-line"><span>Masa Berlaku</span><span>${escapeHTML(data.tanggal_mulai)} s/d ${escapeHTML(data.tanggal_selesai)}</span></div>
        <div class="receipt-divider"></div>
        <div class="receipt-line"><span>Metode Bayar</span><span class="method-tag method-${escapeHTML(data.metode_pembayaran)}">${escapeHTML(data.metode_pembayaran)}</span></div>
        <div class="receipt-line"><span>Harga Paket</span><span>${formatRupiah(data.paket.harga)}</span></div>
        <div class="receipt-line"><span>Nominal Bayar</span><strong>${formatRupiah(data.jumlah_bayar)}</strong></div>
        <div class="receipt-divider"></div>
        <div class="receipt-line receipt-total"><span>Kembalian</span><span>${formatRupiah(data.kembalian)}</span></div>
    `;

    openModal(modalReceipt, btnPrintReceipt);
}

// Data Fetching
async function loadCashierData() {
    try {
        const response = await apiRequest(API_URL);
        const data = response.data || {};

        packages = data.packages || [];
        members = data.members || [];
        transactions = data.transactions || [];

        renderSummary(data.summary || {});
        renderPackageSelectMenu();

        updateMemberDetails();
        updatePackageDetails();
        renderTransactions();
    } catch (err) {
        transactionRows.innerHTML = `<tr><td colspan="7" class="empty-row error">${escapeHTML(err.message)}</td></tr>`;
        transactionCount.textContent = 'Gagal memuat data';
    }
}

// Search and Filter Events
searchInput?.addEventListener('input', renderTransactions);
paymentFilter?.addEventListener('change', renderTransactions);

// Submit Transaction
formAddTransaction?.addEventListener('submit', async (e) => {
    e.preventDefault();

    const userId = Number(selectedUserId.value);
    const selectedPkg = packages.find((p) => String(p.id) === packageSelect.value);
    const amount = Number(amountPaidInput.value) || 0;
    const type = getSelectedTransactionType();

    if (!userId) {
        formMessage.textContent = 'Silakan cari dan pilih member terlebih dahulu.';
        formMessage.className = 'form-message error';
        memberSearchInput.focus();
        return;
    }

    if (!selectedPkg) {
        formMessage.textContent = 'Silakan pilih paket langganan.';
        formMessage.className = 'form-message error';
        packageSelectTrigger?.focus();
        return;
    }

    if (amount < Number(selectedPkg.harga)) {
        formMessage.textContent = `Nominal bayar kurang dari harga paket (${formatRupiah(selectedPkg.harga)}).`;
        formMessage.className = 'form-message error';
        return;
    }

    btnSubmitTransaction.disabled = true;
    btnSubmitTransaction.textContent = 'Memproses Transaksi…';
    formMessage.textContent = '';
    formMessage.className = 'form-message';

    try {
        const res = await apiRequest(`${API_URL}/transactions`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                user_id: userId,
                paket_id: Number(packageSelect.value),
                tipe_transaksi: type,
                metode_pembayaran: paymentMethod.value,
                jumlah_bayar: amount,
            }),
        });

        closeModal(modalAddTransaction, btnOpenAddTransaction);
        formAddTransaction.reset();
        selectedUserId.value = '';
        memberSearchInput.value = '';
        selectPackage(null);
        setTransactionType('baru');
        updateMemberDetails();
        updatePackageDetails();

        // Refresh list & summary
        await loadCashierData();

        // Open Receipt Modal
        if (res.data) {
            openReceiptModal(res.data);
        }
    } catch (err) {
        formMessage.textContent = err.message;
        formMessage.className = 'form-message error';
    } finally {
        btnSubmitTransaction.disabled = false;
        btnSubmitTransaction.textContent = 'Proses & Bayar';
    }
});

// Init
if (user?.role === 'admin') {
    loadCashierData();
}
