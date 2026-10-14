// frontend/admin/member-detail/script.js
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

// Ambil ID member dari parameter URL
const urlParams = new URLSearchParams(window.location.search);
const memberId = parseInt(urlParams.get('id'), 10);

if (!memberId || isNaN(memberId)) {
    window.location.replace('../members/');
}

// Toast helper
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

// ponytail: Render QR Code menggunakan lib lokal qrcodejs dengan fallback ke API gambar.
function renderMemberQr(container, code, size = 150) {
    if (!container || !code) return;
    container.innerHTML = '';
    if (typeof QRCode !== 'undefined') {
        try {
            new QRCode(container, {
                text: code,
                width: size,
                height: size,
                colorDark: '#0f172a',
                colorLight: '#ffffff',
                correctLevel: QRCode.CorrectLevel.M
            });
            return;
        } catch { /* Fallback */ }
    }
    const img = document.createElement('img');
    img.src = `https://api.qrserver.com/v1/create-qr-code/?size=${size}x${size}&data=${encodeURIComponent(code)}`;
    img.alt = `QR Code ${code}`;
    img.width = size;
    img.height = size;
    img.style.width = `${size}px`;
    img.style.height = `${size}px`;
    img.style.display = 'block';
    img.style.borderRadius = '4px';
    container.appendChild(img);
}

let currentMemberData = null;

// Muat data detail member
async function loadMemberDetail() {
    try {
        const res = await fetch(getApiUrl(`admin/members/${memberId}`), {
            credentials: 'same-origin',
        });
        const json = await res.json();

        if (!res.ok || json.status !== 'success' || !json.data) {
            showToast(json.error || 'Data member tidak ditemukan');
            setTimeout(() => window.location.replace('../members/'), 1500);
            return;
        }

        const { member, stats, subscriptions, transactions, attendances } = json.data;
        currentMemberData = member;

        // 1. Header Profil
        const avatarEl = document.getElementById('detailAvatar');
        const nameEl = document.getElementById('detailName');
        const badgeEl = document.getElementById('detailBadge');
        const codeEl = document.getElementById('detailCode');
        const createdEl = document.getElementById('detailCreatedAt');

        if (avatarEl) avatarEl.textContent = member.initials || 'M';
        if (nameEl) nameEl.textContent = member.name;
        if (badgeEl) {
            badgeEl.textContent = member.status_label || member.membership_status;
            badgeEl.className = `status ${member.status_badge_class || 'muted-bg'}`;
        }
        if (codeEl) codeEl.textContent = member.member_code;
        if (createdEl) createdEl.textContent = member.created_at_formatted || '-';

        // 2. Informasi Pribadi
        document.getElementById('detailPhone').textContent = member.phone || '-';
        document.getElementById('detailEmail').textContent = member.email || '-';
        document.getElementById('detailGender').textContent = member.gender === 'wanita' ? 'Wanita' : 'Pria';
        document.getElementById('detailEmergency').textContent = member.emergency_contact || '-';
        document.getElementById('detailAddress').textContent = member.address || '-';

        // 3. Ringkasan Membership
        document.getElementById('detailActivePackage').textContent = member.active_package_name || '-';
        document.getElementById('detailExpiresAt').textContent = member.expires_at_formatted || 'Belum diatur';
        document.getElementById('detailTotalCheckins').textContent = Number(stats.total_checkins || 0).toLocaleString('id-ID');
        document.getElementById('detailTotalSpent').textContent = stats.total_spent_formatted || 'Rp 0';

        // 3b. Render QR Code Absensi
        renderMemberQr(document.getElementById('detailMemberQr'), member.member_code, 150);
        const qrCodeEl = document.getElementById('detailMemberQrCode');
        if (qrCodeEl) qrCodeEl.textContent = member.member_code;

        // 4. Tab Counters
        document.getElementById('countSubscriptions').textContent = subscriptions.length;
        document.getElementById('countTransactions').textContent = transactions.length;
        document.getElementById('countAttendances').textContent = attendances.length;

        // 5. Tabel Riwayat Langganan
        const subTable = document.getElementById('tableSubscriptionsBody');
        if (subTable) {
            if (subscriptions.length === 0) {
                subTable.innerHTML = '<tr><td colspan="6" class="table-empty">Belum ada riwayat langganan</td></tr>';
            } else {
                subTable.innerHTML = subscriptions.map(s => {
                    const statusClass = s.status === 'aktif' ? 'success-bg' : (s.status === 'kedaluwarsa' ? 'danger-bg' : 'warning-bg');
                    const statusLabel = s.status === 'aktif' ? 'Aktif' : (s.status === 'kedaluwarsa' ? 'Kedaluwarsa' : 'Menunggu');
                    return `
                        <tr>
                            <td><b>${escapeHtml(s.nama_paket)}</b></td>
                            <td>${s.durasi_hari} Hari</td>
                            <td>${escapeHtml(s.harga_formatted)}</td>
                            <td>${escapeHtml(s.tanggal_mulai_formatted)}</td>
                            <td>${escapeHtml(s.tanggal_selesai_formatted)}</td>
                            <td><span class="status ${statusClass}">${statusLabel}</span></td>
                        </tr>
                    `;
                }).join('');
            }
        }

        // 6. Tabel Riwayat Transaksi
        const txTable = document.getElementById('tableTransactionsBody');
        if (txTable) {
            if (transactions.length === 0) {
                txTable.innerHTML = '<tr><td colspan="5" class="table-empty">Belum ada riwayat transaksi kasir</td></tr>';
            } else {
                txTable.innerHTML = transactions.map(t => `
                    <tr>
                        <td>#${t.id}</td>
                        <td><b>${escapeHtml(t.nama_paket)}</b></td>
                        <td style="color: #ff9142; font-weight: 600;">${escapeHtml(t.jumlah_bayar_formatted)}</td>
                        <td><span style="text-transform: uppercase; font-weight: 600; font-size: 11px;">${escapeHtml(t.metode_pembayaran)}</span></td>
                        <td>${escapeHtml(t.dibayar_pada_formatted)}</td>
                    </tr>
                `).join('');
            }
        }

        // 7. Tabel Riwayat Presensi
        const attTable = document.getElementById('tableAttendancesBody');
        if (attTable) {
            if (attendances.length === 0) {
                attTable.innerHTML = '<tr><td colspan="3" class="table-empty">Belum ada riwayat presensi check-in</td></tr>';
            } else {
                attTable.innerHTML = attendances.map(a => `
                    <tr>
                        <td>${escapeHtml(a.tanggal)}</td>
                        <td><b style="color: #4ade80;">${escapeHtml(a.jam)} WIB</b></td>
                        <td class="muted">${escapeHtml(a.waktu_presensi)}</td>
                    </tr>
                `).join('');
            }
        }
    } catch (err) {
        console.warn('Gagal memuat detail member:', err);
        showToast('Gagal terhubung ke server');
    }
}

// Navigasi Tab
document.querySelectorAll('.tab-button').forEach(btn => {
    btn.addEventListener('click', () => {
        document.querySelectorAll('.tab-button').forEach(b => {
            b.classList.remove('active');
            b.setAttribute('aria-selected', 'false');
        });
        document.querySelectorAll('.tab-content').forEach(c => {
            c.classList.remove('active');
            c.hidden = true;
        });

        btn.classList.add('active');
        btn.setAttribute('aria-selected', 'true');
        const targetId = btn.dataset.tab;
        const targetContent = document.getElementById(targetId);
        if (targetContent) {
            targetContent.classList.add('active');
            targetContent.hidden = false;
        }
    });
});

// Aksi Check-in Cepat
document.getElementById('btnQuickCheckin')?.addEventListener('click', async () => {
    if (!currentMemberData) return;
    try {
        const res = await fetch(getApiUrl('admin/attendance'), {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'same-origin',
            body: JSON.stringify({ user_id: memberId }),
        });
        const data = await res.json();
        if (res.ok) {
            showToast(data.message || 'Presensi check-in berhasil!');
            loadMemberDetail();
        } else {
            showToast(data.error || 'Gagal melakukan check-in');
        }
    } catch {
        showToast('Gagal terhubung ke server');
    }
});

// Tombol Cetak / Simpan QR Code
document.getElementById('btnPrintMemberQr')?.addEventListener('click', () => {
    window.print();
});

// Modal Perpanjang Paket (Renew)
const modalRenew = document.getElementById('modalRenew');
const btnOpenRenew = document.getElementById('btnOpenRenew');
const btnCloseModalRenew = document.getElementById('btnCloseModalRenew');
const btnCancelModalRenew = document.getElementById('btnCancelModalRenew');
const formRenew = document.getElementById('formRenew');
const renewPackageSelect = document.getElementById('renewPackage');

async function loadPackagesForRenew() {
    if (!renewPackageSelect) return;
    try {
        const res = await fetch(getApiUrl('admin/packages'), { credentials: 'same-origin' });
        const json = await res.json();
        if (json.status === 'success' && Array.isArray(json.data)) {
            renewPackageSelect.innerHTML = json.data
                .filter(p => p.tersedia)
                .map(p => `<option value="${p.id}">${escapeHtml(p.nama_paket)} (${p.durasi_hari} Hari) — ${escapeHtml(p.harga_formatted)}</option>`)
                .join('');
        }
    } catch (err) {
        console.warn('Gagal memuat paket:', err);
    }
}

function openRenewModal() {
    if (!modalRenew) return;
    loadPackagesForRenew();
    modalRenew.classList.add('is-active');
    modalRenew.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';
}

function closeRenewModal() {
    if (!modalRenew) return;
    modalRenew.classList.remove('is-active');
    modalRenew.setAttribute('aria-hidden', 'true');
    document.body.style.overflow = '';
}

btnOpenRenew?.addEventListener('click', openRenewModal);
btnCloseModalRenew?.addEventListener('click', closeRenewModal);
btnCancelModalRenew?.addEventListener('click', closeRenewModal);

modalRenew?.addEventListener('click', (e) => {
    if (e.target === modalRenew) closeRenewModal();
});

formRenew?.addEventListener('submit', async (e) => {
    e.preventDefault();
    const submitBtn = formRenew.querySelector('button[type="submit"]');
    if (submitBtn) submitBtn.disabled = true;

    const formData = new FormData(formRenew);
    const payload = {
        id_paket: formData.get('id_paket'),
        metode_pembayaran: formData.get('metode_pembayaran'),
    };

    try {
        const res = await fetch(getApiUrl(`admin/members/${memberId}/renew`), {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'same-origin',
            body: JSON.stringify(payload),
        });
        const data = await res.json();
        if (res.ok) {
            closeRenewModal();
            showToast(data.message || 'Paket berhasil diperpanjang!');
            loadMemberDetail();
        } else {
            showToast(data.error || 'Gagal memperpanjang paket');
        }
    } catch {
        showToast('Gagal terhubung ke server');
    } finally {
        if (submitBtn) submitBtn.disabled = false;
    }
});

// Modal Edit Profil
const modalEdit = document.getElementById('modalEdit');
const btnOpenEdit = document.getElementById('btnOpenEdit');
const btnCloseModalEdit = document.getElementById('btnCloseModalEdit');
const btnCancelModalEdit = document.getElementById('btnCancelModalEdit');
const formEdit = document.getElementById('formEdit');

function openEditModal() {
    if (!modalEdit || !currentMemberData) return;

    document.getElementById('editName').value = currentMemberData.name || '';
    document.getElementById('editPhone').value = currentMemberData.phone || '';
    document.getElementById('editGender').value = currentMemberData.gender || 'pria';
    document.getElementById('editStatus').value = currentMemberData.membership_status || 'active';
    document.getElementById('editExpiresAt').value = currentMemberData.membership_expires_at || '';
    document.getElementById('editEmergency').value = currentMemberData.emergency_contact || '';
    document.getElementById('editAddress').value = currentMemberData.address || '';

    modalEdit.classList.add('is-active');
    modalEdit.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';
}

function closeEditModal() {
    if (!modalEdit) return;
    modalEdit.classList.remove('is-active');
    modalEdit.setAttribute('aria-hidden', 'true');
    document.body.style.overflow = '';
}

btnOpenEdit?.addEventListener('click', openEditModal);
btnCloseModalEdit?.addEventListener('click', closeEditModal);
btnCancelModalEdit?.addEventListener('click', closeEditModal);

modalEdit?.addEventListener('click', (e) => {
    if (e.target === modalEdit) closeEditModal();
});

formEdit?.addEventListener('submit', async (e) => {
    e.preventDefault();
    const submitBtn = formEdit.querySelector('button[type="submit"]');
    if (submitBtn) submitBtn.disabled = true;

    const formData = new FormData(formEdit);
    const payload = {
        name: formData.get('name'),
        phone: formData.get('phone'),
        gender: formData.get('gender'),
        membership_status: formData.get('membership_status'),
        membership_expires_at: formData.get('membership_expires_at') || null,
        emergency_contact: formData.get('emergency_contact'),
        address: formData.get('address'),
    };

    try {
        const res = await fetch(getApiUrl(`admin/members/${memberId}`), {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'same-origin',
            body: JSON.stringify(payload),
        });
        const data = await res.json();
        if (res.ok) {
            closeEditModal();
            showToast(data.message || 'Data member berhasil diperbarui!');
            loadMemberDetail();
        } else {
            showToast(data.error || 'Gagal memperbarui data member');
        }
    } catch {
        showToast('Gagal terhubung ke server');
    } finally {
        if (submitBtn) submitBtn.disabled = false;
    }
});

// Aksi Hapus Member
document.getElementById('btnDeleteMember')?.addEventListener('click', async () => {
    if (!currentMemberData) return;
    const confirmDelete = confirm(`Apakah Anda yakin ingin menghapus member "${currentMemberData.name}" (${currentMemberData.member_code})?\n\nSemua riwayat langganan dan presensi member ini juga akan terhapus.`);
    if (!confirmDelete) return;

    try {
        const res = await fetch(getApiUrl(`admin/members/${memberId}`), {
            method: 'DELETE',
            credentials: 'same-origin',
        });
        const data = await res.json();
        if (res.ok) {
            showToast(data.message || 'Member berhasil dihapus');
            setTimeout(() => window.location.replace('../members/'), 1200);
        } else {
            showToast(data.error || 'Gagal menghapus member');
        }
    } catch {
        showToast('Gagal terhubung ke server');
    }
});

// Tutup modal dengan tombol Escape
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        if (modalRenew?.classList.contains('is-active')) closeRenewModal();
        if (modalEdit?.classList.contains('is-active')) closeEditModal();
    }
});

loadMemberDetail();

