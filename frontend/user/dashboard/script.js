// frontend/user/dashboard/script.js
// ponytail: client-side auth guard via localStorage. Ganti session cookie / token backend saat butuh SSR ketat.
const raw = localStorage.getItem('user');
if (!raw) {
    window.location.replace('../../login/');
}

const user = JSON.parse(raw || '{}');
if (user.role !== 'user') {
    window.location.replace('../../admin/dashboard/');
}

document.getElementById('userName').textContent = user.name || '-';
document.getElementById('userEmail').textContent = user.email || '-';
document.getElementById('userRole').textContent = user.role || 'user';

document.getElementById('btnLogout').addEventListener('click', () => {
    localStorage.removeItem('user');
    window.location.replace('../../login/');
});
