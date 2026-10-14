// frontend/admin/dashboard/script.js
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

        // Toast Helper
        const toast = document.getElementById('toast');
        let toastTimer;

        function showToast(message) {
            if (!toast) return;
            toast.textContent = message;
            toast.classList.add('show');
            window.clearTimeout(toastTimer);
            toastTimer = window.setTimeout(() => toast.classList.remove('show'), 3000);
        }

        // Modal Tambah Member
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

        btnOpenAddMember?.addEventListener('click', (e) => {
            e.preventDefault();
            openModal();
        });

        btnCloseModal?.addEventListener('click', closeModal);
        btnCancelModal?.addEventListener('click', closeModal);

        modalAddMember?.addEventListener('click', (e) => {
            if (e.target === modalAddMember) closeModal();
        });

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && modalAddMember?.classList.contains('is-active')) {
                closeModal();
            }
        });

        // Muat data dashboard dari API Backend
        async function loadDashboardData() {
            try {
                const res = await fetch(getApiUrl('admin/dashboard'), { credentials: 'same-origin' });
                const json = await res.json();

                if (json.status !== 'success' || !json.data) return;

                const { stats, recent_checkins, expiring_members } = json.data;

                // 1. Update Kartu Statistik
                if (stats) {
                    const elTotal = document.getElementById('statTotalMembers');
                    const elActive = document.getElementById('statActiveMembers');
                    const elExpiring = document.getElementById('statExpiringMembers');
                    const elCheckins = document.getElementById('statTodayCheckins');

                    if (elTotal) elTotal.textContent = Number(stats.total_members).toLocaleString('id-ID');
                    if (elActive) elActive.textContent = Number(stats.active_members).toLocaleString('id-ID');
                    if (elExpiring) elExpiring.textContent = Number(stats.expiring_members).toLocaleString('id-ID');
                    if (elCheckins) elCheckins.textContent = Number(stats.today_checkins).toLocaleString('id-ID');
                }

                // 2. Update Check-in Terbaru
                const attendanceList = document.getElementById('attendanceList');
                if (attendanceList && Array.isArray(recent_checkins)) {
                    if (recent_checkins.length === 0) {
                        attendanceList.innerHTML = '<p class="muted" style="padding: 1rem; text-align: center;">Belum ada check-in hari ini</p>';
                    } else {
                        attendanceList.innerHTML = recent_checkins.map(item => `
                            <div class="attendance-row">
                                <div class="avatar ${item.avatar_color || 'peach'}" aria-hidden="true">${escapeHtml(item.initials || 'M')}</div>
                                <div>
                                    <a href="../member-detail/?id=${item.user_id}" style="color: inherit; text-decoration: none; font-weight: 700; transition: color 150ms ease;" onmouseover="this.style.color='#ff7a1a'" onmouseout="this.style.color='#fff'">${escapeHtml(item.name)}</a>
                                    <small>${escapeHtml(item.member_code)} · ${escapeHtml(item.nama_paket)}</small>
                                </div>
                                <time datetime="${escapeHtml(item.waktu)}">${escapeHtml(item.waktu)}</time>
                            </div>
                        `).join('');
                    }
                }

                // 3. Update Member Akan Berakhir
                const expiringTable = document.getElementById('expiringTableBody');
                if (expiringTable && Array.isArray(expiring_members)) {
                    if (expiring_members.length === 0) {
                        expiringTable.innerHTML = '<tr><td colspan="4" style="text-align: center; color: var(--color-muted, #888); padding: 1.5rem;">Tidak ada membership yang akan berakhir dalam 7 hari</td></tr>';
                    } else {
                        expiringTable.innerHTML = expiring_members.map(m => `
                            <tr>
                                <td>
                                    <a href="../member-detail/?id=${m.id}" style="color: inherit; text-decoration: none; font-weight: 700; transition: color 150ms ease;" onmouseover="this.style.color='#ff7a1a'" onmouseout="this.style.color='#fff'">${escapeHtml(m.name)}</a>
                                    <small>${escapeHtml(m.member_code)}</small>
                                </td>
                                <td>${escapeHtml(m.nama_paket)}</td>
                                <td>${escapeHtml(m.expires_at_formatted)}</td>
                                <td><span class="status warning-bg">${escapeHtml(m.status_badge)}</span></td>
                            </tr>
                        `).join('');
                    }
                }
            } catch (err) {
                console.warn('Gagal memuat data dashboard:', err);
            }
        }

        function escapeHtml(str) {
            return String(str ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        // Submit Pendaftaran Member Baru via API
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
                if (data.data && data.data.id) {
                    setTimeout(() => {
                        window.location.href = `../member-detail/?id=${data.data.id}`;
                    }, 800);
                } else {
                    loadDashboardData();
                }
            } catch (err) {
                showToast('Gagal terhubung ke server backend.');
            } finally {
                if (submitBtn) submitBtn.disabled = false;
            }
        });

        loadDashboardData();
    }
}
