// frontend/login/script.js
// ponytail: deteksi otomatis URL backend (PHP dev-server port 8000 vs Apache LAMPP subfolder).
const API_URL = window.location.port === '3000'
    ? 'http://127.0.0.1:8000/api/login'
    : '../../backend/public/index.php/api/login';

const form = document.getElementById('loginForm');
const btn = document.getElementById('btnSubmit');
const alertBox = document.getElementById('alert');

const showAlert = (message, type = 'error') => {
    alertBox.textContent = message;
    alertBox.className = `alert alert-${type}`;
};

form.addEventListener('submit', async (e) => {
    e.preventDefault();
    alertBox.className = 'alert hidden';
    btn.disabled = true;
    btn.textContent = 'Memproses...';

    const identifier = document.getElementById('email').value.trim();
    const password = document.getElementById('password').value;

    try {
        const res = await fetch(API_URL, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ identifier, email: identifier, password })
        });

        const data = await res.json();

        if (!res.ok) {
            showAlert(data.error || 'Login gagal.');
            return;
        }

        showAlert('Login berhasil! Mengalihkan...', 'success');
        localStorage.setItem('user', JSON.stringify(data.data));

        setTimeout(() => {
            if (data.data.role === 'admin') {
                window.location.href = '../admin/dashboard/';
            } else {
                window.location.href = '../user/dashboard/';
            }
        }, 1000);
    } catch {
        showAlert('Tidak dapat terhubung ke server backend.');
    } finally {
        btn.disabled = false;
        btn.textContent = 'Masuk';
    }
});

// Toggle visibilitas password
const togglePasswordBtn = document.getElementById('togglePassword');
const passwordInput = document.getElementById('password');

if (togglePasswordBtn && passwordInput) {
    const eyeIcon = togglePasswordBtn.querySelector('.icon-eye');
    const eyeOffIcon = togglePasswordBtn.querySelector('.icon-eye-off');

    togglePasswordBtn.addEventListener('click', () => {
        const isPassword = passwordInput.type === 'password';
        passwordInput.type = isPassword ? 'text' : 'password';
        togglePasswordBtn.setAttribute('aria-pressed', String(isPassword));
        togglePasswordBtn.setAttribute('aria-label', isPassword ? 'Sembunyikan password' : 'Lihat password');
        togglePasswordBtn.title = isPassword ? 'Sembunyikan password' : 'Lihat password';
        eyeIcon.classList.toggle('hidden', isPassword);
        eyeOffIcon.classList.toggle('hidden', !isPassword);
    });
}

// Tombol aksi di pojok kanan bawah foto.
const btnFullscreen = document.getElementById('btnFullscreen');
const btnRefresh = document.getElementById('btnRefresh');

btnFullscreen?.addEventListener('click', () => {
    if (!document.fullscreenElement) {
        document.documentElement.requestFullscreen?.().catch(() => {});
    } else {
        document.exitFullscreen?.();
    }
});

btnRefresh?.addEventListener('click', () => window.location.reload());