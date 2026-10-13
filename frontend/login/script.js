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

    const email = document.getElementById('email').value.trim();
    const password = document.getElementById('password').value;

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
