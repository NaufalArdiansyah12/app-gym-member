// frontend/admin/packages/script.js
const rawUser = localStorage.getItem('user');

// ponytail: Deteksi otomatis URL API backend dev-server port 3000 vs Apache LAMPP.
const getApiUrl = (endpoint) => {
    return window.location.port === '3000'
        ? `http://127.0.0.1:8000/api/${endpoint}`
        : `../../../backend/public/index.php/api/${endpoint}`;
};

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
        initAdminPackages(user);
    }
}

function initAdminPackages(user) {
    // Profil admin di topbar
    const userProfileName = document.getElementById('userProfileName');
    const userAvatar = document.getElementById('userAvatar');
    if (userProfileName && user.name) userProfileName.textContent = user.name;
    if (userAvatar && user.name) userAvatar.textContent = user.name.charAt(0).toUpperCase();

    // Logout
    document.getElementById('btnLogout')?.addEventListener('click', () => {
        localStorage.removeItem('user');
        window.location.replace('../../login/');
    });

    // DOM Elements
    const packageGrid = document.getElementById('packageGrid');
    const modalEditPackage = document.getElementById('modalEditPackage');
    const btnCloseModal = document.getElementById('btnCloseModal');
    const btnCancelModal = document.getElementById('btnCancelModal');
    const formEditPackage = document.getElementById('formEditPackage');
    const btnSubmitPackage = document.getElementById('btnSubmitPackage');
    const toast = document.getElementById('toast');

    // Inputs
    const editPackageId = document.getElementById('editPackageId');
    const editPackageName = document.getElementById('editPackageName');
    const editPackagePrice = document.getElementById('editPackagePrice');
    const editPackageDuration = document.getElementById('editPackageDuration');
    const editPackageBenefits = document.getElementById('editPackageBenefits');
    const editPackageStatus = document.getElementById('editPackageStatus');

    let toastTimer;
    let packagesData = [];

    function showToast(message, isError = false) {
        if (!toast) return;
        toast.textContent = message;
        toast.classList.toggle('error', isError);
        toast.classList.add('show');
        window.clearTimeout(toastTimer);
        toastTimer = window.setTimeout(() => {
            toast.classList.remove('show');
            toast.classList.remove('error');
        }, 3000);
    }

    function escapeHtml(str) {
        if (str === null || str === undefined) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function formatRupiah(num) {
        return Math.round(Number(num) || 0).toLocaleString('id-ID');
    }

    // Modal Handlers
    function openModal() {
        if (!modalEditPackage) return;
        modalEditPackage.classList.add('is-active');
        modalEditPackage.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
        editPackageName?.focus();
    }

    function closeModal() {
        if (!modalEditPackage) return;
        modalEditPackage.classList.remove('is-active');
        modalEditPackage.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
        formEditPackage?.reset();
    }

    btnCloseModal?.addEventListener('click', closeModal);
    btnCancelModal?.addEventListener('click', closeModal);

    modalEditPackage?.addEventListener('click', (e) => {
        if (e.target === modalEditPackage) closeModal();
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && modalEditPackage?.classList.contains('is-active')) {
            closeModal();
        }
    });

    // Populate and open edit form
    function editPackage(pkgId) {
        const pkg = packagesData.find((p) => p.id === Number(pkgId));
        if (!pkg) {
            showToast('Data paket tidak ditemukan', true);
            return;
        }

        editPackageId.value = pkg.id;
        editPackageName.value = pkg.nama_paket || '';
        editPackagePrice.value = Math.round(pkg.harga || 0);
        editPackageDuration.value = pkg.durasi_hari || 30;
        editPackageBenefits.value = pkg.benefit || '';
        editPackageStatus.value = pkg.tersedia ? '1' : '0';

        openModal();
    }

    // Render Cards
    function renderPackages(list) {
        if (!packageGrid) return;

        if (!list || list.length === 0) {
            packageGrid.innerHTML = `
                <div class="package-empty-state">
                    <svg class="icon" style="width: 48px; height: 48px; opacity: 0.5;" aria-hidden="true"><use href="#icon-package"/></svg>
                    <p>Tidak ada paket langganan yang ditemukan.</p>
                </div>
            `;
            return;
        }

        packageGrid.innerHTML = list.map((pkg) => {
            const durasi = Number(pkg.durasi_hari);
            const months = Math.max(1, Math.round(durasi / 30));
            const perMonth = Math.round(pkg.harga / months);
            const isPopular = Boolean(pkg.is_popular);

            let tierClass = 'tier-starter';
            let tierBadge = `STARTER · ${months} BULAN`;
            let tierSubtitle = 'Pilihan fleksibel bagi pemula untuk adaptasi latihan harian.';
            let equivalentText = `Rp ${formatRupiah(pkg.harga)} / bulan`;

            if (isPopular) {
                tierClass = 'tier-popular';
                tierBadge = `POPULER · ${months} BULAN`;
                tierSubtitle = 'Paket terlaris dengan total peminat terbanyak saat ini.';
                equivalentText = months > 1 ? `Setara Rp ${formatRupiah(perMonth)} / bulan` : equivalentText;
            } else if (durasi >= 180 || pkg.nama_paket.toLowerCase().includes('6 bulan')) {
                tierClass = 'tier-vip';
                tierBadge = `VIP PRO · ${months} BULAN`;
                tierSubtitle = 'Pilihan hemat bernilai tinggi untuk komitmen jangka panjang.';
                equivalentText = `Setara Rp ${formatRupiah(perMonth)} / bulan (Hemat)`;
            } else if (months > 1) {
                equivalentText = `Setara Rp ${formatRupiah(perMonth)} / bulan`;
            }

            // Benefits list parsing
            let benefitItems = [];
            if (Array.isArray(pkg.benefit_list) && pkg.benefit_list.length > 0) {
                benefitItems = pkg.benefit_list;
            } else if (pkg.benefit) {
                benefitItems = pkg.benefit.split('\n').map((b) => b.trim()).filter(Boolean);
            }

            if (benefitItems.length === 0) {
                benefitItems = ['Akses seluruh peralatan gym standar', 'Free akses ruang ganti & shower'];
            }

            const benefitsHtml = benefitItems.map((item) => `
                <li class="benefit-item">
                    <svg class="benefit-check-icon" aria-hidden="true" focusable="false" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="20 6 9 17 4 12"></polyline>
                    </svg>
                    <span>${escapeHtml(item)}</span>
                </li>
            `).join('');

            return `
                <article class="pro-card ${tierClass}">
                    ${isPopular ? `
                        <div class="popular-ribbon">
                            <svg class="icon" aria-hidden="true" focusable="false"><use href="#icon-sparkle"/></svg>
                            Paling Populer
                        </div>
                    ` : ''}

                    <div class="pro-card-meta">
                        <span class="tier-pill">${tierBadge}</span>
                        <span class="status-chip ${pkg.tersedia ? 'active' : 'inactive'}">
                            ${pkg.tersedia ? 'Aktif' : 'Nonaktif'}
                        </span>
                    </div>

                    <h3 class="pro-card-title">${escapeHtml(pkg.nama_paket)}</h3>
                    <p class="pro-card-subtitle">${escapeHtml(tierSubtitle)}</p>

                    <div class="pro-card-pricing">
                        <div class="price-main">
                            <span class="price-currency">Rp</span>
                            <span class="price-amount">${formatRupiah(pkg.harga)}</span>
                            <span class="price-period">/ paket</span>
                        </div>
                        <div class="price-equivalent">${escapeHtml(equivalentText)}</div>
                        <div class="duration-chip">
                            <svg class="icon" aria-hidden="true" focusable="false"><use href="#icon-package"/></svg>
                            <span>Durasi Keanggotaan: <strong>${durasi} Hari</strong></span>
                        </div>
                    </div>

                    <div class="pro-card-benefits">
                        <h4 class="benefits-label">Benefit & Fasilitas Member:</h4>
                        <ul class="benefit-list">
                            ${benefitsHtml}
                        </ul>
                    </div>

                    <div class="pro-card-footer">
                        <div class="card-footer-meta">
                            <span class="member-badge">
                                <span class="member-badge-count">${Number(pkg.total_active_members || 0)}</span> Member Aktif
                            </span>
                            <span>ID Paket: #${pkg.id}</span>
                        </div>
                        <button class="btn-pro-edit" type="button" data-id="${pkg.id}">
                            ✎ Edit Paket
                        </button>
                    </div>
                </article>
            `;
        }).join('');

        // Attach edit button listeners
        packageGrid.querySelectorAll('.btn-pro-edit').forEach((btn) => {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                const id = btn.getAttribute('data-id');
                editPackage(id);
            });
        });
    }

    // Load data from Backend
    async function loadPackages() {
        if (!packageGrid) return;
        packageGrid.innerHTML = `
            <div class="package-loading-state">
                <div class="loading-spinner"></div>
                <p>Memuat data paket langganan...</p>
            </div>
        `;

        try {
            const res = await fetch(getApiUrl('admin/packages'), {
                method: 'GET',
                credentials: 'same-origin',
                headers: {
                    'Accept': 'application/json',
                },
            });

            const result = await res.json();

            if (!res.ok || result.status !== 'success') {
                throw new Error(result.error || 'Gagal memuat paket dari server');
            }

            packagesData = result.data || [];
            renderPackages(packagesData);
        } catch (err) {
            console.error('Error loadPackages:', err);
            packageGrid.innerHTML = `
                <div class="package-empty-state">
                    <svg class="icon" style="width: 48px; height: 48px; color: #ef4444;" aria-hidden="true"><use href="#icon-warning"/></svg>
                    <p style="color: #f87171;">${escapeHtml(err.message)}</p>
                    <button class="soft-button" type="button" onclick="location.reload()">Coba Lagi</button>
                </div>
            `;
            showToast('Gagal memuat paket langganan', true);
        }
    }

    // Submit Edit Form to Backend
    formEditPackage?.addEventListener('submit', async (e) => {
        e.preventDefault();

        const id = editPackageId.value;
        if (!id) return;

        const payload = {
            nama_paket: editPackageName.value.trim(),
            harga: parseFloat(editPackagePrice.value) || 0,
            durasi_hari: parseInt(editPackageDuration.value, 10) || 30,
            benefit: editPackageBenefits.value.trim(),
            tersedia: editPackageStatus.value === '1' ? 1 : 0,
        };

        if (btnSubmitPackage) {
            btnSubmitPackage.disabled = true;
            btnSubmitPackage.textContent = 'Menyimpan...';
        }

        try {
            const res = await fetch(getApiUrl(`admin/packages/${id}`), {
                method: 'PUT',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                },
                body: JSON.stringify(payload),
            });

            const result = await res.json();

            if (!res.ok || result.status !== 'success') {
                throw new Error(result.error || result.message || 'Gagal menyimpan perubahan paket');
            }

            closeModal();
            showToast(`Paket "${payload.nama_paket}" berhasil diperbarui!`);
            await loadPackages();
        } catch (err) {
            console.error('Error saving package:', err);
            showToast(err.message, true);
        } finally {
            if (btnSubmitPackage) {
                btnSubmitPackage.disabled = false;
                btnSubmitPackage.textContent = 'Simpan Perubahan';
            }
        }
    });

    // Initial load
    loadPackages();
}
