// frontend/admin/members/script.js
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

const searchInput = document.getElementById('memberSearch');
const genderFilter = document.getElementById('memberGenderFilter');
const memberTableBody = document.getElementById('memberTableBody');
const memberCount = document.getElementById('memberCount');
const paginationEl = document.getElementById('memberPagination');
const ITEMS_PER_PAGE = 10;
let allMembers = [];
let currentPage = 1;
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

// Batasi input hanya angka (strip huruf/simbol) sekaligus batasi jumlah digit.
function attachDigitsOnly(input, maxDigits) {
    if (!input) return;
    input.addEventListener('input', () => {
        const digits = input.value.replace(/\D/g, '').slice(0, maxDigits);
        if (input.value !== digits) input.value = digits;
    });
}

['memberNik', 'editNik'].forEach((id) => attachDigitsOnly(document.getElementById(id), 16));
['memberPhone', 'memberEmergency', 'editPhone', 'editEmergency'].forEach((id) => attachDigitsOnly(document.getElementById(id), 13));

// ==========================================
// MODAL STATUS FEEDBACK (BERHASIL / GAGAL)
// ==========================================
const modalStatusResult = document.getElementById('modalStatusResult');
const modalResultIconBadge = document.getElementById('modalResultIconBadge');
const modalResultIcon = document.getElementById('modalResultIcon');
const modalResultTitle = document.getElementById('modalResultTitle');
const modalResultMessage = document.getElementById('modalResultMessage');
const btnModalResultClose = document.getElementById('btnModalResultClose');

function showResultModal(isSuccess, title, message) {
    if (!modalStatusResult) return;

    if (isSuccess) {
        modalResultIconBadge.className = 'result-icon-badge success-badge';
        modalResultIcon.innerHTML = '<use href="#icon-check"/>';
        modalResultTitle.textContent = title || 'Berhasil!';
        modalResultTitle.style.color = '#f9fafb';
        modalResultMessage.textContent = message || 'Aksi berhasil dilakukan.';
        btnModalResultClose.className = 'primary-button btn-full';
        btnModalResultClose.textContent = 'OK, Selesai';
    } else {
        modalResultIconBadge.className = 'result-icon-badge error-badge';
        modalResultIcon.innerHTML = '<use href="#icon-close"/>';
        modalResultTitle.textContent = title || 'Gagal!';
        modalResultTitle.style.color = '#fca5a5';
        modalResultMessage.textContent = message || 'Terjadi kesalahan saat memproses data.';
        btnModalResultClose.className = 'primary-button btn-full btn-error-style';
        btnModalResultClose.textContent = 'Tutup';
    }

    modalStatusResult.classList.add('is-active');
    modalStatusResult.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';
}

function closeResultModal() {
    if (!modalStatusResult) return;
    modalStatusResult.classList.remove('is-active');
    modalStatusResult.setAttribute('aria-hidden', 'true');
    document.body.style.overflow = '';
}

btnModalResultClose?.addEventListener('click', closeResultModal);
modalStatusResult?.addEventListener('click', (e) => {
    if (e.target === modalStatusResult) closeResultModal();
});

// ponytail: Render QR Code menggunakan lib lokal qrcodejs dengan fallback ke API gambar.
function renderMemberQr(container, code, size = 120) {
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

let activeDetailMember = null;

// ==========================================
// 1. DAFTAR MEMBER & FILTER (DATA ORANG SAJA)
// ==========================================
async function loadMembers() {
    const q = encodeURIComponent(searchInput?.value.trim() || '');
    const gender = encodeURIComponent(genderFilter?.value || 'all');

    try {
        const res = await fetch(getApiUrl(`admin/members?search=${q}&gender=${gender}`), {
            credentials: 'same-origin',
        });
        const json = await res.json();

        if (json.status !== 'success' || !json.data) return;

        const { stats, members } = json.data;

        // Update Kartu Statistik Berbasis Data Orang
        if (stats) {
            const elTotal = document.getElementById('statTotalMembers');
            const elMale = document.getElementById('statMaleMembers');
            const elFemale = document.getElementById('statFemaleMembers');
            const elEmergency = document.getElementById('statEmergencyMembers');

            if (elTotal) elTotal.textContent = Number(stats.total || 0).toLocaleString('id-ID');
            if (elMale) elMale.textContent = Number(stats.pria || 0).toLocaleString('id-ID');
            if (elFemale) elFemale.textContent = Number(stats.wanita || 0).toLocaleString('id-ID');
            if (elEmergency) elEmergency.textContent = Number(stats.with_contact || 0).toLocaleString('id-ID');
        }

        allMembers = Array.isArray(members) ? members : [];
        renderMemberTable();
    } catch (err) {
        console.warn('Gagal memuat data member:', err);
    }
}

// ponytail: Client-side pagination 10 data per halaman. Upgrade ke server-side limit/offset saat dataset member > ribuan.
function renderMemberTable() {
    if (!memberTableBody) return;

    const total = allMembers.length;
    const totalPages = Math.max(1, Math.ceil(total / ITEMS_PER_PAGE));

    if (currentPage > totalPages) currentPage = totalPages;
    if (currentPage < 1) currentPage = 1;

    const start = (currentPage - 1) * ITEMS_PER_PAGE;
    const end = Math.min(start + ITEMS_PER_PAGE, total);
    const pageMembers = allMembers.slice(start, end);

    if (pageMembers.length === 0) {
        memberTableBody.innerHTML = '<tr><td colspan="6" style="text-align: center; color: var(--color-muted, #888); padding: 2rem;">Tidak ada data member yang sesuai filter</td></tr>';
    } else {
        memberTableBody.innerHTML = pageMembers.map(m => {
            const photoHtml = `<div class="member-table-avatar">${escapeHtml((m.name || 'M').charAt(0).toUpperCase())}</div>`;

            return `
            <tr data-id="${m.id}">
                <td>
                    <div class="member-cell-profile">
                        ${photoHtml}
                        <div>
                            <button type="button" class="btn-show-detail-trigger" data-id="${m.id}" style="background:none; border:none; padding:0; color:#fff; font-weight:700; cursor:pointer; text-align:left; font:inherit; transition: color 150ms ease;" onmouseover="this.style.color='#ff7a1a'" onmouseout="this.style.color='#fff'">${escapeHtml(m.name)}</button>
                            <small style="display:block; color:#ff9142; font-family:monospace; margin-top:2px;">
                                ${escapeHtml(m.member_code)}${m.nik && m.nik !== '-' ? ` · <span style="color:#9ca3af; font-family:sans-serif;">NIK: ${escapeHtml(m.nik)}</span>` : ''}
                            </small>
                        </div>
                    </div>
                </td>
                <td>${escapeHtml(m.phone)}</td>
                <td><span class="status ${escapeHtml(m.gender_badge_class)}">${escapeHtml(m.gender_label)}</span></td>
                <td>${escapeHtml(m.emergency_contact)}</td>
                <td>${escapeHtml(m.created_at_formatted)}</td>
                <td class="col-aksi">
                    <div class="action-buttons-wrap">
                        <button class="icon-button btn-show-detail-trigger" type="button" data-id="${m.id}" title="Lihat profil member">
                            <svg class="icon" aria-hidden="true" focusable="false"><use href="#icon-eye"/></svg>
                        </button>
                        <button class="icon-button btn-edit-member" type="button" data-id="${m.id}" title="Edit profil member">
                            <svg class="icon" aria-hidden="true" focusable="false"><use href="#icon-edit"/></svg>
                        </button>
                        <button class="icon-button btn-manage-password" type="button" data-id="${m.id}" title="Lihat / reset password member">
                            <svg class="icon" aria-hidden="true" focusable="false"><use href="#icon-refresh"/></svg>
                        </button>
                        <button class="icon-button btn-delete-member" type="button" data-id="${m.id}" data-name="${escapeHtml(m.name)}" title="Hapus member">
                            <svg class="icon" aria-hidden="true" focusable="false"><use href="#icon-trash"/></svg>
                        </button>
                    </div>
                </td>
            </tr>
        `;
        }).join('');

        // Pasang event klik untuk buka modal detail
        memberTableBody.querySelectorAll('.btn-show-detail-trigger').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                openDetailModal(btn.dataset.id);
            });
        });

        // Pasang event klik untuk buka modal edit
        memberTableBody.querySelectorAll('.btn-edit-member').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                const id = btn.dataset.id;
                const member = allMembers.find(item => item.id == id);
                if (member) {
                    openEditModal(member);
                }
            });
        });

        // Pasang event kelola password
        memberTableBody.querySelectorAll('.btn-manage-password').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                const member = allMembers.find(item => item.id == btn.dataset.id);
                if (member) openPasswordModal(member);
            });
        });

        // Pasang event delete
        memberTableBody.querySelectorAll('.btn-delete-member').forEach(btn => {
            btn.addEventListener('click', async (e) => {
                e.stopPropagation();
                deleteMember(btn.dataset.id, btn.dataset.name);
            });
        });
    }

    if (memberCount) {
        if (total === 0) {
            memberCount.textContent = 'Menampilkan 0 data member';
        } else {
            memberCount.textContent = `Menampilkan ${start + 1}–${end} dari ${total} data member`;
        }
    }

    renderPagination(totalPages);
}

function renderPagination(totalPages) {
    if (!paginationEl) return;

    if (allMembers.length === 0) {
        paginationEl.innerHTML = '';
        return;
    }

    let pages = [];
    if (totalPages <= 7) {
        for (let i = 1; i <= totalPages; i++) pages.push(i);
    } else {
        if (currentPage <= 4) {
            pages = [1, 2, 3, 4, 5, '...', totalPages];
        } else if (currentPage >= totalPages - 3) {
            pages = [1, '...', totalPages - 4, totalPages - 3, totalPages - 2, totalPages - 1, totalPages];
        } else {
            pages = [1, '...', currentPage - 1, currentPage, currentPage + 1, '...', totalPages];
        }
    }

    const prevDisabled = currentPage <= 1 ? 'disabled' : '';
    const nextDisabled = currentPage >= totalPages ? 'disabled' : '';

    let html = `<button class="pagination-btn" type="button" data-page="${currentPage - 1}" ${prevDisabled} aria-label="Halaman sebelumnya">‹</button>`;

    pages.forEach(p => {
        if (p === '...') {
            html += `<span class="pagination-ellipsis">…</span>`;
        } else {
            const isActive = p === currentPage;
            html += `<button class="pagination-btn ${isActive ? 'active' : ''}" type="button" data-page="${p}" ${isActive ? 'disabled aria-current="page"' : ''}>${p}</button>`;
        }
    });

    html += `<button class="pagination-btn" type="button" data-page="${currentPage + 1}" ${nextDisabled} aria-label="Halaman berikutnya">›</button>`;

    paginationEl.innerHTML = html;
}

paginationEl?.addEventListener('click', (e) => {
    const btn = e.target.closest('.pagination-btn');
    if (!btn || btn.disabled) return;
    const targetPage = Number(btn.dataset.page);
    if (!targetPage || targetPage === currentPage) return;
    currentPage = targetPage;
    renderMemberTable();
    document.querySelector('.table-wrap')?.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
});

// Debounce input search
let searchTimer;
searchInput?.addEventListener('input', () => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => {
        currentPage = 1;
        loadMembers();
    }, 250);
});
genderFilter?.addEventListener('change', () => {
    currentPage = 1;
    loadMembers();
});

// ==========================================
// 2. MODAL DETAIL MEMBER (DATA PRIBADI & PRESENSI)
// ==========================================
const modalDetailMember = document.getElementById('modalDetailMember');
const btnCloseModalDetail = document.getElementById('btnCloseModalDetail');

async function openDetailModal(id) {
    if (!modalDetailMember) return;

    modalDetailMember.classList.add('is-active');
    modalDetailMember.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';

    // State memuat
    document.getElementById('modalDetailName').textContent = 'Memuat profil...';
    document.getElementById('mdBadge').textContent = '...';
    document.getElementById('mdBadge').className = 'status muted-bg';
    document.getElementById('mdCode').textContent = 'GYM-...';
    document.getElementById('mdCreatedAt').textContent = '-';
    const loadPhotoEl = document.getElementById('mdPhoto');
    const loadPhotoEmptyEl = document.getElementById('mdPhotoEmpty');
    if (loadPhotoEl) {
        loadPhotoEl.removeAttribute('src');
        loadPhotoEl.style.display = 'none';
    }
    if (loadPhotoEmptyEl) loadPhotoEmptyEl.style.display = 'flex';
    document.getElementById('mdTableAttBody').innerHTML = '<tr><td colspan="2" class="table-empty">Memuat riwayat presensi...</td></tr>';

    try {
        const res = await fetch(getApiUrl(`admin/members/${id}`), { credentials: 'same-origin' });
        const json = await res.json();

        if (!res.ok || json.status !== 'success' || !json.data) {
            showToast(json.error || 'Gagal memuat detail member');
            closeDetailModal();
            return;
        }

        const { member, stats, attendances } = json.data;
        activeDetailMember = member;

        // Header Profil: selalu inisial (foto member hanya untuk verifikasi, bukan foto profil)
        const avatarEl = document.getElementById('mdAvatar');
        avatarEl.textContent = member.initials || 'M';

        // Foto Verifikasi Member (setengah badan)
        const photoEl = document.getElementById('mdPhoto');
        const photoEmptyEl = document.getElementById('mdPhotoEmpty');
        if (photoEl && photoEmptyEl) {
            if (member.foto) {
                photoEl.src = `../../${member.foto}`;
                photoEl.style.display = 'block';
                photoEmptyEl.style.display = 'none';
            } else {
                photoEl.removeAttribute('src');
                photoEl.style.display = 'none';
                photoEmptyEl.style.display = 'flex';
            }
        }

        document.getElementById('modalDetailName').textContent = member.name;
        const badgeEl = document.getElementById('mdBadge');
        badgeEl.textContent = member.gender_label || (member.gender === 'wanita' ? 'Wanita' : 'Pria');
        badgeEl.className = `status ${member.gender_badge_class || (member.gender === 'wanita' ? 'pink-bg' : 'blue-bg')}`;
        document.getElementById('mdCode').textContent = member.member_code;
        document.getElementById('mdCreatedAt').textContent = member.created_at_formatted || '-';

        // Data Identitas KTP
        const elNik = document.getElementById('mdNik');
        if (elNik) elNik.textContent = member.nik && member.nik !== '-' ? member.nik : '-';

        const elBirth = document.getElementById('mdBirth');
        if (elBirth) {
            const birthParts = [];
            if (member.tempat_lahir && member.tempat_lahir !== '-') birthParts.push(member.tempat_lahir);
            if (member.tanggal_lahir_formatted && member.tanggal_lahir_formatted !== '-') birthParts.push(member.tanggal_lahir_formatted);
            elBirth.textContent = birthParts.length > 0 ? birthParts.join(', ') : '-';
        }

        const elAddress = document.getElementById('mdAddress');
        if (elAddress) elAddress.textContent = member.address && member.address !== '-' ? member.address : '-';

        // Data Kontak & Akun
        document.getElementById('mdPhone').textContent = member.phone || '-';
        document.getElementById('mdGender').textContent = member.gender_label || (member.gender === 'wanita' ? 'Wanita' : 'Pria');
        document.getElementById('mdEmergency').textContent = member.emergency_contact || '-';

        // Informasi Akun & Presensi
        document.getElementById('mdInfoCode').textContent = member.member_code;

        // Password (ditampilkan tersamarkan, tombol lihat/reset ada di samping)
        const elPw = document.getElementById('mdPassword');
        if (elPw) {
            const pw = member.password_plain || '';
            elPw.dataset.value = pw;
            elPw.dataset.revealed = '0';
            elPw.textContent = pw ? MASK : '-';
        }
        const infoPkgEl = document.getElementById('mdInfoPackage');
        if (infoPkgEl) {
            infoPkgEl.textContent = member.active_package_name && member.active_package_name !== 'Belum ada paket'
                ? `${member.status_label} (${member.active_package_name})`
                : (member.status_label || 'Tidak Aktif');
        }
        const infoExpEl = document.getElementById('mdInfoExpires');
        if (infoExpEl) {
            infoExpEl.textContent = member.expires_at_formatted || '-';
        }
        document.getElementById('mdInfoJoined').textContent = member.created_at_formatted || '-';
        document.getElementById('mdTotalCheckins').textContent = `${Number(stats.total_checkins || 0).toLocaleString('id-ID')} kali`;
        document.getElementById('mdLastCheckin').textContent = stats.last_checkin || 'Belum pernah';

        // Render QR Code Member untuk Presensi
        renderMemberQr(document.getElementById('mdQrHolder'), member.member_code, 120);

        // Riwayat Presensi
        document.getElementById('mdCountAtt').textContent = Array.isArray(attendances) ? attendances.length : 0;
        const attBody = document.getElementById('mdTableAttBody');
        if (!Array.isArray(attendances) || attendances.length === 0) {
            attBody.innerHTML = '<tr><td colspan="2" class="table-empty">Belum ada riwayat presensi</td></tr>';
        } else {
            attBody.innerHTML = attendances.map(a => `
                <tr>
                    <td>${escapeHtml(a.tanggal)}</td>
                    <td><b style="color: #4ade80;">${escapeHtml(a.jam)} WIB</b></td>
                </tr>
            `).join('');
        }
    } catch {
        showToast('Gagal terhubung ke server');
        closeDetailModal();
    }
}

function closeDetailModal() {
    if (!modalDetailMember) return;
    modalDetailMember.classList.remove('is-active');
    modalDetailMember.setAttribute('aria-hidden', 'true');
    document.body.style.overflow = '';
}

btnCloseModalDetail?.addEventListener('click', closeDetailModal);
modalDetailMember?.addEventListener('click', (e) => {
    if (e.target === modalDetailMember) closeDetailModal();
});

// Toggle lihat password di modal detail
document.getElementById('mdBtnTogglePassword')?.addEventListener('click', () => {
    const elPw = document.getElementById('mdPassword');
    if (!elPw || !elPw.dataset.value) return;
    const revealed = elPw.dataset.revealed === '1';
    elPw.textContent = revealed ? MASK : elPw.dataset.value;
    elPw.dataset.revealed = revealed ? '0' : '1';
});

// Buka modal kelola password dari modal detail
document.getElementById('mdBtnShowPasswordModal')?.addEventListener('click', () => {
    if (activeDetailMember) openPasswordModal(activeDetailMember);
});

// Aksi di dalam Modal Detail
document.getElementById('mdBtnQuickCheckin')?.addEventListener('click', async () => {
    if (!activeDetailMember) return;
    try {
        const res = await fetch(getApiUrl('admin/attendance'), {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'same-origin',
            body: JSON.stringify({ user_id: activeDetailMember.id }),
        });
        const data = await res.json();
        if (res.ok) {
            showToast(data.message || 'Presensi check-in berhasil dicatat!');
            openDetailModal(activeDetailMember.id);
            loadMembers();
        } else {
            showToast(data.error || 'Gagal melakukan check-in');
        }
    } catch {
        showToast('Gagal terhubung ke server');
    }
});

// ==========================================
// MODAL KONFIRMASI HAPUS MEMBER
// ==========================================
const modalDeleteMember = document.getElementById('modalDeleteMember');
const btnCloseModalDelete = document.getElementById('btnCloseModalDelete');
const btnCancelDelete = document.getElementById('btnCancelDelete');
const btnConfirmDelete = document.getElementById('btnConfirmDelete');
const deleteMemberName = document.getElementById('deleteMemberName');

let pendingDelete = null;

function openDeleteModal(id, name, fromModal = false) {
    if (!modalDeleteMember) return;
    pendingDelete = { id, name, fromModal };
    if (deleteMemberName) deleteMemberName.textContent = `"${name}"`;
    modalDeleteMember.classList.add('is-active');
    modalDeleteMember.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';
}

function closeDeleteModal() {
    if (!modalDeleteMember) return;
    modalDeleteMember.classList.remove('is-active');
    modalDeleteMember.setAttribute('aria-hidden', 'true');
    document.body.style.overflow = '';
    pendingDelete = null;
}

btnCloseModalDelete?.addEventListener('click', closeDeleteModal);
btnCancelDelete?.addEventListener('click', closeDeleteModal);
modalDeleteMember?.addEventListener('click', (e) => {
    if (e.target === modalDeleteMember) closeDeleteModal();
});

btnConfirmDelete?.addEventListener('click', async () => {
    if (!pendingDelete) return;
    const { id, fromModal } = pendingDelete;
    btnConfirmDelete.disabled = true;
    btnConfirmDelete.textContent = 'Menghapus...';

    try {
        const delRes = await fetch(getApiUrl(`admin/members/${id}`), {
            method: 'DELETE',
            credentials: 'same-origin',
        });
        const delData = await delRes.json();
        closeDeleteModal();
        if (fromModal) closeDetailModal();

        if (delRes.ok) {
            loadMembers();
            showResultModal(true, 'Member Berhasil Dihapus', delData.message || 'Member berhasil dihapus dari sistem.');
        } else {
            showResultModal(false, 'Gagal Menghapus Member', delData.error || 'Gagal menghapus member.');
        }
    } catch {
        closeDeleteModal();
        showResultModal(false, 'Kesalahan Jaringan', 'Gagal terhubung ke server.');
    } finally {
        btnConfirmDelete.disabled = false;
        btnConfirmDelete.textContent = 'Ya, Hapus';
    }
});

function deleteMember(id, name, fromModal = false) {
    openDeleteModal(id, name, fromModal);
}

// ==========================================
// MODAL PASSWORD MEMBER (LIHAT & RESET)
// ==========================================
const modalMemberPassword = document.getElementById('modalMemberPassword');
const btnCloseModalPassword = document.getElementById('btnCloseModalPassword');
const btnDonePassword = document.getElementById('btnDonePassword');
const btnTogglePw = document.getElementById('btnTogglePw');
const btnCopyPw = document.getElementById('btnCopyPw');
const btnResetPassword = document.getElementById('btnResetPassword');
const pwMemberName = document.getElementById('pwMemberName');
const pwMemberCode = document.getElementById('pwMemberCode');
const pwMemberValue = document.getElementById('pwMemberValue');
const pwHint = document.getElementById('pwHint');

const MASK = '••••••••';
let activePasswordMember = null;
let pwRevealed = false;

function setPwDisplay() {
    if (!pwMemberValue) return;
    const pw = activePasswordMember?.password || '';
    pwMemberValue.textContent = pw ? (pwRevealed ? pw : MASK) : '-';
    if (pwHint) {
        pwHint.textContent = pw
            ? 'Member dapat login memakai Kode Member atau Email dengan password di atas.'
            : 'Password lama tidak tersedia. Klik "Reset Password" untuk membuat password baru.';
    }
}

function openPasswordModal(member) {
    if (!modalMemberPassword || !member) return;
    activePasswordMember = {
        id: member.id,
        name: member.name || '-',
        member_code: member.member_code || '-',
        password: member.password_plain || '',
    };
    pwRevealed = false;
    if (pwMemberName) pwMemberName.textContent = activePasswordMember.name;
    if (pwMemberCode) pwMemberCode.textContent = activePasswordMember.member_code;
    setPwDisplay();
    modalMemberPassword.classList.add('is-active');
    modalMemberPassword.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';
}

function closePasswordModal() {
    if (!modalMemberPassword) return;
    modalMemberPassword.classList.remove('is-active');
    modalMemberPassword.setAttribute('aria-hidden', 'true');
    document.body.style.overflow = '';
    activePasswordMember = null;
}

btnCloseModalPassword?.addEventListener('click', closePasswordModal);
btnDonePassword?.addEventListener('click', closePasswordModal);
modalMemberPassword?.addEventListener('click', (e) => {
    if (e.target === modalMemberPassword) closePasswordModal();
});

btnTogglePw?.addEventListener('click', () => {
    if (!activePasswordMember?.password) return;
    pwRevealed = !pwRevealed;
    setPwDisplay();
});

btnCopyPw?.addEventListener('click', async () => {
    const pw = activePasswordMember?.password || '';
    if (!pw) {
        showToast('Tidak ada password untuk disalin');
        return;
    }
    try {
        await navigator.clipboard.writeText(pw);
        showToast('Password disalin ke clipboard');
    } catch {
        showToast(`Gagal menyalin otomatis. Password: ${pw}`);
    }
});

btnResetPassword?.addEventListener('click', async () => {
    if (!activePasswordMember) return;
    btnResetPassword.disabled = true;
    try {
        const res = await fetch(getApiUrl(`admin/members/${activePasswordMember.id}/reset-password`), {
            method: 'POST',
            credentials: 'same-origin',
        });
        const data = await res.json();
        if (res.ok && data.data?.password) {
            activePasswordMember.password = data.data.password;
            pwRevealed = true;
            setPwDisplay();
            if (activeDetailMember && activeDetailMember.id == activePasswordMember.id) {
                activeDetailMember.password_plain = data.data.password;
            }
            loadMembers();
            showToast('Password baru berhasil dibuat');
        } else {
            showToast(data.error || 'Gagal mereset password');
        }
    } catch {
        showToast('Gagal terhubung ke server');
    } finally {
        btnResetPassword.disabled = false;
    }
});

// ==========================================
// 3. MODAL EDIT PROFIL MEMBER (DATA ORANG)
// ==========================================
const modalEdit = document.getElementById('modalEdit');
const btnCloseModalEdit = document.getElementById('btnCloseModalEdit');
const btnCancelModalEdit = document.getElementById('btnCancelModalEdit');
const formEdit = document.getElementById('formEdit');

function openEditModal(member) {
    if (!modalEdit || !member) return;

    document.getElementById('editMemberId').value = member.id;
    document.getElementById('editName').value = member.name || '';
    if (document.getElementById('editNik')) document.getElementById('editNik').value = (member.nik && member.nik !== '-') ? member.nik : '';
    if (document.getElementById('editBirthPlace')) document.getElementById('editBirthPlace').value = (member.tempat_lahir && member.tempat_lahir !== '-') ? member.tempat_lahir : '';
    if (document.getElementById('editBirthDate')) document.getElementById('editBirthDate').value = member.tanggal_lahir || '';
    document.getElementById('editPhone').value = member.phone || '';
    document.getElementById('editGender').value = member.gender || 'pria';
    if (document.getElementById('editAddress')) document.getElementById('editAddress').value = (member.address && member.address !== '-') ? member.address : '';
    document.getElementById('editEmergency').value = member.emergency_contact === '-' ? '' : (member.emergency_contact || '');

    // Tampilkan foto lama (jika ada) agar bisa diganti dengan foto baru.
    editPhoto.setExisting(member.foto ? `../../${member.foto}` : null);

    modalEdit.classList.add('is-active');
    modalEdit.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';
}

function closeEditModal() {
    if (!modalEdit) return;
    editPhoto.remove();
    modalEdit.classList.remove('is-active');
    modalEdit.setAttribute('aria-hidden', 'true');
    document.body.style.overflow = '';
}

btnCloseModalEdit?.addEventListener('click', closeEditModal);
btnCancelModalEdit?.addEventListener('click', closeEditModal);
modalEdit?.addEventListener('click', (e) => {
    if (e.target === modalEdit) closeEditModal();
});

formEdit?.addEventListener('submit', async (e) => {
    e.preventDefault();
    const submitBtn = formEdit.querySelector('button[type="submit"]');
    if (submitBtn) submitBtn.disabled = true;

    const id = document.getElementById('editMemberId').value;
    const formData = new FormData(formEdit);
    const payload = {
        name: formData.get('name'),
        nik: formData.get('nik') ? formData.get('nik').trim() : '',
        tempat_lahir: formData.get('tempat_lahir') ? formData.get('tempat_lahir').trim() : '',
        tanggal_lahir: formData.get('tanggal_lahir') || undefined,
        phone: formData.get('phone'),
        gender: formData.get('gender'),
        address: formData.get('address'),
        emergency_contact: formData.get('emergency_contact'),
    };

    // Kirim foto hanya bila user mengubah/menghapus (dirty). Nilai '' = hapus foto.
    if (editPhoto.isDirty()) {
        payload.foto = editPhoto.getValue();
    }

    try {
        const res = await fetch(getApiUrl(`admin/members/${id}`), {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'same-origin',
            body: JSON.stringify(payload),
        });
        const data = await res.json();
        if (res.ok) {
            closeEditModal();
            loadMembers();
            showResultModal(true, 'Data Member Diperbarui', data.message || 'Data member berhasil diperbarui.');
        } else {
            showResultModal(false, 'Gagal Memperbarui Member', data.error || 'Gagal memperbarui data member.');
        }
    } catch {
        showResultModal(false, 'Kesalahan Jaringan', 'Gagal terhubung ke server.');
    } finally {
        if (submitBtn) submitBtn.disabled = false;
    }
});

// ==========================================
// 4. MODAL TAMBAH MEMBER (ADD MEMBER) & KAMERA
// ==========================================
const modalAddMember = document.getElementById('modalAddMember');
const btnOpenAddMember = document.getElementById('btnOpenAddMember');
const btnCloseModal = document.getElementById('btnCloseModal');
const btnCancelModal = document.getElementById('btnCancelModal');
const formAddMember = document.getElementById('formAddMember');
const memberNameInput = document.getElementById('memberName');
const memberPackageSelect = document.getElementById('memberPackage');
const packageActivationFields = document.getElementById('packageActivationFields');
const memberStartDate = document.getElementById('memberStartDate');
const packageInfoBadge = document.getElementById('packageInfoBadge');

// Elemen Kamera & Foto Setengah Badan (dibuat sebagai factory agar dipakai modal Tambah & Edit)
function createPhotoCapture(ids) {
    const el = {
        wrapper: document.getElementById(ids.wrapper),
        placeholder: document.getElementById(ids.placeholder),
        stream: document.getElementById(ids.stream),
        video: document.getElementById(ids.video),
        preview: document.getElementById(ids.preview),
        canvas: document.getElementById(ids.canvas),
        data: document.getElementById(ids.data),
        file: document.getElementById(ids.file),
        initial: document.getElementById(ids.initial),
        active: document.getElementById(ids.active),
        captured: document.getElementById(ids.captured),
        btnStart: document.getElementById(ids.btnStart),
        btnCapture: document.getElementById(ids.btnCapture),
        btnStop: document.getElementById(ids.btnStop),
        btnRetake: document.getElementById(ids.btnRetake),
        btnRemove: document.getElementById(ids.btnRemove),
    };

    let stream = null;
    let dirty = false;

    function render(state) {
        if (el.stream) el.stream.style.display = state === 'streaming' ? 'block' : 'none';
        if (el.preview) el.preview.style.display = state === 'photo' ? 'block' : 'none';
        if (el.placeholder) el.placeholder.style.display = state === 'empty' ? 'flex' : 'none';
        if (el.wrapper) {
            el.wrapper.className = 'photo-portrait-wrapper' +
                (state === 'streaming' ? ' is-streaming' : state === 'photo' ? ' has-photo' : '');
        }
        if (el.initial) el.initial.style.display = state === 'empty' ? 'flex' : 'none';
        if (el.active) el.active.style.display = state === 'streaming' ? 'flex' : 'none';
        if (el.captured) el.captured.style.display = state === 'photo' ? 'flex' : 'none';
    }

    function stopStream() {
        if (stream) {
            stream.getTracks().forEach(t => t.stop());
            stream = null;
        }
        if (el.video) el.video.srcObject = null;
    }

    async function start() {
        try {
            stopStream();
            stream = await navigator.mediaDevices.getUserMedia({
                video: { facingMode: 'user', width: { ideal: 640 }, height: { ideal: 480 } },
                audio: false,
            });
            if (el.video) {
                el.video.srcObject = stream;
                await el.video.play();
            }
            render('streaming');
        } catch (err) {
            console.warn('Gagal mengakses kamera:', err);
            showToast('Tidak dapat mengakses kamera. Silakan gunakan tombol Upload Berkas.');
            stopStream();
            render((el.preview && el.preview.getAttribute('src')) ? 'photo' : 'empty');
        }
    }

    function stop() {
        stopStream();
        if (el.preview && el.preview.getAttribute('src')) {
            render('photo');
        } else {
            render('empty');
        }
    }

    function capture() {
        if (!stream || !el.video) return;

        const vidW = el.video.videoWidth || 640;
        const vidH = el.video.videoHeight || 480;

        // Crop potret rasio 3:4 setengah badan
        const targetW = 450;
        const targetH = 600;

        if (el.canvas) {
            el.canvas.width = targetW;
            el.canvas.height = targetH;
            const ctx = el.canvas.getContext('2d');

            let srcW = vidH * 0.75;
            let srcH = vidH;
            if (srcW > vidW) {
                srcW = vidW;
                srcH = vidW / 0.75;
            }

            const srcX = (vidW - srcW) / 2;
            const srcY = (vidH - srcH) / 2;

            ctx.translate(targetW, 0);
            ctx.scale(-1, 1);
            ctx.drawImage(el.video, srcX, srcY, srcW, srcH, 0, 0, targetW, targetH);

            const dataUrl = el.canvas.toDataURL('image/jpeg', 0.88);
            if (el.data) el.data.value = dataUrl;
            if (el.preview) el.preview.src = dataUrl;
        }

        dirty = true;
        stopStream();
        render('photo');
    }

    function remove() {
        stopStream();
        if (el.data) el.data.value = '';
        if (el.file) el.file.value = '';
        if (el.preview) el.preview.removeAttribute('src');
        dirty = true;
        render('empty');
    }

    // Tampilkan foto lama yang sudah tersimpan di server (dipakai modal Edit).
    function setExisting(src) {
        stopStream();
        if (el.data) el.data.value = '';
        if (el.file) el.file.value = '';
        dirty = false;
        if (el.preview && src) {
            el.preview.src = src;
            render('photo');
        } else {
            render('empty');
        }
    }

    function getValue() {
        return (el.data && el.data.value) ? el.data.value : '';
    }

    function isDirty() {
        return dirty;
    }

    el.btnStart?.addEventListener('click', start);
    el.btnCapture?.addEventListener('click', capture);
    el.btnStop?.addEventListener('click', stop);
    el.btnRetake?.addEventListener('click', start);
    el.btnRemove?.addEventListener('click', remove);

    el.file?.addEventListener('change', (e) => {
        const file = e.target.files?.[0];
        if (!file) return;

        if (!file.type.startsWith('image/')) {
            showToast('Berkas harus berupa gambar (JPG, PNG, WEBP)');
            return;
        }

        const reader = new FileReader();
        reader.onload = (evt) => {
            const dataUrl = evt.target?.result;
            if (el.data) el.data.value = dataUrl;
            if (el.preview) el.preview.src = dataUrl;
            dirty = true;
            render('photo');
        };
        reader.readAsDataURL(file);
    });

    render('empty');

    return { start, stop, remove, setExisting, getValue, isDirty };
}

const addPhoto = createPhotoCapture({
    wrapper: 'photoWrapper', placeholder: 'photoPlaceholder', stream: 'cameraStreamContainer', video: 'cameraVideo',
    preview: 'photoPreview', canvas: 'photoCanvas', data: 'memberPhotoData', file: 'memberPhotoFile',
    initial: 'initialPhotoControls', active: 'activeCameraControls', captured: 'capturedPhotoControls',
    btnStart: 'btnStartCamera', btnCapture: 'btnCapturePhoto', btnStop: 'btnStopCamera', btnRetake: 'btnRetakePhoto', btnRemove: 'btnRemovePhoto',
});

const editPhoto = createPhotoCapture({
    wrapper: 'editPhotoWrapper', placeholder: 'editPhotoPlaceholder', stream: 'editCameraStreamContainer', video: 'editCameraVideo',
    preview: 'editPhotoPreview', canvas: 'editPhotoCanvas', data: 'editMemberPhotoData', file: 'editMemberPhotoFile',
    initial: 'editInitialPhotoControls', active: 'editActiveCameraControls', captured: 'editCapturedPhotoControls',
    btnStart: 'btnEditStartCamera', btnCapture: 'btnEditCapturePhoto', btnStop: 'btnEditStopCamera', btnRetake: 'btnEditRetakePhoto', btnRemove: 'btnEditRemovePhoto',
});

let availablePackages = [];

async function loadPackagesForModal() {
    try {
        const res = await fetch(getApiUrl('admin/packages'), { credentials: 'same-origin' });
        const json = await res.json();
        if (json.status === 'success' && Array.isArray(json.data)) {
            availablePackages = json.data.filter(p => p.tersedia);
            if (memberPackageSelect) {
                memberPackageSelect.innerHTML = '<option value="">-- Pilih Paket --</option>' +
                    availablePackages.map(p => {
                        const price = p.harga_formatted || ('Rp ' + Number(p.harga).toLocaleString('id-ID'));
                        return `<option value="${p.id}">${escapeHtml(p.nama_paket)} — ${price} (${p.durasi_hari} hari)</option>`;
                    }).join('');
            }
        }
    } catch (err) {
        console.warn('Gagal memuat paket:', err);
    }
}

function updatePackageFields() {
    const pkgId = Number(memberPackageSelect?.value || 0);
    const selected = availablePackages.find(p => p.id === pkgId);

    if (selected) {
        if (packageActivationFields) packageActivationFields.style.display = 'block';
        if (memberStartDate && !memberStartDate.value) {
            memberStartDate.value = new Date().toISOString().slice(0, 10);
        }
        if (packageInfoBadge) {
            packageInfoBadge.hidden = false;
            const price = selected.harga_formatted || ('Rp ' + Number(selected.harga).toLocaleString('id-ID'));
            packageInfoBadge.innerHTML = `<strong>${escapeHtml(selected.nama_paket)}</strong> · Durasi ${selected.durasi_hari} Hari · Biaya: <strong>${price}</strong>`;
        }
    } else {
        if (packageActivationFields) packageActivationFields.style.display = 'none';
        if (packageInfoBadge) packageInfoBadge.hidden = true;
    }
}

memberPackageSelect?.addEventListener('change', updatePackageFields);

function openModal() {
    if (!modalAddMember) return;
    if (formAddMember) formAddMember.reset();
    addPhoto.remove();
    if (memberStartDate) memberStartDate.value = new Date().toISOString().slice(0, 10);
    updatePackageFields();
    modalAddMember.classList.add('is-active');
    modalAddMember.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';
    window.setTimeout(() => document.getElementById('memberNik')?.focus(), 80);
}

function closeModal() {
    if (!modalAddMember) return;
    addPhoto.remove();
    modalAddMember.classList.remove('is-active');
    modalAddMember.setAttribute('aria-hidden', 'true');
    document.body.style.overflow = '';
    btnOpenAddMember?.focus();
}

btnOpenAddMember?.addEventListener('click', openModal);
btnCloseModal?.addEventListener('click', closeModal);
btnCancelModal?.addEventListener('click', closeModal);

modalAddMember?.addEventListener('click', (e) => {
    if (e.target === modalAddMember) closeModal();
});

// Submit Pendaftaran Member Baru
formAddMember?.addEventListener('submit', async (e) => {
    e.preventDefault();
    const submitBtn = formAddMember.querySelector('button[type="submit"]');
    if (submitBtn) submitBtn.disabled = true;

    const formData = new FormData(formAddMember);
    const rawNik = (formData.get('nik') || '').trim().replace(/\D/g, '');
    const rawPhone = (formData.get('nomor_telepon') || '').trim().replace(/\D/g, '');
    const idPaket = Number(formData.get('id_paket')) || 0;

    if (rawNik !== '' && rawNik.length !== 16) {
        showResultModal(false, 'Validasi Gagal', 'NIK harus terdiri dari 16 digit angka sesuai KTP.');
        if (submitBtn) submitBtn.disabled = false;
        return;
    }

    if (rawPhone === '' || rawPhone.length < 10 || rawPhone.length > 13) {
        showResultModal(false, 'Validasi Gagal', 'Nomor WhatsApp/HP harus berupa angka 10 sampai 13 digit.');
        if (submitBtn) submitBtn.disabled = false;
        return;
    }

    if (idPaket <= 0) {
        showResultModal(false, 'Validasi Gagal', 'Paket membership wajib dipilih.');
        if (submitBtn) submitBtn.disabled = false;
        return;
    }

    const payload = {
        nik: rawNik,
        nama_lengkap: (formData.get('nama_lengkap') || '').trim(),
        tempat_lahir: (formData.get('tempat_lahir') || '').trim(),
        tanggal_lahir: formData.get('tanggal_lahir') || undefined,
        jenis_kelamin: formData.get('jenis_kelamin') || 'pria',
        alamat: (formData.get('alamat') || '').trim(),
        nomor_telepon: rawPhone,
        kontak_darurat: (formData.get('kontak_darurat') || '').trim().replace(/\D/g, ''),
        foto: addPhoto.getValue() || undefined,
        id_paket: idPaket,
        tanggal_mulai: formData.get('tanggal_mulai') || undefined,
        metode_pembayaran: formData.get('metode_pembayaran') || 'tunai',
    };

    try {
        const res = await fetch(getApiUrl('admin/members'), {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'same-origin',
            body: JSON.stringify(payload),
        });

        const data = await res.json();

        if (!res.ok) {
            showResultModal(false, 'Pendaftaran Gagal', data.error || 'Gagal mendaftarkan member.');
            return;
        }

        closeModal();
        formAddMember.reset();
        addPhoto.remove();
        updatePackageFields();
        currentPage = 1;
        loadMembers();
        showResultModal(true, 'Pendaftaran Berhasil', data.message || 'Member baru berhasil didaftarkan.');
    } catch {
        showResultModal(false, 'Kesalahan Jaringan', 'Gagal terhubung ke server backend.');
    } finally {
        if (submitBtn) submitBtn.disabled = false;
    }
});

document.querySelectorAll('.demo-action').forEach((button) => {
    button.addEventListener('click', () => {
        showToast('Fitur export data member akan segera hadir.');
    });
});

// Tutup modal apapun dengan tombol Escape
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        if (modalStatusResult?.classList.contains('is-active')) closeResultModal();
        if (modalDeleteMember?.classList.contains('is-active')) closeDeleteModal();
        if (modalDetailMember?.classList.contains('is-active')) closeDetailModal();
        if (modalEdit?.classList.contains('is-active')) closeEditModal();
        if (modalAddMember?.classList.contains('is-active')) closeModal();
    }
});

loadMembers();
loadPackagesForModal();
