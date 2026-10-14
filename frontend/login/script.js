// frontend/login/script.js
// ponytail: deteksi otomatis URL backend (PHP dev-server port 8000 vs Apache LAMPP subfolder).
const API_URL = window.location.port === '3000'
    ? 'http://127.0.0.1:8000/api/login'
    : '../../backend/public/index.php/api/login';

const form = document.getElementById('loginForm');
const btn = document.getElementById('btnSubmit');
const alertBox = document.getElementById('alert');
const emailInput = document.getElementById('email');
const passwordInput = document.getElementById('password');

const showAlert = (message, type = 'error') => {
    alertBox.textContent = message;
    alertBox.className = `alert alert-${type}`;
};

// Toggle intip kata sandi
const btnToggle = document.getElementById('btnTogglePassword');
if (btnToggle && passwordInput) {
    btnToggle.addEventListener('click', () => {
        const isPass = passwordInput.type === 'password';
        passwordInput.type = isPass ? 'text' : 'password';
        btnToggle.textContent = isPass ? 'Tutup' : 'Lihat';
    });
}

// Tombol demo kredensial cepat
const fillCredentials = (email, pass) => {
    if (emailInput) emailInput.value = email;
    if (passwordInput) passwordInput.value = pass;
    alertBox.className = 'alert hidden';
    if (passwordInput) passwordInput.focus();
};

document.getElementById('btnDemoAdmin')?.addEventListener('click', () => {
    fillCredentials('admin@gym.com', 'admin123');
});

document.getElementById('btnDemoUser')?.addEventListener('click', () => {
    fillCredentials('user@gym.com', 'user123');
});

form.addEventListener('submit', async (e) => {
    e.preventDefault();
    alertBox.className = 'alert hidden';
    btn.disabled = true;
    btn.innerHTML = `<span>Memproses...</span>`;

    const email = emailInput ? emailInput.value.trim() : '';
    const password = passwordInput ? passwordInput.value : '';

    if (!email || !password) {
        showAlert('Harap isi alamat email dan kata sandi.');
        btn.disabled = false;
        btn.innerHTML = `<span>Masuk Sekarang</span><svg class="btn-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="m9 5 7 7-7 7"/></svg>`;
        return;
    }

    try {
        const res = await fetch(API_URL, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ email, password })
        });

        const data = await res.json();

        if (!res.ok) {
            showAlert(data.error || 'Login gagal.');
            return;
        }

        showAlert('Login berhasil! Mengalihkan ke dashboard...', 'success');
        localStorage.setItem('user', JSON.stringify(data.data));

        setTimeout(() => {
            if (data.data.role === 'admin') {
                window.location.href = '../admin/dashboard/';
            } else {
                window.location.href = '../user/dashboard/';
            }
        }, 800);
    } catch {
        showAlert('Tidak dapat terhubung ke server backend.');
    } finally {
        btn.disabled = false;
        btn.innerHTML = `<span>Masuk Sekarang</span><svg class="btn-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="m9 5 7 7-7 7"/></svg>`;
    }
});
