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
        const packageSearch = document.getElementById('packageSearch');
        const packageStatus = document.getElementById('packageStatus');
        const packageCount = document.getElementById('packageCount');
        const cards = Array.from(document.querySelectorAll('.package-card-item'));
        const durationCards = Array.from(document.querySelectorAll('.glass-package-card'));
        const editButtons = Array.from(document.querySelectorAll('.package-edit-button'));

        cards.forEach((card) => {
            card.addEventListener('click', () => {
                const alreadySelected = card.classList.contains('is-selected');
                cards.forEach((item) => item.classList.toggle('is-selected', item === card && !alreadySelected));

                if (!alreadySelected) {
                    const title = card.querySelector('h3')?.textContent?.trim() || 'Paket';
                    showToast(`${title} dipilih`);
                }
            });
        });

        editButtons.forEach((button) => {
            button.addEventListener('click', (event) => {
                event.stopPropagation();
                const card = button.closest('.package-card-item');
                const title = card?.querySelector('h3')?.textContent?.trim() || 'Paket';
                showToast(`Edit paket: ${title}`);
            });
        });
        const modalAddPackage = document.getElementById('modalAddPackage');
        const btnOpenAddPackage = document.getElementById('btnOpenAddPackage');
        const btnClosePackageModal = document.getElementById('btnClosePackageModal');
        const btnCancelPackageModal = document.getElementById('btnCancelPackageModal');
        const formAddPackage = document.getElementById('formAddPackage');
        const toast = document.getElementById('toast');
        let toastTimer;
        let activeDuration = 'all';

        function showToast(message) {
            if (!toast) return;
            toast.textContent = message;
            toast.classList.add('show');
            window.clearTimeout(toastTimer);
            toastTimer = window.setTimeout(() => toast.classList.remove('show'), 2600);
        }

        function updatePackageCount() {
            const visibleCards = cards.filter((card) => !card.hidden);
            if (packageCount) {
                packageCount.textContent = visibleCards.length
                    ? `Menampilkan ${visibleCards.length} paket`
                    : 'Tidak ada paket yang cocok';
            }
        }

        function applyPackageFilters() {
            const query = (packageSearch?.value || '').trim().toLowerCase();
            const status = packageStatus?.value || 'all';

            cards.forEach((card) => {
                const text = (card.textContent || '').toLowerCase();
                const cardStatus = card.dataset.status || 'active';
                const cardDuration = (card.dataset.duration || '').toLowerCase();
                const matchesQuery = !query || text.includes(query) || cardDuration.includes(query);
                const matchesStatus = status === 'all' || cardStatus === status;
                const matchesDuration = activeDuration === 'all' || cardDuration === activeDuration;
                card.hidden = !(matchesQuery && matchesStatus && matchesDuration);
            });

            updatePackageCount();
        }

        durationCards.forEach((card) => {
            card.addEventListener('click', () => {
                activeDuration = card.dataset.duration || 'all';
                durationCards.forEach((item) => item.classList.toggle('active', item === card));
                applyPackageFilters();
            });
        });

        packageSearch?.addEventListener('input', applyPackageFilters);
        packageStatus?.addEventListener('change', applyPackageFilters);

        function openPackageModal() {
            if (!modalAddPackage) return;
            modalAddPackage.classList.add('is-active');
            modalAddPackage.setAttribute('aria-hidden', 'false');
            document.body.style.overflow = 'hidden';
            const firstInput = document.getElementById('packageName');
            window.setTimeout(() => firstInput?.focus(), 80);
        }

        function closePackageModal() {
            if (!modalAddPackage) return;
            modalAddPackage.classList.remove('is-active');
            modalAddPackage.setAttribute('aria-hidden', 'true');
            document.body.style.overflow = '';
            btnOpenAddPackage?.focus();
        }

        btnOpenAddPackage?.addEventListener('click', (event) => {
            event.preventDefault();
            openPackageModal();
        });

        btnClosePackageModal?.addEventListener('click', closePackageModal);
        btnCancelPackageModal?.addEventListener('click', closePackageModal);

        modalAddPackage?.addEventListener('click', (event) => {
            if (event.target === modalAddPackage) {
                closePackageModal();
            }
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && modalAddPackage?.classList.contains('is-active')) {
                closePackageModal();
            }
        });

        formAddPackage?.addEventListener('submit', (event) => {
            event.preventDefault();
            const formData = new FormData(formAddPackage);
            const name = (formData.get('nama_paket') || 'Paket baru').toString().trim();
            closePackageModal();
            formAddPackage.reset();
            showToast(`Paket “${name}” berhasil ditambahkan (demo)`);
        });

        updatePackageCount();
    }
}
