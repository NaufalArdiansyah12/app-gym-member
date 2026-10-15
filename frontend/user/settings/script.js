// frontend/user/settings/script.js
// ponytail: client-side auth guard via localStorage.
const raw = localStorage.getItem('user');
if (!raw) {
    window.location.replace('../../login/');
}

let user = JSON.parse(raw || '{}');
if (user.role && user.role !== 'user') {
    window.location.replace('../../admin/dashboard/');
}

// Helper API Endpoint URL (dev-server port 3000 vs Apache LAMPP)
const getApiUrl = (endpoint) => {
    return window.location.port === '3000'
        ? `http://127.0.0.1:8000/api/${endpoint}`
        : `../../../backend/public/index.php/api/${endpoint}`;
};

// DOM Elements - Shell & Navigation
const safeSetText = (id, text) => {
    const el = document.getElementById(id);
    if (el) el.textContent = text;
};

function updateShellIdentity(name, code) {
    const displayName = name || 'Gym Member';
    const initials = displayName.trim().charAt(0).toUpperCase() || 'M';

    safeSetText('welcomeName', displayName);
    safeSetText('navUserName', displayName);
    safeSetText('userAvatar', initials);
    safeSetText('mobileUserAvatar', initials);

    // Identity Card
    safeSetText('identityName', displayName);
    safeSetText('identityAvatar', initials);
    if (code) safeSetText('identityMemberCode', code);
}

// Logout listeners
const handleLogout = () => {
    localStorage.removeItem('user');
    window.location.replace('../../login/');
};
document.getElementById('btnLogout')?.addEventListener('click', handleLogout);
document.getElementById('btnModalLogout')?.addEventListener('click', handleLogout);

// Mobile Sidebar & Profile Modal Handlers
const sidebar = document.getElementById('sidebar');
const sidebarBackdrop = document.getElementById('sidebarBackdrop');
const btnMobileMenu = document.getElementById('btnMobileMenu');
const btnSidebarClose = document.getElementById('btnSidebarClose');

if (btnMobileMenu && sidebar && sidebarBackdrop) {
    btnMobileMenu.addEventListener('click', () => {
        sidebar.classList.add('mobile-open');
        sidebarBackdrop.classList.add('active');
    });

    const closeSidebar = () => {
        sidebar.classList.remove('mobile-open');
        sidebarBackdrop.classList.remove('active');
    };

    btnSidebarClose?.addEventListener('click', closeSidebar);
    sidebarBackdrop.addEventListener('click', closeSidebar);
}

const mobileProfileModal = document.getElementById('mobileProfileModal');
const mobileUserAvatar = document.getElementById('mobileUserAvatar');
const btnCloseProfileModal = document.getElementById('btnCloseProfileModal');

if (mobileProfileModal && mobileUserAvatar) {
    mobileUserAvatar.addEventListener('click', () => {
        mobileProfileModal.classList.add('active');
    });
    btnCloseProfileModal?.addEventListener('click', () => {
        mobileProfileModal.classList.remove('active');
    });
}

// Toast Notification
const toastEl = document.getElementById('toast');
let toastTimer;
function showToast(message) {
    if (!toastEl) return;
    toastEl.textContent = message;
    toastEl.classList.add('show');
    window.clearTimeout(toastTimer);
    toastTimer = window.setTimeout(() => toastEl.classList.remove('show'), 3000);
}

// Form Elements
const inputName = document.getElementById('inputName');
const inputMemberCode = document.getElementById('inputMemberCode');
const inputPhone = document.getElementById('inputPhone');
const inputGender = document.getElementById('inputGender');
const inputEmergency = document.getElementById('inputEmergency');

const formatGender = (g) => {
    const v = String(g || '').toLowerCase();
    if (v === 'wanita') return 'Wanita (Perempuan)';
    if (v === 'pria') return 'Pria (Laki-laki)';
    return '-';
};

const passwordForm = document.getElementById('passwordForm');
const inputCurrentPassword = document.getElementById('inputCurrentPassword');
const inputNewPassword = document.getElementById('inputNewPassword');
const inputConfirmPassword = document.getElementById('inputConfirmPassword');
const btnChangePassword = document.getElementById('btnChangePassword');

// Inisialisasi awal dari localStorage
updateShellIdentity(user.name, user.member_code);

// Toggle Password Visibility
document.querySelectorAll('.btn-toggle-pw').forEach(btn => {
    btn.addEventListener('click', () => {
        const targetId = btn.getAttribute('data-target');
        const input = document.getElementById(targetId);
        if (!input) return;

        const isPassword = input.type === 'password';
        input.type = isPassword ? 'text' : 'password';
        btn.style.color = isPassword ? '#ff7a1a' : '#94a3b8';
    });
});

// Load Profile Data from API
async function loadUserProfile() {
    try {
        const headers = { 'Accept': 'application/json' };
        if (user.id) headers['X-User-Id'] = String(user.id);

        const res = await fetch(getApiUrl('user/profile'), { headers });
        if (!res.ok) throw new Error(`HTTP ${res.status}`);

        const json = await res.json();
        if (json.status !== 'success' || !json.data || !json.data.user) {
            throw new Error(json.error || 'Gagal memuat profil');
        }

        const u = json.data.user;
        user = { ...user, ...u };
        localStorage.setItem('user', JSON.stringify(user));

        // Populate Form
        inputName.value = u.name || '';
        inputMemberCode.value = u.member_code || '';
        inputPhone.value = u.phone || '';
        inputGender.value = formatGender(u.gender);
        inputEmergency.value = u.emergency_contact || '';

        // Populate Identity Card
        updateShellIdentity(u.name, u.member_code);
        safeSetText('identityEmail', u.email || '-');
        safeSetText('identityPackage', u.package_name || 'Tanpa Paket');

        const statusBadge = document.getElementById('identityStatusBadge');
        if (statusBadge) {
            const isAct = u.membership_status === 'active';
            statusBadge.className = isAct ? 'status green-bg' : 'status warning-bg';
            statusBadge.textContent = isAct ? 'Member Aktif' : 'Non-Aktif / Expired';
        }

        // Modal mobile sync
        safeSetText('modalUserName', u.name);
        safeSetText('modalUserCode', u.member_code);
        safeSetText('modalPackageName', u.package_name || '-');
    } catch (err) {
        console.error('Error fetching user profile:', err);
        showToast('Menggunakan data lokal (gagal memuat data terbaru).');

        // Fallback to local storage
        inputName.value = user.name || '';
        inputMemberCode.value = user.member_code || '';
        inputPhone.value = user.phone || '';
        inputGender.value = formatGender(user.gender);
        inputEmergency.value = user.emergency_contact || '';
    }
}

// Data diri tidak dapat diubah member; hanya kata sandi (panel keamanan) yang bisa diubah sendiri.

// Modal Password Result Elements
const modalPasswordResult = document.getElementById('modalPasswordResult');
const modalResultIconBadge = document.getElementById('modalResultIconBadge');
const modalResultIcon = document.getElementById('modalResultIcon');
const modalResultTitle = document.getElementById('modalResultTitle');
const modalResultMessage = document.getElementById('modalResultMessage');
const btnModalResultClose = document.getElementById('btnModalResultClose');

function showPasswordModal(isSuccess, title, message) {
    if (!modalPasswordResult) return;

    if (isSuccess) {
        modalResultIconBadge.className = 'result-icon-badge success-badge';
        modalResultIcon.innerHTML = '<use href="#icon-check"/>';
        modalResultTitle.textContent = title || 'Kata Sandi Berhasil Diperbarui!';
        modalResultTitle.style.color = '#f9fafb';
        modalResultMessage.textContent = message || 'Kata sandi akun Anda telah berhasil diperbarui.';
        btnModalResultClose.className = 'primary-button btn-full';
        btnModalResultClose.textContent = 'OK, Selesai';
    } else {
        modalResultIconBadge.className = 'result-icon-badge error-badge';
        modalResultIcon.innerHTML = '<use href="#icon-close"/>';
        modalResultTitle.textContent = title || 'Gagal Memperbarui Kata Sandi';
        modalResultTitle.style.color = '#fca5a5';
        modalResultMessage.textContent = message || 'Terjadi kesalahan saat memproses pembaruan kata sandi.';
        btnModalResultClose.className = 'primary-button btn-full btn-error-style';
        btnModalResultClose.textContent = 'Coba Lagi';
    }

    modalPasswordResult.classList.add('is-active');
    modalPasswordResult.setAttribute('aria-hidden', 'false');
}

function hidePasswordModal() {
    if (!modalPasswordResult) return;
    modalPasswordResult.classList.remove('is-active');
    modalPasswordResult.setAttribute('aria-hidden', 'true');
}

btnModalResultClose?.addEventListener('click', hidePasswordModal);
modalPasswordResult?.addEventListener('click', (e) => {
    if (e.target === modalPasswordResult) hidePasswordModal();
});
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && modalPasswordResult?.classList.contains('is-active')) {
        hidePasswordModal();
    }
});

// Handle Change Password Form Submit
passwordForm.addEventListener('submit', async (e) => {
    e.preventDefault();

    const currentPassword = inputCurrentPassword.value;
    const newPassword = inputNewPassword.value;
    const confirmPassword = inputConfirmPassword.value;

    if (!currentPassword || !newPassword || !confirmPassword) {
        showPasswordModal(false, 'Kolom Belum Lengkap', 'Semua kolom kata sandi wajib diisi.');
        return;
    }

    if (newPassword.length < 6) {
        showPasswordModal(false, 'Kata Sandi Terlalu Pendek', 'Kata sandi baru minimal harus 6 karakter.');
        return;
    }

    if (newPassword !== confirmPassword) {
        showPasswordModal(false, 'Konfirmasi Tidak Cocok', 'Konfirmasi kata sandi baru tidak cocok dengan kata sandi baru.');
        return;
    }

    btnChangePassword.disabled = true;
    btnChangePassword.innerHTML = 'Memproses…';

    try {
        const payload = {
            current_password: currentPassword,
            new_password: newPassword,
            confirm_password: confirmPassword,
        };

        const headers = {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
        };
        if (user.id) headers['X-User-Id'] = String(user.id);

        const res = await fetch(getApiUrl('user/password'), {
            method: 'POST',
            headers,
            body: JSON.stringify(payload),
        });

        const json = await res.json();
        if (!res.ok || json.error) {
            throw new Error(json.error || 'Gagal mengubah kata sandi');
        }

        showPasswordModal(true, 'Kata Sandi Berhasil Diperbarui!', json.message || 'Kata sandi akun Anda telah diperbarui. Silakan gunakan kata sandi baru ini saat login berikutnya.');
        passwordForm.reset();
    } catch (err) {
        console.error('Change password error:', err);
        showPasswordModal(false, 'Gagal Memperbarui Kata Sandi', err.message || 'Terjadi kesalahan pada server saat memperbarui kata sandi.');
    } finally {
        btnChangePassword.disabled = false;
        btnChangePassword.innerHTML = '<svg class="icon icon-sm" aria-hidden="true" focusable="false"><use href="#icon-check"/></svg> Perbarui Kata Sandi';
    }
});

// Inisialisasi
loadUserProfile();
