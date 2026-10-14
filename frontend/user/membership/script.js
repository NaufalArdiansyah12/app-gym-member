// frontend/user/membership/script.js
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
safeSetText('memActiveCode', memberCode);
safeSetText('passMemberName', displayName);
safeSetText('passMemberCode', memberCode);
safeSetText('passFooterId', `ID: #${memberIdNum}`);

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
// 2. RENDER QR CODE & AKSI DOWNLOAD / SALIN
// ==========================================
function renderMemberQr(code) {
    if (!code) return;
    const containers = [
        document.getElementById('memberQrCode'),
        document.getElementById('modalUserQr')
    ].filter(Boolean);

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
            } catch { /* Fallback */ }
        }
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

// Fungsi Download QR Code Resmi
async function downloadMemberQr() {
    const code = document.getElementById('passMemberCode')?.textContent || memberCode;
    const name = document.getElementById('passMemberName')?.textContent || displayName;
    const pkg = document.getElementById('passPackageName')?.textContent || 'Member';
    const qrHolder = document.getElementById('memberQrCode');
    if (!qrHolder) {
        showToast('Kontainer QR tidak ditemukan.');
        return;
    }

    try {
        const sourceCanvas = qrHolder.querySelector('canvas');
        const sourceImg = qrHolder.querySelector('img');

        const exportCanvas = document.createElement('canvas');
        const padding = 28;
        const qrSize = 300;
        const totalWidth = qrSize + (padding * 2);
        const totalHeight = qrSize + (padding * 2) + 90;
        exportCanvas.width = totalWidth;
        exportCanvas.height = totalHeight;

        const ctx = exportCanvas.getContext('2d');
        if (!ctx) throw new Error('Canvas 2D context tidak tersedia');

        // Latar belakang putih bersih
        ctx.fillStyle = '#ffffff';
        ctx.fillRect(0, 0, totalWidth, totalHeight);

        // Header strip warna aksen gym
        ctx.fillStyle = '#ff7a1a';
        ctx.fillRect(0, 0, totalWidth, 8);

        // Header title
        ctx.fillStyle = '#ea580c';
        ctx.font = 'bold 15px system-ui, -apple-system, sans-serif';
        ctx.textAlign = 'center';
        ctx.fillText('USER GYM · PASS PRESENSI', totalWidth / 2, 34);

        // Gambar QR Code
        if (sourceCanvas) {
            ctx.imageSmoothingEnabled = false;
            ctx.drawImage(sourceCanvas, padding, padding + 18, qrSize, qrSize);
        } else if (sourceImg && sourceImg.src) {
            const img = new Image();
            img.crossOrigin = 'anonymous';
            await new Promise((resolve, reject) => {
                img.onload = resolve;
                img.onerror = reject;
                img.src = sourceImg.src;
            });
            ctx.imageSmoothingEnabled = false;
            ctx.drawImage(img, padding, padding + 18, qrSize, qrSize);
        } else {
            throw new Error('QR belum di-render');
        }

        // Box info kode member di bawah QR
        const boxY = qrSize + padding + 28;
        ctx.fillStyle = '#f8fafc';
        ctx.fillRect(padding, boxY, qrSize, 56);
        ctx.strokeStyle = '#e2e8f0';
        ctx.lineWidth = 1;
        ctx.strokeRect(padding, boxY, qrSize, 56);

        // Teks Kode Member
        ctx.fillStyle = '#0f172a';
        ctx.font = 'bold 20px ui-monospace, SFMono-Regular, monospace';
        ctx.textAlign = 'center';
        ctx.fillText(code, totalWidth / 2, boxY + 26);

        // Teks Nama Member & Paket
        ctx.fillStyle = '#64748b';
        ctx.font = '500 12px system-ui, -apple-system, sans-serif';
        ctx.fillText(`${name} · ${pkg}`, totalWidth / 2, boxY + 44);

        // Trigger Download Anchor
        const link = document.createElement('a');
        link.download = `QR-Member-${code}.png`;
        link.href = exportCanvas.toDataURL('image/png');
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        showToast(`QR Code (${code}) berhasil diunduh!`);
    } catch (err) {
        console.error('Gagal download QR:', err);
        const sourceImg = qrHolder.querySelector('img');
        if (sourceImg && sourceImg.src) {
            const fallbackLink = document.createElement('a');
            fallbackLink.download = `QR-Member-${code}.png`;
            fallbackLink.href = sourceImg.src;
            fallbackLink.target = '_blank';
            document.body.appendChild(fallbackLink);
            fallbackLink.click();
            document.body.removeChild(fallbackLink);
            showToast(`QR Code (${code}) berhasil diunduh!`);
        } else {
            showToast('Gagal mengunduh QR Code. Silakan coba lagi.');
        }
    }
}

const btnDownloadQr = document.getElementById('btnDownloadQr');
if (btnDownloadQr) btnDownloadQr.addEventListener('click', downloadMemberQr);

const btnDownloadQrInline = document.getElementById('btnDownloadQrInline');
if (btnDownloadQrInline) btnDownloadQrInline.addEventListener('click', downloadMemberQr);

// ==========================================
// 3. LOAD DATA MEMBERSHIP DARI BACKEND
// ==========================================
async function loadMembershipData() {
    const tbody = document.getElementById('membershipFullTableBody');

    try {
        const res = await fetch(getApiUrl('user/membership'), {
            headers: {
                'Content-Type': 'application/json',
                'X-User-Id': String(user.id || '')
            },
            credentials: 'same-origin'
        });

        const json = await res.json();
        if (json.status !== 'success' || !json.data) return;

        const { active, history } = json.data;

        // Card stats & pass card
        if (active) {
            const activeMemberCode = active.member_code || memberCode;
            const isAktif = active.status === 'Aktif' || active.days_remaining > 0;

            safeSetText('memActiveName', active.package_name);
            safeSetText('memActiveDays', active.days_remaining);
            safeSetText('memActiveCode', activeMemberCode);
            if (active.duration) safeSetText('memActiveDuration', active.duration);

            const memActiveStatus = document.getElementById('memActiveStatus');
            if (memActiveStatus) {
                memActiveStatus.textContent = isAktif ? 'Aktif' : 'Nonaktif';
                memActiveStatus.className = `status ${isAktif ? 'green-bg' : 'neutral-bg'}`;
            }

            if (active.end_date_indo) {
                safeSetText('memPeriodSub', `Berlaku sampai ${active.end_date_indo}`);
            } else if (isAktif) {
                safeSetText('memPeriodSub', 'Masa aktif langganan sedang berjalan');
            } else {
                safeSetText('memPeriodSub', 'Masa aktif langganan telah berakhir');
            }

            safeSetText('passMemberName', displayName);
            safeSetText('passMemberCode', activeMemberCode);
            safeSetText('passPackageName', active.package_name);
            safeSetText('passFooterId', `ID: #${memberIdNum}`);

            const passCardStatus = document.getElementById('passCardStatus');
            if (passCardStatus) {
                passCardStatus.textContent = isAktif ? 'MEMBER AKTIF' : 'EXPIRED';
                passCardStatus.className = `pass-chip-status ${isAktif ? 'active' : 'expired'}`;
            }

            renderMemberQr(activeMemberCode);
        }

        // Table history
        if (tbody && Array.isArray(history)) {
            tbody.innerHTML = '';

            if (history.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="6" class="muted" style="text-align: center; padding: 32px 16px;">
                            Belum ada riwayat transaksi paket langganan.
                        </td>
                    </tr>
                `;
                return;
            }

            history.forEach(item => {
                const tr = document.createElement('tr');
                const isAktif = item.status === 'Aktif';
                tr.innerHTML = `
                    <td>
                        <b>${item.package_name}</b>
                        <small>Paket Langganan</small>
                    </td>
                    <td>${item.period}</td>
                    <td>${item.duration}</td>
                    <td>${item.price}</td>
                    <td>${item.payment_method}</td>
                    <td>
                        <span class="status ${isAktif ? 'green-bg' : 'neutral-bg'}">
                            ${item.status}
                        </span>
                    </td>
                `;
                tbody.appendChild(tr);
            });
        }
    } catch (e) {
        console.error('Gagal memuat data membership:', e);
        if (tbody) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="6" class="muted" style="text-align: center; padding: 24px; color: #f87171;">
                        Gagal terhubung ke server backend.
                    </td>
                </tr>
            `;
        }
    }
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
    renderMemberQr(memberCode);

    const activePkg = document.getElementById('memActiveName')?.textContent || 'Paket Gym';
    const activeDays = document.getElementById('memActiveDays')?.textContent || '0';
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
loadMembershipData();
