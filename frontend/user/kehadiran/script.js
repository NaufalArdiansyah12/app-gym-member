// frontend/user/kehadiran/script.js
// ponytail: client-side auth guard via localStorage.
const raw = localStorage.getItem('user');
if (!raw) {
    window.location.replace('../../login/');
}

const user = JSON.parse(raw || '{}');
if (user.role && user.role !== 'user') {
    window.location.replace('../../admin/dashboard/');
}

// Helper API Endpoint URL (dev-server port 3000 vs Apache LAMPP)
const getApiUrl = (endpoint) => {
    return window.location.port === '3000'
        ? `http://127.0.0.1:8000/api/${endpoint}`
        : `../../../backend/public/index.php/api/${endpoint}`;
};

// 1. Inisialisasi Data Pengguna
const displayName = user.name || 'Gym Member';
const memberIdNum = user.id ? String(user.id).padStart(4, '0') : '0102';
const memberCode = user.member_code || `GYM-${memberIdNum}`;
const initials = displayName.trim().charAt(0).toUpperCase() || 'M';

const safeSetText = (id, text) => {
    const el = document.getElementById(id);
    if (el) el.textContent = text;
};

safeSetText('welcomeName', displayName);
safeSetText('navUserName', displayName);
safeSetText('userAvatar', initials);
safeSetText('mobileUserAvatar', initials);

// Mobile Drawer & Overlay Management
const sidebar = document.getElementById('sidebar');
const btnMobileMenu = document.getElementById('btnMobileMenu');
const btnSidebarClose = document.getElementById('btnSidebarClose');
const sidebarBackdrop = document.getElementById('sidebarBackdrop');

function openSidebar() {
    if (sidebar) sidebar.classList.add('is-open');
    if (sidebarBackdrop) sidebarBackdrop.classList.add('is-active');
    document.body.style.overflow = 'hidden';
}

function closeSidebar() {
    if (sidebar) sidebar.classList.remove('is-open');
    if (sidebarBackdrop) sidebarBackdrop.classList.remove('is-active');
    document.body.style.overflow = '';
}

if (btnMobileMenu) btnMobileMenu.addEventListener('click', openSidebar);
if (btnSidebarClose) btnSidebarClose.addEventListener('click', closeSidebar);
if (sidebarBackdrop) sidebarBackdrop.addEventListener('click', closeSidebar);

// Tombol Keluar (Logout)
const handleLogout = () => {
    closeSidebar();
    localStorage.removeItem('user');
    window.location.replace('../../login/');
};

const btnLogout = document.getElementById('btnLogout');
if (btnLogout) btnLogout.addEventListener('click', handleLogout);

// Toast Notifikasi
const toastElem = document.getElementById('toast');
let toastTimer = null;

function showToast(msg) {
    if (!toastElem) return;
    if (toastTimer) clearTimeout(toastTimer);
    toastElem.textContent = msg;
    toastElem.classList.add('visible');
    toastTimer = setTimeout(() => {
        toastElem.classList.remove('visible');
    }, 3200);
}

// ==========================================
// 2. LOAD DATA KEHADIRAN DARI BACKEND
// ==========================================
async function loadAttendanceData() {
    const tbody = document.getElementById('attendanceFullTableBody');
    const badge = document.getElementById('totalAttendanceBadge');

    try {
        const res = await fetch(getApiUrl('user/attendance'), {
            headers: {
                'Content-Type': 'application/json',
                'X-User-Id': String(user.id || '')
            },
            credentials: 'same-origin'
        });

        const json = await res.json();
        if (json.status !== 'success' || !json.data) return;

        const { total, attendances } = json.data;

        if (badge) {
            badge.textContent = `${total} Total Hadir`;
        }

        if (tbody && Array.isArray(attendances)) {
            tbody.innerHTML = '';

            if (attendances.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="5" class="muted" style="text-align: center; padding: 32px 16px;">
                            Belum ada riwayat sesi latihan gym. Silakan lakukan check-in di Dashboard.
                        </td>
                    </tr>
                `;
                return;
            }

            attendances.forEach(item => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td>
                        <b>${item.date}</b>
                        <small>${item.day_name}</small>
                    </td>
                    <td>${item.time} WIB</td>
                    <td>${item.session}</td>
                    <td>${item.notes || '-'}</td>
                    <td><span class="status green-bg">${item.status}</span></td>
                `;
                tbody.appendChild(tr);
            });
        }
    } catch (e) {
        console.error('Gagal memuat data presensi:', e);
        if (tbody) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="5" class="muted" style="text-align: center; padding: 24px; color: #f87171;">
                        Gagal terhubung ke server backend.
                    </td>
                </tr>
            `;
        }
    }
}

// ==========================================
// 3. MOBILE PROFILE MODAL
// ==========================================
const mobileProfileModal = document.getElementById('mobileProfileModal');
const btnCloseProfileModal = document.getElementById('btnCloseProfileModal');
const btnModalLogout = document.getElementById('btnModalLogout');

function openProfileModal() {
    if (!mobileProfileModal) return;

    safeSetText('modalUserName', displayName);
    safeSetText('modalUserCode', memberCode);
    safeSetText('modalUserAvatar', initials);

    mobileProfileModal.classList.add('is-active');
    document.body.style.overflow = 'hidden';
}

function closeProfileModal() {
    if (!mobileProfileModal) return;
    mobileProfileModal.classList.remove('is-active');
    document.body.style.overflow = '';
}

if (btnCloseProfileModal) btnCloseProfileModal.addEventListener('click', closeProfileModal);
if (mobileProfileModal) {
    mobileProfileModal.addEventListener('click', (e) => {
        if (e.target === mobileProfileModal) closeProfileModal();
    });
}
if (btnModalLogout) btnModalLogout.addEventListener('click', handleLogout);

const mobileUserAvatar = document.getElementById('mobileUserAvatar');
if (mobileUserAvatar) {
    mobileUserAvatar.style.cursor = 'pointer';
    mobileUserAvatar.addEventListener('click', openProfileModal);
}

// Inisialisasi Pertama
loadAttendanceData();
