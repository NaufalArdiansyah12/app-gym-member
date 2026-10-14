// ponytail: BarcodeDetector native dipakai jika didukung, dengan fallback jsQR lokal. Input manual tetap tersedia.
const rawUser = localStorage.getItem('user');

if (!rawUser) {
    window.location.replace('../../login/');
} else {
    try {
        const user = JSON.parse(rawUser);
        if (!user || user.role !== 'admin') window.location.replace('../../user/dashboard/');
        const firstName = String(user.name || 'Admin').trim().split(/\s+/)[0];
        document.getElementById('userName')?.replaceChildren(document.createTextNode(firstName));
        document.getElementById('userProfileName')?.replaceChildren(document.createTextNode(user.name || 'Admin Gym'));
        const avatar = document.getElementById('userAvatar');
        if (avatar) avatar.textContent = firstName.charAt(0).toUpperCase();
        document.getElementById('btnLogout')?.addEventListener('click', () => { localStorage.removeItem('user'); window.location.replace('../../login/'); });
    } catch { localStorage.removeItem('user'); window.location.replace('../../login/'); }
}

const form = document.getElementById('attendanceForm');
const codeInput = document.getElementById('memberCode');
const result = document.getElementById('checkinResult');
const list = document.getElementById('attendanceList');
const count = document.getElementById('todayCheckins');
const video = document.getElementById('scannerVideo');
const scannerBox = document.getElementById('scannerBox');
const scannerNote = document.getElementById('scannerNote');
const toggleScanner = document.getElementById('toggleScanner');
const modalSuccess = document.getElementById('modalAttendanceSuccess');
const modalName = document.getElementById('modalAttName');
const modalCode = document.getElementById('modalAttCode');
const modalAvatar = document.getElementById('modalAttAvatar');
const modalTime = document.getElementById('modalAttTime');
const modalStatus = document.getElementById('modalAttStatus');
const modalExpires = document.getElementById('modalAttExpires');
const modalExpiresRow = document.getElementById('modalAttExpiresRow');
const btnCloseAttModal = document.getElementById('btnCloseAttModal');
const autoCloseSpan = document.getElementById('autoCloseSeconds');

const modalWarning = document.getElementById('modalAttendanceWarning');
const modalWarnName = document.getElementById('modalAttWarnName');
const modalWarnCode = document.getElementById('modalAttWarnCode');
const modalWarnAvatar = document.getElementById('modalAttWarnAvatar');
const modalWarnTime = document.getElementById('modalAttWarnTime');
const modalWarnExpires = document.getElementById('modalAttWarnExpires');
const modalWarnExpiresRow = document.getElementById('modalAttWarnExpiresRow');
const btnCloseAttWarnModal = document.getElementById('btnCloseAttWarnModal');
const autoCloseWarnSpan = document.getElementById('autoCloseWarnSeconds');

const API_URL = window.location.port === '3000'
    ? 'http://127.0.0.1:8000/api/admin/attendance'
    : '../../../backend/public/index.php/api/admin/attendance';

let cameraStream = null;
let scanFrame = null;
let submitting = false;
let lastScannedCode = '';
let lastScannedTime = 0;
let autoCloseTimer = null;
let autoCloseInterval = null;
let autoCloseWarnTimer = null;
let autoCloseWarnInterval = null;

function showResult(message, error = false) {
    result.hidden = false;
    result.className = error ? 'checkin-result error' : 'checkin-result';
    result.textContent = message;
}

function openSuccessModal(data) {
    if (!modalSuccess) return;

    if (modalName) modalName.textContent = data.name || 'Member';
    if (modalCode) modalCode.textContent = data.member_code || '-';
    if (modalAvatar) {
        const initials = (data.name || 'M').split(/\s+/).map(p => p[0]).join('').slice(0, 2).toUpperCase();
        modalAvatar.textContent = initials;
    }

    if (modalTime) {
        const dt = data.waktu_presensi ? new Date(data.waktu_presensi.replace(' ', 'T')) : new Date();
        modalTime.textContent = dt.toLocaleDateString('id-ID', {
            day: 'numeric',
            month: 'short',
            year: 'numeric'
        }) + ', ' + dt.toLocaleTimeString('id-ID', {
            hour: '2-digit',
            minute: '2-digit'
        }) + ' WIB';
    }

    if (modalStatus) {
        modalStatus.textContent = data.membership_status === 'active' ? 'Aktif' : (data.membership_status || 'Aktif');
        modalStatus.className = 'status success-bg';
    }

    if (data.membership_expires_at && modalExpires) {
        const expDate = new Date(data.membership_expires_at + 'T00:00:00');
        modalExpires.textContent = expDate.toLocaleDateString('id-ID', {
            day: 'numeric',
            month: 'short',
            year: 'numeric'
        });
        if (modalExpiresRow) modalExpiresRow.hidden = false;
    } else if (modalExpiresRow) {
        modalExpiresRow.hidden = true;
    }

    modalSuccess.classList.add('is-active');
    modalSuccess.setAttribute('aria-hidden', 'false');

    let secondsLeft = 4;
    if (autoCloseSpan) autoCloseSpan.textContent = secondsLeft;

    clearInterval(autoCloseInterval);
    clearTimeout(autoCloseTimer);

    autoCloseInterval = setInterval(() => {
        secondsLeft -= 1;
        if (autoCloseSpan) autoCloseSpan.textContent = Math.max(0, secondsLeft);
        if (secondsLeft <= 0) {
            clearInterval(autoCloseInterval);
        }
    }, 1000);

    autoCloseTimer = setTimeout(() => {
        closeSuccessModal();
    }, 4000);
}

function closeSuccessModal() {
    clearInterval(autoCloseInterval);
    clearTimeout(autoCloseTimer);
    if (!modalSuccess) return;
    modalSuccess.classList.remove('is-active');
    modalSuccess.setAttribute('aria-hidden', 'true');
}

function openWarningModal(data) {
    if (!modalWarning) return;

    if (modalWarnName) modalWarnName.textContent = data.name || 'Member';
    if (modalWarnCode) modalWarnCode.textContent = data.member_code || '-';
    if (modalWarnAvatar) {
        const initials = (data.name || 'M').split(/\s+/).map(p => p[0]).join('').slice(0, 2).toUpperCase();
        modalWarnAvatar.textContent = initials;
    }

    if (modalWarnTime) {
        const dt = data.waktu_presensi ? new Date(data.waktu_presensi.replace(' ', 'T')) : new Date();
        modalWarnTime.textContent = dt.toLocaleTimeString('id-ID', {
            hour: '2-digit',
            minute: '2-digit'
        }) + ' WIB';
    }

    if (data.membership_expires_at && modalWarnExpires) {
        const expDate = new Date(data.membership_expires_at + 'T00:00:00');
        modalWarnExpires.textContent = expDate.toLocaleDateString('id-ID', {
            day: 'numeric',
            month: 'short',
            year: 'numeric'
        });
        if (modalWarnExpiresRow) modalWarnExpiresRow.hidden = false;
    } else if (modalWarnExpiresRow) {
        modalWarnExpiresRow.hidden = true;
    }

    modalWarning.classList.add('is-active');
    modalWarning.setAttribute('aria-hidden', 'false');

    let secondsLeft = 4;
    if (autoCloseWarnSpan) autoCloseWarnSpan.textContent = secondsLeft;

    clearInterval(autoCloseWarnInterval);
    clearTimeout(autoCloseWarnTimer);

    autoCloseWarnInterval = setInterval(() => {
        secondsLeft -= 1;
        if (autoCloseWarnSpan) autoCloseWarnSpan.textContent = Math.max(0, secondsLeft);
        if (secondsLeft <= 0) {
            clearInterval(autoCloseWarnInterval);
        }
    }, 1000);

    autoCloseWarnTimer = setTimeout(() => {
        closeWarningModal();
    }, 4000);
}

function closeWarningModal() {
    clearInterval(autoCloseWarnInterval);
    clearTimeout(autoCloseWarnTimer);
    if (!modalWarning) return;
    modalWarning.classList.remove('is-active');
    modalWarning.setAttribute('aria-hidden', 'true');
}

btnCloseAttModal?.addEventListener('click', closeSuccessModal);
btnCloseAttWarnModal?.addEventListener('click', closeWarningModal);

modalSuccess?.addEventListener('click', (e) => {
    if (e.target === modalSuccess) closeSuccessModal();
});

modalWarning?.addEventListener('click', (e) => {
    if (e.target === modalWarning) closeWarningModal();
});

document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        if (modalSuccess?.classList.contains('is-active')) closeSuccessModal();
        if (modalWarning?.classList.contains('is-active')) closeWarningModal();
    }
});

function extractMemberCode(raw) {
    if (!raw) return '';
    const str = String(raw).trim();
    const match = str.match(/GYM[-A-Z0-9]+/i);
    return match ? match[0].toUpperCase() : str.toUpperCase();
}

function beepSuccess() {
    try {
        const AudioCtx = window.AudioContext || window.webkitAudioContext;
        if (!AudioCtx) return;
        const ctx = new AudioCtx();
        const osc = ctx.createOscillator();
        const gain = ctx.createGain();
        osc.connect(gain);
        gain.connect(ctx.destination);
        osc.type = 'sine';
        osc.frequency.setValueAtTime(880, ctx.currentTime);
        gain.gain.setValueAtTime(0.15, ctx.currentTime);
        gain.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.15);
        osc.start(ctx.currentTime);
        osc.stop(ctx.currentTime + 0.15);
    } catch { /* Audio diabaikan jika dibatasi browser */ }
}

function beepWarning() {
    try {
        const AudioCtx = window.AudioContext || window.webkitAudioContext;
        if (!AudioCtx) return;
        const ctx = new AudioCtx();
        const osc = ctx.createOscillator();
        const gain = ctx.createGain();
        osc.connect(gain);
        gain.connect(ctx.destination);
        osc.type = 'triangle';
        osc.frequency.setValueAtTime(440, ctx.currentTime);
        osc.frequency.setValueAtTime(330, ctx.currentTime + 0.12);
        gain.gain.setValueAtTime(0.2, ctx.currentTime);
        gain.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.3);
        osc.start(ctx.currentTime);
        osc.stop(ctx.currentTime + 0.3);
    } catch { /* Audio diabaikan jika dibatasi browser */ }
}

function triggerScanFeedback() {
    if (scannerBox) {
        scannerBox.classList.add('scan-success');
        setTimeout(() => scannerBox.classList.remove('scan-success'), 600);
    }
    beepSuccess();
}

function triggerWarningFeedback() {
    if (scannerBox) {
        scannerBox.classList.add('scan-warning');
        setTimeout(() => scannerBox.classList.remove('scan-warning'), 600);
    }
    beepWarning();
}

async function checkIn(code) {
    const cleanCode = extractMemberCode(code);
    if (!cleanCode) {
        showResult('Kode member kosong atau tidak valid', true);
        return;
    }
    if (submitting) return;
    submitting = true;
    try {
        const response = await fetch(`${API_URL}/checkin`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ member_code: cleanCode }),
        });
        const payload = await response.json();

        if (response.status === 409 || payload.status === 'already_checked_in') {
            triggerWarningFeedback();
            openWarningModal(payload.data || { member_code: cleanCode, name: 'Member' });
            showResult(`⚠ ${payload.data?.name || cleanCode} sudah presensi hari ini.`, true);
            form?.reset();
            return;
        }

        if (!response.ok) throw new Error(payload.error || 'Check-in gagal');
        
        triggerScanFeedback();
        openSuccessModal(payload.data);
        showResult(`✓ ${payload.data.name} berhasil check-in (${payload.data.member_code}).`);
        count.textContent = Number(count.textContent) + 1;
        form?.reset();
        loadToday();
    } catch (error) {
        showResult(error.message, true);
    } finally {
        submitting = false;
    }
}

async function loadToday() {
    try {
        const response = await fetch(`${API_URL}/today`);
        const payload = await response.json();
        if (!response.ok || !payload.data) return;
        count.textContent = payload.data.total ?? 0;
        if (!list) return;
        
        if (!payload.data.items || payload.data.items.length === 0) {
            list.innerHTML = '<p class="muted" style="padding: 1rem; text-align: center;">Belum ada presensi tercatat hari ini.</p>';
            return;
        }

        list.replaceChildren(...payload.data.items.map((item) => {
            const row = document.createElement('div');
            row.className = 'attendance-row';
            const initials = item.name.split(/\s+/).map((part) => part[0]).join('').slice(0, 2).toUpperCase();
            const time = new Date(item.waktu_presensi.replace(' ', 'T')).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
            const avatar = document.createElement('div');
            avatar.className = 'avatar orange';
            avatar.textContent = initials;
            const details = document.createElement('div');
            const name = document.createElement('b');
            name.textContent = item.name;
            const code = document.createElement('small');
            code.textContent = item.member_code;
            details.append(name, code);
            const timestamp = document.createElement('time');
            timestamp.textContent = time;
            row.append(avatar, details, timestamp);
            return row;
        }));
    } catch { /* Backend offline */ }
}

form?.addEventListener('submit', (event) => {
    event.preventDefault();
    checkIn(codeInput?.value || '');
});

async function stopScanner() {
    if (scanFrame) {
        cancelAnimationFrame(scanFrame);
        scanFrame = null;
    }
    if (cameraStream) {
        cameraStream.getTracks().forEach((track) => track.stop());
        cameraStream = null;
    }
    if (video) {
        video.srcObject = null;
        video.hidden = true;
    }
    scannerBox?.classList.remove('scanning', 'scan-success');
    if (toggleScanner) toggleScanner.textContent = 'Aktifkan kamera';
    if (scannerNote) scannerNote.textContent = 'Kamera scanner belum diaktifkan';
}

async function startScanner() {
    if (!('BarcodeDetector' in window) && typeof window.jsQR !== 'function') {
        showResult('Decoder QR belum siap. Muat ulang halaman atau gunakan input manual.', true);
        return;
    }

    try {
        let stream;
        try {
            stream = await navigator.mediaDevices.getUserMedia({
                video: { facingMode: { ideal: 'environment' }, width: { ideal: 1280 }, height: { ideal: 720 } },
                audio: false
            });
        } catch {
            stream = await navigator.mediaDevices.getUserMedia({ video: true, audio: false });
        }

        cameraStream = stream;
        video.srcObject = cameraStream;
        video.hidden = false;
        scannerBox?.classList.add('scanning');
        if (toggleScanner) toggleScanner.textContent = 'Matikan kamera';
        if (scannerNote) scannerNote.textContent = 'Arahkan QR member ke dalam kotak scanner';

        try {
            await video.play();
        } catch { /* Autoplay */ }

        let detector = null;
        if ('BarcodeDetector' in window) {
            try {
                detector = new BarcodeDetector({ formats: ['qr_code'] });
            } catch {
                detector = null;
            }
        }

        const procCanvas = document.createElement('canvas');
        const procCtx = procCanvas.getContext('2d', { willReadFrequently: true });
        let lastScanProcess = 0;

        const scanLoop = async (now) => {
            if (!cameraStream) return;

            // Lewati proses jika modal konfirmasi / peringatan presensi masih terbuka
            if (modalSuccess?.classList.contains('is-active') || modalWarning?.classList.contains('is-active')) {
                scanFrame = requestAnimationFrame(scanLoop);
                return;
            }

            // Throttle proses tiap 90ms (~11 FPS) agar hemat daya dan responsif
            if (now - lastScanProcess > 90 && video.readyState >= HTMLMediaElement.HAVE_CURRENT_DATA && video.videoWidth > 0) {
                lastScanProcess = now;
                try {
                    let rawCode = null;

                    // 1. Coba BarcodeDetector native
                    if (detector) {
                        try {
                            const barcodes = await detector.detect(video);
                            if (barcodes && barcodes.length > 0) {
                                rawCode = barcodes[0].rawValue;
                            }
                        } catch { /* Fallback ke jsQR */ }
                    }

                    // 2. Fallback ke jsQR
                    if (!rawCode && typeof window.jsQR === 'function') {
                        const maxDim = 640;
                        const scale = Math.min(1, maxDim / video.videoWidth);
                        procCanvas.width = Math.round(video.videoWidth * scale);
                        procCanvas.height = Math.round(video.videoHeight * scale);

                        procCtx.drawImage(video, 0, 0, procCanvas.width, procCanvas.height);
                        const imgData = procCtx.getImageData(0, 0, procCanvas.width, procCanvas.height);
                        const result = window.jsQR(imgData.data, procCanvas.width, procCanvas.height, {
                            inversionAttempts: 'dontInvert',
                        });
                        if (result && result.data) {
                            rawCode = result.data;
                        }
                    }

                    // 3. Proses check-in jika kode QR terbaca
                    if (rawCode) {
                        const memberCode = extractMemberCode(rawCode);
                        const timeNow = Date.now();
                        // Cooldown 3 detik untuk kode yang sama agar tidak spam ganda
                        if (memberCode && !submitting && (memberCode !== lastScannedCode || timeNow - lastScannedTime > 3000)) {
                            lastScannedCode = memberCode;
                            lastScannedTime = timeNow;
                            await checkIn(memberCode);
                        }
                    }
                } catch {
                    /* Frame dilewati jika belum siap */
                }
            }

            if (cameraStream) {
                scanFrame = requestAnimationFrame(scanLoop);
            }
        };

        scanFrame = requestAnimationFrame(scanLoop);
    } catch (err) {
        showResult(`Kamera tidak dapat diakses (${err.message || 'Izin ditolak'}). Gunakan input manual.`, true);
        await stopScanner();
    }
}

toggleScanner?.addEventListener('click', () => {
    if (cameraStream) {
        stopScanner();
    } else {
        startScanner();
    }
});

loadToday();
