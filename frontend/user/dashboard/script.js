// frontend/user/dashboard/script.js
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

// Inisialisasi Tampilan Kartu Member Digital
safeSetText('passMemberName', displayName);
safeSetText('passMemberCode', memberCode);
safeSetText('passFooterId', `ID: #${memberIdNum}`);

// Render QR Code Member
function renderMemberQr(code) {
    if (!code) return;
    const containers = [document.getElementById('memberQrCode'), document.getElementById('modalUserQr')].filter(Boolean);
    containers.forEach((qrContainer) => {
        qrContainer.innerHTML = '';
        if (typeof QRCode !== 'undefined') {
            try {
                new QRCode(qrContainer, {
                    text: code,
                    width: 130,
                    height: 130,
                    colorDark: '#0f172a',
                    colorLight: '#ffffff',
                    correctLevel: QRCode.CorrectLevel.M
                });
                return;
            } catch { /* Fallback di bawah */ }
        }
        // Fallback QR Image jika lib QRCode belum ready
        const img = document.createElement('img');
        img.src = `https://api.qrserver.com/v1/create-qr-code/?size=130x130&data=${encodeURIComponent(code)}`;
        img.alt = `QR Code ${code}`;
        img.width = 130;
        img.height = 130;
        img.style.width = '130px';
        img.style.height = '130px';
        img.style.display = 'block';
        img.style.borderRadius = '4px';
        qrContainer.appendChild(img);
    });
}
renderMemberQr(memberCode);

// Tombol Salin Kode Member
const btnCopyMemberCode = document.getElementById('btnCopyMemberCode');
if (btnCopyMemberCode) {
    btnCopyMemberCode.addEventListener('click', async () => {
        const code = document.getElementById('passMemberCode')?.textContent || memberCode;
        try {
            if (navigator.clipboard && navigator.clipboard.writeText) {
                await navigator.clipboard.writeText(code);
            }
            showToast(`Kode member ${code} berhasil disalin!`);
        } catch {
            showToast(`Kode member Anda: ${code}`);
        }
    });
}

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

// Confetti Effect
function launchConfetti() {
    const canvas = document.getElementById('confettiCanvas');
    if (!canvas) return;
    const ctx = canvas.getContext('2d');
    canvas.width = window.innerWidth;
    canvas.height = window.innerHeight;

    const colors = ['#ff7a1a', '#ea580c', '#4ade80', '#10b981', '#8b5cf6', '#facc15', '#ffffff'];
    const particles = [];
    const count = 90;

    for (let i = 0; i < count; i++) {
        particles.push({
            x: canvas.width / 2,
            y: canvas.height * 0.45,
            w: Math.random() * 8 + 4,
            h: Math.random() * 6 + 4,
            color: colors[Math.floor(Math.random() * colors.length)],
            vx: (Math.random() - 0.5) * 16,
            vy: (Math.random() - 1.2) * 14,
            rotation: Math.random() * 360,
            rotationSpeed: (Math.random() - 0.5) * 10,
            gravity: 0.35,
            opacity: 1
        });
    }

    let animationId;
    function renderFrame() {
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        let alive = false;

        particles.forEach(p => {
            p.x += p.vx;
            p.y += p.vy;
            p.vy += p.gravity;
            p.rotation += p.rotationSpeed;
            p.opacity -= 0.014;

            if (p.opacity > 0 && p.y < canvas.height) {
                alive = true;
                ctx.save();
                ctx.globalAlpha = Math.max(0, p.opacity);
                ctx.translate(p.x, p.y);
                ctx.rotate((p.rotation * Math.PI) / 180);
                ctx.fillStyle = p.color;
                ctx.fillRect(-p.w / 2, -p.h / 2, p.w, p.h);
                ctx.restore();
            }
        });

        if (alive) {
            animationId = requestAnimationFrame(renderFrame);
        } else {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            cancelAnimationFrame(animationId);
        }
    }
    renderFrame();
}

// ==========================================
// 2. LOAD DATA DASHBOARD DARI BACKEND
// ==========================================
async function loadDashboardData() {
    try {
        const res = await fetch(getApiUrl('user/dashboard'), {
            headers: {
                'Content-Type': 'application/json',
                'X-User-Id': String(user.id || '')
            },
            credentials: 'same-origin'
        });

        const json = await res.json();
        if (json.status !== 'success' || !json.data) return;

        const { user: userData, stats, recent_attendances, recent_packages } = json.data;

        // Sync data user terbaru
        if (userData) {
            safeSetText('welcomeName', userData.name);
            safeSetText('navUserName', userData.name);
            safeSetText('userAvatar', userData.initials || initials);
            safeSetText('mobileUserAvatar', userData.initials || initials);
            safeSetText('passMemberName', userData.name);
            const activeMemberCode = userData.member_code || memberCode;
            safeSetText('passMemberCode', activeMemberCode);
            renderMemberQr(activeMemberCode);
        }

        // Stats card
        if (stats) {
            safeSetText('dashDaysRemaining', stats.days_remaining);
            safeSetText('dashPackageName', stats.package_name);
            safeSetText('dashMembershipExpiryShort', stats.membership_expires_short || '-');
            safeSetText('dashMonthlyVisits', stats.monthly_visits ?? 0);
            safeSetText('passPackageName', stats.package_name);
            safeSetText('passExpiryDate', stats.membership_expires_short || '-');

            const isMembershipActive = (stats.days_remaining > 0) || (stats.membership_status === 'active' || stats.membership_status === 'aktif');
            const passCardStatus = document.getElementById('passCardStatus');
            if (passCardStatus) {
                passCardStatus.textContent = isMembershipActive ? 'MEMBER AKTIF' : 'EXPIRED';
                passCardStatus.className = `pass-chip-status ${isMembershipActive ? 'active' : 'expired'}`;
            }

            // Presensi hari ini
            const statusText = document.getElementById('dashTodayCheckinStatus');
            const timeText = document.getElementById('dashTodayCheckinTime');
            const noticeBox = document.getElementById('alreadyCheckedInNotice');
            const notCheckedBox = document.getElementById('notCheckedInNotice');
            const bannerSubtitle = document.getElementById('checkedInBannerSubtitle');
            const badgeStatus = document.getElementById('badgeAttendanceStatus');

            if (stats.today_checkin && stats.today_checkin.checked_in) {
                if (statusText) statusText.textContent = 'Sudah Hadir';
                if (timeText) timeText.textContent = `Pukul ${stats.today_checkin.time} WIB`;
                if (noticeBox) noticeBox.classList.remove('hidden');
                if (notCheckedBox) notCheckedBox.classList.add('hidden');
                if (bannerSubtitle) {
                    bannerSubtitle.textContent = `Presensi Anda telah dicatat oleh admin hari ini pukul ${stats.today_checkin.time} WIB (${(stats.today_checkin.session || '').split('/')[0].trim()}).`;
                }
                if (badgeStatus) {
                    badgeStatus.textContent = 'Sudah Hadir';
                    badgeStatus.className = 'status green-bg';
                }
            } else {
                if (statusText) statusText.textContent = 'Belum Hadir';
                if (timeText) timeText.textContent = 'Belum ada catatan';
                if (noticeBox) noticeBox.classList.add('hidden');
                if (notCheckedBox) notCheckedBox.classList.remove('hidden');
                if (badgeStatus) {
                    badgeStatus.textContent = isMembershipActive ? 'Aktif' : 'Nonaktif';
                    badgeStatus.className = `status ${isMembershipActive ? 'green-bg' : 'neutral-bg'}`;
                }
            }
        }

        // Recent check-in list
        const recentList = document.getElementById('dashRecentAttendanceList');
        if (recentList && Array.isArray(recent_attendances)) {
            recentList.innerHTML = '';
            const avatars = ['peach', 'blue', 'violet'];

            if (recent_attendances.length === 0) {
                recentList.innerHTML = `<p class="muted" style="text-align: center; padding: 16px;">Belum ada catatan check-in.</p>`;
            } else {
                recent_attendances.slice(0, 3).forEach((item, index) => {
                    const row = document.createElement('div');
                    row.className = 'attendance-row';
                    const colorClass = avatars[index % avatars.length];
                    row.innerHTML = `
                        <div class="avatar ${colorClass}" aria-hidden="true">${initials}</div>
                        <div>
                            <b>${(item.session || '').split('/')[0].trim()}</b>
                            <small>${item.date} · ${item.notes || 'Sesi Latihan Gym'}</small>
                        </div>
                        <time datetime="${item.time}">${item.time}</time>
                    `;
                    recentList.appendChild(row);
                });
            }
        }

        // Recent packages table
        const dashTbody = document.getElementById('dashMembershipTableBody');
        if (dashTbody && Array.isArray(recent_packages)) {
            dashTbody.innerHTML = '';
            recent_packages.forEach(pkg => {
                const tr = document.createElement('tr');
                const isAktif = pkg.status === 'Aktif';
                tr.innerHTML = `
                    <td>
                        <b>${pkg.package_name}</b>
                        <small>Paket Langganan</small>
                    </td>
                    <td>${pkg.duration}</td>
                    <td>${pkg.end_date}</td>
                    <td>
                        <span class="status ${isAktif ? 'warning-bg' : 'neutral-bg'}">
                            ${isAktif ? `${pkg.days_remaining} hari lagi` : 'Selesai'}
                        </span>
                    </td>
                `;
                dashTbody.appendChild(tr);
            });
        }
    } catch (e) {
        console.error('Gagal memuat data dashboard:', e);
    }
}

// ==========================================
// 3. AKSI KARTU MEMBER DIGITAL
// ==========================================
// Quick Action Klik Kartu Member
const quickBtnMemberCard = document.getElementById('quickBtnMemberCard');
if (quickBtnMemberCard) {
    quickBtnMemberCard.addEventListener('click', (e) => {
        e.preventDefault();
        const panel = document.getElementById('memberCardPanel');
        if (panel) {
            panel.scrollIntoView({ behavior: 'smooth', block: 'start' });
            panel.classList.add('highlight-panel');
            setTimeout(() => panel.classList.remove('highlight-panel'), 1400);
        }
        showToast('Tunjukkan QR Code member ini kepada admin untuk scan presensi.');
    });
}

// ==========================================
// 4. MOBILE PROFILE MODAL
// ==========================================
const mobileProfileModal = document.getElementById('mobileProfileModal');
const btnCloseProfileModal = document.getElementById('btnCloseProfileModal');
const btnModalLogout = document.getElementById('btnModalLogout');

function openProfileModal() {
    if (!mobileProfileModal) return;

    safeSetText('modalUserName', displayName);
    safeSetText('modalUserCode', memberCode);
    safeSetText('modalUserAvatar', initials);

    const activePkg = document.getElementById('dashPackageName')?.textContent || 'Paket Gym';
    const activeDays = document.getElementById('dashDaysRemaining')?.textContent || '0';
    safeSetText('modalPackageName', activePkg);
    safeSetText('modalDaysRemaining', activeDays);

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
loadDashboardData();
