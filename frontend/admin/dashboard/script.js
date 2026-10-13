// ponytail: Dashboard values stay static until read-only report APIs exist.
const rawUser = localStorage.getItem('user');

if (!rawUser) {
    window.location.replace('../../login/');
} else {
    let user;

    try {
        user = JSON.parse(rawUser);
    } catch {
        localStorage.removeItem('user');
        window.location.replace('../../login/');
    }

    if (!user || user.role !== 'admin') {
        window.location.replace('../../user/dashboard/');
    } else {
        const firstName = String(user.name || 'Admin').trim().split(/\s+/)[0];
        const userName = document.getElementById('userName');
        const profileName = document.getElementById('userProfileName');
        const avatar = document.getElementById('userAvatar');

        if (userName) userName.textContent = firstName;
        if (profileName) profileName.textContent = user.name || 'Admin Gym';
        if (avatar) avatar.textContent = firstName.charAt(0).toUpperCase();

        document.getElementById('btnLogout')?.addEventListener('click', () => {
            localStorage.removeItem('user');
            window.location.replace('../../login/');
        });

        // Modal Tambah Member
        const modalAddMember = document.getElementById('modalAddMember');
        const btnOpenAddMember = document.getElementById('btnOpenAddMember');
        const btnCloseModal = document.getElementById('btnCloseModal');
        const btnCancelModal = document.getElementById('btnCancelModal');
        const formAddMember = document.getElementById('formAddMember');
        const memberNameInput = document.getElementById('memberName');
        const toast = document.getElementById('toast');
        let toastTimer;

        function showToast(message) {
            if (!toast) return;
            toast.textContent = message;
            toast.classList.add('show');
            window.clearTimeout(toastTimer);
            toastTimer = window.setTimeout(() => toast.classList.remove('show'), 2600);
        }

        function openModal() {
            if (!modalAddMember) return;
            modalAddMember.classList.add('is-active');
            modalAddMember.setAttribute('aria-hidden', 'false');
            document.body.style.overflow = 'hidden';
            window.setTimeout(() => memberNameInput?.focus(), 80);
        }

        function closeModal() {
            if (!modalAddMember) return;
            modalAddMember.classList.remove('is-active');
            modalAddMember.setAttribute('aria-hidden', 'true');
            document.body.style.overflow = '';
            btnOpenAddMember?.focus();
        }

        btnOpenAddMember?.addEventListener('click', (e) => {
            e.preventDefault();
            openModal();
        });

        btnCloseModal?.addEventListener('click', closeModal);
        btnCancelModal?.addEventListener('click', closeModal);

        modalAddMember?.addEventListener('click', (e) => {
            if (e.target === modalAddMember) {
                closeModal();
            }
        });

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && modalAddMember?.classList.contains('is-active')) {
                closeModal();
            }
        });

        formAddMember?.addEventListener('submit', (e) => {
            e.preventDefault();
            const formData = new FormData(formAddMember);
            const nama = (formData.get('nama_lengkap') || 'Member baru').toString().trim();
            closeModal();
            formAddMember.reset();
            showToast(`Berhasil mendaftarkan ${nama}! (demo)`);
        });
    }
}
