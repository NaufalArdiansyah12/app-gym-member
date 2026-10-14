// ponytail: BarcodeDetector native dipakai tanpa library tambahan; input manual tetap tersedia sebagai fallback.
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
const API_URL = window.location.port === '3000'
    ? 'http://127.0.0.1:8000/api/admin/attendance'
    : '../../../backend/public/index.php/api/admin/attendance';
let cameraStream;
let scanFrame;
let submitting = false;

function showResult(message, error = false) {
    result.hidden = false;
    result.className = error ? 'checkin-result error' : 'checkin-result';
    result.textContent = message;
}

async function checkIn(code) {
    if (submitting) return;
    submitting = true;
    try {
        const response = await fetch(`${API_URL}/checkin`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ member_code: code.trim().toUpperCase() }),
        });
        const payload = await response.json();
        if (!response.ok) throw new Error(payload.error || 'Check-in gagal');
        showResult(`${payload.data.name} berhasil check-in (${payload.data.member_code}).`);
        count.textContent = Number(count.textContent) + 1;
        form.reset();
        if (cameraStream) await stopScanner();
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
        if (!response.ok) return;
        count.textContent = payload.data.total;
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
    } catch { /* Backend may be offline while page shell remains usable. */ }
}

form?.addEventListener('submit', (event) => {
    event.preventDefault();
    checkIn(codeInput.value);
});

async function stopScanner() {
    if (scanFrame) cancelAnimationFrame(scanFrame);
    cameraStream?.getTracks().forEach((track) => track.stop());
    cameraStream = null;
    video.hidden = true;
    toggleScanner.textContent = 'Aktifkan kamera';
    scannerNote.textContent = 'Kamera scanner belum diaktifkan';
}

async function startScanner() {
    if (!('BarcodeDetector' in window) && typeof window.jsQR !== 'function') {
        showResult('Decoder QR tidak tersedia. Periksa koneksi internet atau masukkan kode member manual.', true);
        return;
    }
    try {
        cameraStream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' }, audio: false });
        video.srcObject = cameraStream;
        video.hidden = false;
        scannerNote.textContent = 'Arahkan QR member ke kamera';
        toggleScanner.textContent = 'Matikan kamera';
        const detector = 'BarcodeDetector' in window ? new BarcodeDetector({ formats: ['qr_code'] }) : null;
        const canvas = document.createElement('canvas');
        const context = canvas.getContext('2d', { willReadFrequently: true });
        const scan = async () => {
            if (!cameraStream) return;
            try {
                let code;
                if (detector) {
                    code = (await detector.detect(video))[0]?.rawValue;
                } else if (video.readyState >= HTMLMediaElement.HAVE_CURRENT_DATA) {
                    canvas.width = video.videoWidth;
                    canvas.height = video.videoHeight;
                    context.drawImage(video, 0, 0, canvas.width, canvas.height);
                    code = window.jsQR(context.getImageData(0, 0, canvas.width, canvas.height).data, canvas.width, canvas.height)?.data;
                }
                if (code) await checkIn(code);
            } catch { /* Frame belum siap atau QR belum terbaca. */ }
            scanFrame = requestAnimationFrame(scan);
        };
        scan();
    } catch {
        showResult('Kamera tidak dapat diakses. Izinkan kamera atau gunakan input manual.', true);
    }
}

toggleScanner?.addEventListener('click', () => cameraStream ? stopScanner() : startScanner());
loadToday();

document.querySelectorAll('.demo-action').forEach((button) => button.addEventListener('click', () => {
    const toast = document.getElementById('toast');
    if (!toast) return;
    toast.textContent = 'Tampilan demo — data belum terhubung ke backend.';
    toast.classList.add('show');
    window.setTimeout(() => toast.classList.remove('show'), 2600);
}));
