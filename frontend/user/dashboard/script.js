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

// 1. Inisialisasi Data Pengguna
const displayName = user.name || 'Gym Member';
const memberIdNum = user.id ? String(user.id).padStart(4, '0') : '0102';
const memberCode = `GYM-${memberIdNum}`;

const safeSetText = (id, text) => {
    const el = document.getElementById(id);
    if (el) el.textContent = text;
};

safeSetText('welcomeName', displayName);
safeSetText('navUserName', displayName);
safeSetText('memActiveCode', memberCode);

// Avatar inisial
const initials = displayName.trim().charAt(0).toUpperCase() || 'M';
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

if (btnMobileMenu) {
    btnMobileMenu.addEventListener('click', openSidebar);
}
if (btnSidebarClose) {
    btnSidebarClose.addEventListener('click', closeSidebar);
}
if (sidebarBackdrop) {
    sidebarBackdrop.addEventListener('click', closeSidebar);
}

// Tombol Keluar (Logout)
const btnLogout = document.getElementById('btnLogout');
if (btnLogout) {
    btnLogout.addEventListener('click', () => {
        closeSidebar();
        localStorage.removeItem('user');
        window.location.replace('../../login/');
    });
}

// Logo Klik -> Kembali ke Dashboard
const brandLogoBtn = document.getElementById('brandLogoBtn');
if (brandLogoBtn) {
    brandLogoBtn.addEventListener('click', () => {
        closeSidebar();
        switchView('view-dashboard');
    });
}

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

// Helper Format Tanggal
const MONTH_NAMES = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
const DAY_NAMES = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];

function formatDateIndo(dateStr) {
    const d = new Date(dateStr);
    if (isNaN(d.getTime())) return dateStr;
    return `${d.getDate()} ${MONTH_NAMES[d.getMonth()]} ${d.getFullYear()}`;
}

function formatDateWithDay(dateStr) {
    const d = new Date(dateStr);
    if (isNaN(d.getTime())) return { dayName: 'Hari', formatted: dateStr };
    return {
        dayName: DAY_NAMES[d.getDay()],
        formatted: `${d.getDate()} ${MONTH_NAMES[d.getMonth()]} ${d.getFullYear()}`
    };
}

// ==========================================
// 2. DATA SISA WAKTU & MEMBERSHIP
// ==========================================
const MEMBERSHIP_STORAGE_KEY = `gym_membership_data_${user.id || user.email || 'guest'}`;

function getMembershipData() {
    const saved = localStorage.getItem(MEMBERSHIP_STORAGE_KEY);
    if (saved) {
        try {
            return JSON.parse(saved);
        } catch (e) {
            console.error('Error parsing membership data', e);
        }
    }

    const today = new Date();
    const start = new Date(today);
    start.setDate(today.getDate() - 24);

    const end = new Date(today);
    end.setDate(today.getDate() + 36); // 36 hari lagi

    const defaultList = [
        {
            id: 1,
            packageName: 'Paket Platinum 2 Bulan',
            startDate: start.toISOString().split('T')[0],
            endDate: end.toISOString().split('T')[0],
            duration: '60 Hari',
            price: 'Rp 450.000',
            paymentMethod: 'QRIS',
            status: 'Aktif'
        },
        {
            id: 2,
            packageName: 'Paket Regular 1 Bulan',
            startDate: '2026-07-01',
            endDate: '2026-07-31',
            duration: '30 Hari',
            price: 'Rp 250.000',
            paymentMethod: 'Transfer Bank',
            status: 'Selesai'
        },
        {
            id: 3,
            packageName: 'Paket Trial 7 Hari',
            startDate: '2026-06-15',
            endDate: '2026-06-22',
            duration: '7 Hari',
            price: 'Rp 75.000',
            paymentMethod: 'Tunai',
            status: 'Selesai'
        }
    ];

    localStorage.setItem(MEMBERSHIP_STORAGE_KEY, JSON.stringify(defaultList));
    return defaultList;
}

function renderMembership() {
    const list = getMembershipData();
    const active = list.find(m => m.status === 'Aktif') || list[0];

    if (active) {
        const today = new Date();
        today.setHours(0, 0, 0, 0);

        const endDate = new Date(active.endDate);
        endDate.setHours(23, 59, 59, 999);

        const diffTime = endDate.getTime() - today.getTime();
        const daysRemaining = Math.max(0, Math.ceil(diffTime / (1000 * 60 * 60 * 24)));

        // Stats Card Dashboard
        safeSetText('dashDaysRemaining', daysRemaining);
        safeSetText('dashPackageName', active.packageName);
        safeSetText('dashMembershipExpiryShort', formatDateIndo(active.endDate));

        // View 3: Halaman Member
        safeSetText('memActiveName', active.packageName);
        safeSetText('memActiveDays', daysRemaining);

        // Render Table di Dashboard (Left Bottom Panel)
        const dashTbody = document.getElementById('dashMembershipTableBody');
        if (dashTbody) {
            dashTbody.innerHTML = '';
            list.forEach(item => {
                const tr = document.createElement('tr');
                const isAktif = item.status === 'Aktif';
                tr.innerHTML = `
                    <td>
                        <b>${item.packageName}</b>
                        <small>Paket Langganan</small>
                    </td>
                    <td>${item.duration}</td>
                    <td>${formatDateIndo(item.endDate)}</td>
                    <td>
                        <span class="status ${isAktif ? 'warning-bg' : 'neutral-bg'}">
                            ${isAktif ? `${daysRemaining} hari lagi` : 'Selesai'}
                        </span>
                    </td>
                `;
                dashTbody.appendChild(tr);
            });
        }

        // Render Table Lengkap di Halaman Member
        const fullTbody = document.getElementById('membershipFullTableBody');
        if (fullTbody) {
            fullTbody.innerHTML = '';
            list.forEach(item => {
                const tr = document.createElement('tr');
                const isAktif = item.status === 'Aktif';
                tr.innerHTML = `
                    <td><b>${item.packageName}</b></td>
                    <td>${formatDateIndo(item.startDate)} - ${formatDateIndo(item.endDate)}</td>
                    <td>${item.duration}</td>
                    <td><strong style="color: #ff9142;">${item.price}</strong></td>
                    <td>${item.paymentMethod}</td>
                    <td>
                        <span class="status ${isAktif ? 'green-bg' : 'neutral-bg'}">
                            ${item.status}
                        </span>
                    </td>
                `;
                fullTbody.appendChild(tr);
            });
        }
    }
}

// ==========================================
// 3. DATA ABSENSI / PRESENSI
// ==========================================
const ATTENDANCE_STORAGE_KEY = `gym_attendance_records_${user.id || user.email || 'guest'}`;

function getAttendanceRecords() {
    const saved = localStorage.getItem(ATTENDANCE_STORAGE_KEY);
    if (saved) {
        try {
            return JSON.parse(saved);
        } catch (e) {
            console.error('Error parsing attendance data', e);
        }
    }

    const today = new Date();
    const d1 = new Date(today); d1.setDate(today.getDate() - 1);
    const d2 = new Date(today); d2.setDate(today.getDate() - 3);
    const d3 = new Date(today); d3.setDate(today.getDate() - 5);

    const defaultRecords = [
        {
            id: 1,
            date: d1.toISOString().split('T')[0],
            time: '08:35',
            session: 'Gym Umum / Fitness Regular',
            notes: 'Upper Body Workout',
            status: 'Hadir'
        },
        {
            id: 2,
            date: d2.toISOString().split('T')[0],
            time: '16:45',
            session: 'Free Weights & Barbell Area',
            notes: 'Leg Day & Squat',
            status: 'Hadir'
        },
        {
            id: 3,
            date: d3.toISOString().split('T')[0],
            time: '09:12',
            session: 'Cardio & Treadmill Zone',
            notes: 'HIIT Cardio 40m',
            status: 'Hadir'
        }
    ];

    localStorage.setItem(ATTENDANCE_STORAGE_KEY, JSON.stringify(defaultRecords));
    return defaultRecords;
}

function saveAttendanceRecords(records) {
    localStorage.setItem(ATTENDANCE_STORAGE_KEY, JSON.stringify(records));
}

function renderAttendance() {
    const records = getAttendanceRecords();
    const todayStr = new Date().toISOString().split('T')[0];

    const todayRecord = records.find(r => r.date === todayStr);

    const btnSubmit = document.getElementById('btnSubmitDashboardCheckin');
    const statusText = document.getElementById('dashTodayCheckinStatus');
    const timeText = document.getElementById('dashTodayCheckinTime');
    const noticeBox = document.getElementById('alreadyCheckedInNotice');
    const bannerSubtitle = document.getElementById('checkedInBannerSubtitle');
    const badgeStatus = document.getElementById('badgeAttendanceStatus');

    if (todayRecord) {
        if (statusText) statusText.textContent = 'Sudah Hadir';
        if (timeText) timeText.textContent = `Pukul ${todayRecord.time} WIB`;
        if (noticeBox) noticeBox.classList.remove('hidden');
        if (bannerSubtitle) {
            bannerSubtitle.textContent = `Anda sudah melakukan check-in hari ini pada pukul ${todayRecord.time} WIB (${todayRecord.session.split('/')[0].trim()}).`;
        }
        if (badgeStatus) {
            badgeStatus.textContent = 'Hadir';
            badgeStatus.className = 'status green-bg';
        }
        if (btnSubmit) {
            btnSubmit.innerHTML = `
                <svg class="icon" aria-hidden="true" focusable="false"><use href="#icon-check"/></svg>
                <span>Anda Sudah Presensi Hari Ini</span>
            `;
            btnSubmit.disabled = true;
        }
    } else {
        if (statusText) statusText.textContent = 'Belum Hadir';
        if (timeText) timeText.textContent = 'Belum ada catatan';
        if (noticeBox) noticeBox.classList.add('hidden');
        if (badgeStatus) {
            badgeStatus.textContent = 'Aktif';
            badgeStatus.className = 'status green-bg';
        }
        if (btnSubmit) {
            btnSubmit.innerHTML = `
                <svg class="icon" aria-hidden="true" focusable="false"><use href="#icon-check"/></svg>
                <span>Konfirmasi Check-in Masuk</span>
            `;
            btnSubmit.disabled = false;
        }
    }

    // Hitung Kunjungan Bulan Ini
    const now = new Date();
    const currentMonth = now.getMonth();
    const currentYear = now.getFullYear();

    const monthlyVisits = records.filter(r => {
        const d = new Date(r.date);
        return d.getMonth() === currentMonth && d.getFullYear() === currentYear;
    }).length;

    safeSetText('dashMonthlyVisits', monthlyVisits);
    safeSetText('totalAttendanceBadge', `${records.length} Total Hadir`);

    // 1. Render List "Check-in Hari Ini" pada Dashboard (Matches Admin Attendance List)
    const recentList = document.getElementById('dashRecentAttendanceList');
    if (recentList) {
        recentList.innerHTML = '';
        const avatars = ['peach', 'blue', 'violet'];
        const recentThree = records.slice(0, 3);

        if (recentThree.length === 0) {
            recentList.innerHTML = `<p class="muted" style="text-align: center; padding: 16px;">Belum ada catatan check-in.</p>`;
        } else {
            recentThree.forEach((item, index) => {
                const row = document.createElement('div');
                row.className = 'attendance-row';
                const colorClass = avatars[index % avatars.length];
                const d = formatDateWithDay(item.date);
                row.innerHTML = `
                    <div class="avatar ${colorClass}" aria-hidden="true">${initials}</div>
                    <div>
                        <b>${item.session.split('/')[0].trim()}</b>
                        <small>${d.formatted} · ${item.notes || 'Sesi Latihan Gym'}</small>
                    </div>
                    <time datetime="${item.time}">${item.time}</time>
                `;
                recentList.appendChild(row);
            });
        }
    }

    // 2. Render Full Table di View Riwayat Kehadiran
    const fullTbody = document.getElementById('attendanceFullTableBody');
    if (fullTbody) {
        fullTbody.innerHTML = '';
        if (records.length === 0) {
            fullTbody.innerHTML = `<tr><td colspan="5" style="text-align: center; color: #b8beca; padding: 24px;">Belum ada riwayat kehadiran.</td></tr>`;
        } else {
            records.forEach(item => {
                const tr = document.createElement('tr');
                const d = formatDateWithDay(item.date);
                tr.innerHTML = `
                    <td>
                        <b>${d.formatted}</b>
                        <small>${d.dayName}</small>
                    </td>
                    <td><strong style="color: #f3f4f6;">${item.time} WIB</strong></td>
                    <td>${item.session}</td>
                    <td>${item.notes || '-'}</td>
                    <td><span class="status green-bg">Hadir</span></td>
                `;
                fullTbody.appendChild(tr);
            });
        }
    }
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

// Form Check-in Submit
const formCheckIn = document.getElementById('formCheckInDashboard');
if (formCheckIn) {
    formCheckIn.addEventListener('submit', (e) => {
        e.preventDefault();
        const todayStr = new Date().toISOString().split('T')[0];
        const records = getAttendanceRecords();

        if (records.some(r => r.date === todayStr)) {
            showToast('Anda sudah melakukan check-in untuk hari ini.');
            return;
        }

        const session = document.getElementById('dashSessionType')?.value || 'Gym Umum / Fitness Regular';
        const notesInput = document.getElementById('dashCheckinNotes');
        const notes = notesInput ? notesInput.value.trim() : '';

        const now = new Date();
        const timeStr = `${String(now.getHours()).padStart(2, '0')}:${String(now.getMinutes()).padStart(2, '0')}`;

        records.unshift({
            id: Date.now(),
            date: todayStr,
            time: timeStr,
            session: session,
            notes: notes,
            status: 'Hadir'
        });

        saveAttendanceRecords(records);
        renderAttendance();
        if (notesInput) notesInput.value = '';

        launchConfetti();
        showToast(`Presensi berhasil dicatat pukul ${timeStr} WIB! Selamat berolahraga.`);
    });
}

// Quick Action Check-in Click
const quickBtnCheckIn = document.getElementById('quickBtnCheckIn');
if (quickBtnCheckIn) {
    quickBtnCheckIn.addEventListener('click', () => {
        const formInput = document.getElementById('dashCheckinNotes');
        if (formInput) formInput.focus();
        showToast('Silakan pilih sesi dan konfirmasi check-in di panel sebelah.');
    });
}

// ==========================================
// 4. VIEW NAVIGATION SWITCHER
// ==========================================
const navItems = document.querySelectorAll('.nav-item');
const viewPanels = document.querySelectorAll('.view-panel');
const viewLinks = document.querySelectorAll('.view-link');

function switchView(targetViewId) {
    closeSidebar();

    navItems.forEach(item => {
        if (item.getAttribute('data-view') === targetViewId) {
            item.classList.add('active');
        } else {
            item.classList.remove('active');
        }
    });

    viewPanels.forEach(panel => {
        if (panel.id === targetViewId) {
            panel.classList.add('active');
        } else {
            panel.classList.remove('active');
        }
    });

    const eyebrow = document.getElementById('pageEyebrow');
    const heading = document.getElementById('pageHeading');
    const subtitle = document.getElementById('pageSubtitle');
    const today = new Date();
    const dStr = `${today.getDate()} ${MONTH_NAMES[today.getMonth()]} ${today.getFullYear()}`.toUpperCase();

    if (targetViewId === 'view-dashboard') {
        if (eyebrow) eyebrow.textContent = `DASHBOARD · ${dStr}`;
        if (heading) heading.innerHTML = `Selamat pagi, <span id="welcomeName">${displayName}</span>`;
        if (subtitle) subtitle.textContent = 'Pantau status membership dan absensi gym Anda.';
    } else if (targetViewId === 'view-attendance') {
        if (eyebrow) eyebrow.textContent = `RIWAYAT KEHADIRAN · ${dStr}`;
        if (heading) heading.textContent = 'Riwayat Seluruh Kehadiran';
        if (subtitle) subtitle.textContent = 'Daftar lengkap sesi latihan gym yang telah Anda ikuti.';
    } else if (targetViewId === 'view-membership') {
        if (eyebrow) eyebrow.textContent = `HALAMAN MEMBER · ${dStr}`;
        if (heading) heading.textContent = 'Status & Sisa Waktu Membership';
        if (subtitle) subtitle.textContent = 'Informasi detail paket aktif dan sisa durasi membership Anda.';
    }

    window.location.hash = targetViewId.replace('view-', '');
    window.scrollTo({ top: 0, behavior: 'smooth' });

    updateBottomNavActive(targetViewId);
    renderMembership();
    renderAttendance();
}

// ==========================================
// 5. MOBILE BOTTOM NAV & PROFILE MODAL
// ==========================================
const bottomNavBtns = document.querySelectorAll('.nav-circle-btn');

function updateBottomNavActive(viewId) {
    bottomNavBtns.forEach(btn => btn.classList.remove('active'));
    if (viewId === 'view-dashboard') {
        const btn = document.getElementById('btnBottomHome');
        if (btn) btn.classList.add('active');
    } else if (viewId === 'view-attendance') {
        const btn = document.getElementById('btnBottomAttendance');
        if (btn) btn.classList.add('active');
    } else if (viewId === 'view-membership') {
        const btn = document.getElementById('btnBottomMembership');
        if (btn) btn.classList.add('active');
    }
}

// Mobile Bottom Nav: Button 1 (Home)
const btnBottomHome = document.getElementById('btnBottomHome');
if (btnBottomHome) {
    btnBottomHome.addEventListener('click', () => {
        closeProfileModal();
        switchView('view-dashboard');
    });
}

// Mobile Bottom Nav: Button 2 (Dumbbell - Quick Check-in)
const btnBottomCheckin = document.getElementById('btnBottomCheckin');
if (btnBottomCheckin) {
    btnBottomCheckin.addEventListener('click', () => {
        closeProfileModal();
        switchView('view-dashboard');
        bottomNavBtns.forEach(btn => btn.classList.remove('active'));
        btnBottomCheckin.classList.add('active');

        setTimeout(() => {
            const formCard = document.getElementById('formCheckInDashboard');
            if (formCard) {
                formCard.scrollIntoView({ behavior: 'smooth', block: 'center' });
                const input = document.getElementById('dashCheckinNotes');
                if (input) input.focus();
            }
        }, 150);
        showToast('Pilih sesi dan tekan konfirmasi check-in.');
    });
}

// Mobile Bottom Nav: Button 3 (Riwayat Kehadiran)
const btnBottomAttendance = document.getElementById('btnBottomAttendance');
if (btnBottomAttendance) {
    btnBottomAttendance.addEventListener('click', () => {
        closeProfileModal();
        switchView('view-attendance');
    });
}

// Mobile Bottom Nav: Button 4 (Halaman Member / Sisa Waktu)
const btnBottomMembership = document.getElementById('btnBottomMembership');
if (btnBottomMembership) {
    btnBottomMembership.addEventListener('click', () => {
        closeProfileModal();
        switchView('view-membership');
    });
}

// Mobile Bottom Nav: Button 5 (Profile Modal)
const btnBottomProfile = document.getElementById('btnBottomProfile');
const mobileProfileModal = document.getElementById('mobileProfileModal');
const btnCloseProfileModal = document.getElementById('btnCloseProfileModal');
const btnModalLogout = document.getElementById('btnModalLogout');

function openProfileModal() {
    if (!mobileProfileModal) return;

    safeSetText('modalUserName', displayName);
    safeSetText('modalUserCode', memberCode);
    safeSetText('modalUserAvatar', initials);

    const activePkg = document.getElementById('dashPackageName')?.textContent || 'Paket Platinum 2 Bulan';
    const activeDays = document.getElementById('dashDaysRemaining')?.textContent || '36';
    safeSetText('modalPackageName', activePkg);
    safeSetText('modalDaysRemaining', activeDays);

    bottomNavBtns.forEach(btn => btn.classList.remove('active'));
    if (btnBottomProfile) btnBottomProfile.classList.add('active');

    mobileProfileModal.classList.add('is-active');
    document.body.style.overflow = 'hidden';
}

function closeProfileModal() {
    if (!mobileProfileModal) return;
    mobileProfileModal.classList.remove('is-active');
    document.body.style.overflow = '';

    const activePanel = document.querySelector('.view-panel.active');
    if (activePanel) {
        updateBottomNavActive(activePanel.id);
    }
}

if (btnBottomProfile) {
    btnBottomProfile.addEventListener('click', () => {
        if (mobileProfileModal && mobileProfileModal.classList.contains('is-active')) {
            closeProfileModal();
        } else {
            openProfileModal();
        }
    });
}

if (btnCloseProfileModal) {
    btnCloseProfileModal.addEventListener('click', closeProfileModal);
}

if (mobileProfileModal) {
    mobileProfileModal.addEventListener('click', (e) => {
        if (e.target === mobileProfileModal) {
            closeProfileModal();
        }
    });
}

if (btnModalLogout) {
    btnModalLogout.addEventListener('click', () => {
        localStorage.removeItem('user');
        window.location.replace('../../login/');
    });
}

const mobileUserAvatar = document.getElementById('mobileUserAvatar');
if (mobileUserAvatar) {
    mobileUserAvatar.style.cursor = 'pointer';
    mobileUserAvatar.addEventListener('click', openProfileModal);
}

navItems.forEach(item => {
    item.addEventListener('click', (e) => {
        e.preventDefault();
        const targetView = item.getAttribute('data-view');
        if (targetView) switchView(targetView);
    });
});

viewLinks.forEach(link => {
    link.addEventListener('click', (e) => {
        e.preventDefault();
        const targetView = link.getAttribute('data-view');
        if (targetView) switchView(targetView);
    });
});

// Deteksi Hash saat Halaman Dimuat
window.addEventListener('DOMContentLoaded', () => {
    const hash = window.location.hash.replace('#', '');
    if (hash === 'attendance') {
        switchView('view-attendance');
    } else if (hash === 'membership') {
        switchView('view-membership');
    } else {
        switchView('view-dashboard');
    }
});

// Inisialisasi Pertama
renderMembership();
renderAttendance();
