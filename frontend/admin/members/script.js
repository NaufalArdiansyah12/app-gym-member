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

        // Render Tabel Member (Hanya Data Orang)
        if (memberTableBody) {
            if (!Array.isArray(members) || members.length === 0) {
                memberTableBody.innerHTML = '<tr><td colspan="6" style="text-align: center; color: var(--color-muted, #888); padding: 2rem;">Tidak ada data member yang sesuai filter</td></tr>';
            } else {
                memberTableBody.innerHTML = members.map(m => `
                    <tr data-id="${m.id}">
                        <td>
                            <button type="button" class="btn-show-detail-trigger" data-id="${m.id}" style="background:none; border:none; padding:0; color:#fff; font-weight:700; cursor:pointer; text-align:left; font:inherit; transition: color 150ms ease;" onmouseover="this.style.color='#ff7a1a'" onmouseout="this.style.color='#fff'">${escapeHtml(m.name)}</button>
                            <small style="display:block; color:#ff9142; font-family:monospace; margin-top:2px;">${escapeHtml(m.member_code)}</small>
                        </td>
                        <td>${escapeHtml(m.phone)}</td>
                        <td><span class="status ${escapeHtml(m.gender_badge_class)}">${escapeHtml(m.gender_label)}</span></td>
                        <td>${escapeHtml(m.emergency_contact)}</td>
                        <td>${escapeHtml(m.created_at_formatted)}</td>
                        <td class="col-aksi">
                            <div class="action-buttons-wrap">
                                <button class="icon-button btn-show-detail-trigger" type="button" data-id="${m.id}" title="Lihat profil member" style="font-size: 1rem; cursor: pointer;">👁️</button>
                                <button class="icon-button btn-edit-member" type="button" data-id="${m.id}" title="Edit profil member" style="font-size: 1rem; cursor: pointer;">✎</button>
                                <button class="icon-button btn-delete-member" type="button" data-id="${m.id}" data-name="${escapeHtml(m.name)}" title="Hapus member" style="font-size: 1.1rem; opacity: 0.75; cursor: pointer;">🗑</button>
                            </div>
                        </td>
                    </tr>
                `).join('');

                // Pasang event klik untuk buka modal detail
                memberTableBody.querySelectorAll('.btn-show-detail-trigger').forEach(btn => {
                    btn.addEventListener('click', (e) => {
                        e.stopPropagation();
                        const id = btn.dataset.id;
                        openDetailModal(id);
                    });
                });

                // Pasang event klik untuk buka modal edit
                memberTableBody.querySelectorAll('.btn-edit-member').forEach(btn => {
                    btn.addEventListener('click', (e) => {
                        e.stopPropagation();
                        const id = btn.dataset.id;
                        const member = members.find(item => item.id == id);
                        if (member) {
                            openEditModal(member);
                        }
                    });
                });

                // Pasang event delete
                memberTableBody.querySelectorAll('.btn-delete-member').forEach(btn => {
                    btn.addEventListener('click', async (e) => {
                        e.stopPropagation();
                        const id = btn.dataset.id;
                        const name = btn.dataset.name;
                        deleteMember(id, name);
                    });
                });
            }
        }

        if (memberCount) {
            memberCount.textContent = `Menampilkan ${members.length} data member`;
        }
    } catch (err) {
        console.warn('Gagal memuat data member:', err);
    }
}

// Debounce input search
let searchTimer;
searchInput?.addEventListener('input', () => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(loadMembers, 250);
});
genderFilter?.addEventListener('change', loadMembers);

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

        // Header Profil
        document.getElementById('mdAvatar').textContent = member.initials || 'M';
        document.getElementById('modalDetailName').textContent = member.name;
        const badgeEl = document.getElementById('mdBadge');
        badgeEl.textContent = member.gender_label || (member.gender === 'wanita' ? 'Wanita' : 'Pria');
        badgeEl.className = `status ${member.gender_badge_class || (member.gender === 'wanita' ? 'pink-bg' : 'blue-bg')}`;
        document.getElementById('mdCode').textContent = member.member_code;
        document.getElementById('mdCreatedAt').textContent = member.created_at_formatted || '-';

        // Data Pribadi & Kontak
        document.getElementById('mdPhone').textContent = member.phone || '-';
        document.getElementById('mdGender').textContent = member.gender_label || (member.gender === 'wanita' ? 'Wanita' : 'Pria');
        document.getElementById('mdEmergency').textContent = member.emergency_contact || '-';
        document.getElementById('mdAddress').textContent = member.address || '-';

        // Informasi Akun & Presensi
        document.getElementById('mdInfoCode').textContent = member.member_code;
        document.getElementById('mdInfoJoined').textContent = member.created_at_formatted || '-';
        document.getElementById('mdTotalCheckins').textContent = `${Number(stats.total_checkins || 0).toLocaleString('id-ID')} kali`;
        document.getElementById('mdLastCheckin').textContent = stats.last_checkin || 'Belum pernah';

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

async function deleteMember(id, name, fromModal = false) {
    if (!confirm(`Hapus data member "${name}" dari sistem?\n\nSemua riwayat presensi member ini juga akan terhapus.`)) return;

    try {
        const delRes = await fetch(getApiUrl(`admin/members/${id}`), {
            method: 'DELETE',
            credentials: 'same-origin',
        });
        const delData = await delRes.json();
        if (delRes.ok) {
            showToast(delData.message || 'Member berhasil dihapus');
            if (fromModal) closeDetailModal();
            loadMembers();
        } else {
            showToast(delData.error || 'Gagal menghapus member');
        }
    } catch {
        showToast('Gagal terhubung ke server');
    }
}

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
    document.getElementById('editPhone').value = member.phone || '';
    document.getElementById('editGender').value = member.gender || 'pria';
    document.getElementById('editEmergency').value = member.emergency_contact === '-' ? '' : (member.emergency_contact || '');

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
        phone: formData.get('phone'),
        gender: formData.get('gender'),
        emergency_contact: formData.get('emergency_contact'),
    };

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
            showToast(data.message || 'Data member berhasil diperbarui!');
            loadMembers();
            openDetailModal(id);
        } else {
            showToast(data.error || 'Gagal memperbarui data member');
        }
    } catch {
        showToast('Gagal terhubung ke server');
    } finally {
        if (submitBtn) submitBtn.disabled = false;
    }
});

// ==========================================
// 4. MODAL TAMBAH MEMBER (ADD MEMBER)
// ==========================================
const modalAddMember = document.getElementById('modalAddMember');
const btnOpenAddMember = document.getElementById('btnOpenAddMember');
const btnCloseModal = document.getElementById('btnCloseModal');
const btnCancelModal = document.getElementById('btnCancelModal');
const formAddMember = document.getElementById('formAddMember');
const memberNameInput = document.getElementById('memberName');

function openModal() {
    if (!modalAddMember) return;
    modalAddMember.classList.add('is-active');
    modalAddMember.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';
    window.setTimeout(() => memberNameInput?.focus(), 80);
}

function closeModal() {
    if (!modalAddMember) return;
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
    const payload = {
        nama_lengkap: formData.get('nama_lengkap'),
        nomor_telepon: formData.get('nomor_telepon'),
        jenis_kelamin: formData.get('jenis_kelamin'),
        kontak_darurat: formData.get('kontak_darurat'),
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
            showToast(data.error || 'Gagal mendaftarkan member.');
            return;
        }

        closeModal();
        formAddMember.reset();
        showToast(data.message || 'Member baru berhasil didaftarkan!');
        loadMembers();
    } catch {
        showToast('Gagal terhubung ke server backend.');
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
        if (modalDetailMember?.classList.contains('is-active')) closeDetailModal();
        if (modalEdit?.classList.contains('is-active')) closeEditModal();
        if (modalAddMember?.classList.contains('is-active')) closeModal();
    }
});

loadMembers();
