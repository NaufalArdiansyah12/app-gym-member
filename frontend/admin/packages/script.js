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
    const btnOpenAddPackage = document.getElementById('btnOpenAddPackage');
    const modalPackage = document.getElementById('modalPackage');
    const modalPackageTitle = document.getElementById('modalPackageTitle');
    const modalPackageSubtitle = document.getElementById('modalPackageSubtitle');
    const btnCloseModal = document.getElementById('btnCloseModal');
    const btnCancelModal = document.getElementById('btnCancelModal');
    const formPackage = document.getElementById('formPackage');
    const btnSubmitPackage = document.getElementById('btnSubmitPackage');
    const toast = document.getElementById('toast');

    // Inputs & Presets
    const packageId = document.getElementById('packageId');
    const packageName = document.getElementById('packageName');
    const packagePrice = document.getElementById('packagePrice');
    const packageDuration = document.getElementById('packageDuration');
    const packageBenefits = document.getElementById('packageBenefits');
    const packageStatus = document.getElementById('packageStatus');
    const durationPresets = document.getElementById('durationPresets');
    const durationHint = document.getElementById('durationHint');

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
        if (!modalPackage) return;
        modalPackage.classList.add('is-active');
        modalPackage.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
    }

    function closeModal() {
        if (!modalPackage) return;
        modalPackage.classList.remove('is-active');
        modalPackage.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
        formPackage?.reset();
    }

    btnCloseModal?.addEventListener('click', closeModal);
    btnCancelModal?.addEventListener('click', closeModal);

    modalPackage?.addEventListener('click', (e) => {
        if (e.target === modalPackage) closeModal();
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && modalPackage?.classList.contains('is-active')) {
            closeModal();
        }
    });

    // Durasi Helper & Sinkronisasi
    function updateDurationHint(days) {
        if (!durationHint) return;
        const d = Number(days) || 0;
        if (d <= 0) {
            durationHint.textContent = 'Durasi hari wajib lebih dari 0.';
        } else if (d % 30 === 0) {
            const m = d / 30;
            durationHint.textContent = `Dihitung otomatis: ${d} hari = ${m} bulan membership`;
        } else {
            const approx = (d / 30).toFixed(1);
            durationHint.textContent = `${d} hari membership (sekitar ${approx} bulan)`;
        }
    }

    function syncPresetFromDays(days) {
        if (!durationPresets) return;
        const targetDays = String(days);
        let matched = false;

        durationPresets.querySelectorAll('.preset-btn').forEach((btn) => {
            if (btn.dataset.days === targetDays) {
                btn.classList.add('active');
                matched = true;
            } else {
                btn.classList.remove('active');
            }
        });

        if (!matched) {
            const customBtn = durationPresets.querySelector('[data-days="custom"]');
            if (customBtn) customBtn.classList.add('active');
        }
    }

    function detectDurationFromName(name) {
        if (!name) return null;
        const lower = name.toLowerCase();

        // 1. Tahun
        const yr = lower.match(/(\d+)\s*(?:tahun|thn|year)/);
        if (yr) return parseInt(yr[1], 10) * 360;

        // 2. Bulan
        const mo = lower.match(/(\d+)\s*(?:bulan|bln|month)/);
        if (mo) return parseInt(mo[1], 10) * 30;

        // 3. Hari
        const dy = lower.match(/(\d+)\s*(?:hari|day)/);
        if (dy) return parseInt(dy[1], 10);

        return null;
    }

    // Klik tombol preset durasi (1 Bulan, 2 Bulan, 3 Bulan, dll.)
    durationPresets?.addEventListener('click', (e) => {
        const btn = e.target.closest('.preset-btn');
        if (!btn) return;

        durationPresets.querySelectorAll('.preset-btn').forEach((b) => b.classList.remove('active'));
        btn.classList.add('active');

        if (btn.dataset.days === 'custom') {
            packageDuration?.focus();
            packageDuration?.select();
            updateDurationHint(parseInt(packageDuration.value, 10) || 0);
        } else {
            const days = parseInt(btn.dataset.days, 10);
            const months = btn.dataset.months;
            if (packageDuration) packageDuration.value = days;
            updateDurationHint(days);

            // Auto sesuaikan nama paket jika masih kosong atau berpola standar
            if (packageName) {
                const currentName = packageName.value.trim();
                const isDefaultPattern = currentName === '' || /^Paket\s+(\d+\s*(?:Bulan|Hari)|1\s*Tahun)/i.test(currentName);
                if (isDefaultPattern) {
                    packageName.value = months === '12' ? 'Paket 12 Bulan (1 Tahun)' : `Paket ${months} Bulan`;
                }
            }
        }
    });

    // Ketik durasi hari langsung -> sinkronkan hint dan preset
    packageDuration?.addEventListener('input', () => {
        const days = parseInt(packageDuration.value, 10) || 0;
        updateDurationHint(days);
        syncPresetFromDays(days);

        // Jika nama paket masih kosong / standar, otomatis sesuaikan nama
        if (days > 0 && days % 30 === 0 && packageName) {
            const currentName = packageName.value.trim();
            const isDefaultPattern = currentName === '' || /^Paket\s+\d+\s*Bulan/i.test(currentName);
            if (isDefaultPattern) {
                packageName.value = `Paket ${days / 30} Bulan`;
            }
        }
    });

    // Ketik nama paket -> deteksi kata seperti "2 bulan" otomatis sesuaikan hari
    packageName?.addEventListener('input', () => {
        // Hanya auto-detect jika user sedang menambah paket baru
        if (!packageId?.value) {
            const detected = detectDurationFromName(packageName.value);
            if (detected && packageDuration) {
                packageDuration.value = detected;
                updateDurationHint(detected);
                syncPresetFromDays(detected);
            }
        }
    });

    // Buka Modal Tambah Paket
    function openAddModal() {
        if (modalPackageTitle) modalPackageTitle.textContent = 'Tambah Paket Langganan Baru';
        if (modalPackageSubtitle) modalPackageSubtitle.textContent = 'Atur nama paket, durasi hari, harga, dan benefit fasilitas.';
        if (btnSubmitPackage) btnSubmitPackage.textContent = 'Tambah Paket Baru';

        if (packageId) packageId.value = '';
        if (packageName) packageName.value = 'Paket 2 Bulan';
        if (packagePrice) packagePrice.value = '';
        if (packageDuration) packageDuration.value = '60';
        if (packageBenefits) {
            packageBenefits.value = 'Akses seluruh area fitness & cardio\nFree locker room & shower bersih\n1x Sesi konsultasi instruktur';
        }
        if (packageStatus) packageStatus.value = '1';

        syncPresetFromDays(60);
        updateDurationHint(60);
        openModal();
        packagePrice?.focus();
    }

    btnOpenAddPackage?.addEventListener('click', openAddModal);

    // Buka Modal Edit Paket
    function editPackage(pkgId) {
        const pkg = packagesData.find((p) => p.id === Number(pkgId));
        if (!pkg) {
            showToast('Data paket tidak ditemukan', true);
            return;
        }

        if (modalPackageTitle) modalPackageTitle.textContent = 'Edit Paket Langganan';
        if (modalPackageSubtitle) modalPackageSubtitle.textContent = 'Ubah nama paket, harga, durasi, dan daftar benefit fasilitas.';
        if (btnSubmitPackage) btnSubmitPackage.textContent = 'Simpan Perubahan';

        if (packageId) packageId.value = pkg.id;
        if (packageName) packageName.value = pkg.nama_paket || '';
        if (packagePrice) packagePrice.value = Math.round(pkg.harga || 0);
        if (packageDuration) packageDuration.value = pkg.durasi_hari || 30;
        if (packageBenefits) packageBenefits.value = pkg.benefit || '';
        if (packageStatus) packageStatus.value = pkg.tersedia ? '1' : '0';

        syncPresetFromDays(pkg.durasi_hari || 30);
        updateDurationHint(pkg.durasi_hari || 30);
        openModal();
        packageName?.focus();
    }

    // Hapus Paket Langganan
    async function deletePackage(pkgId, pkgName) {
        const confirmed = window.confirm(`Apakah Anda yakin ingin menghapus paket "${pkgName}"?`);
        if (!confirmed) return;

        try {
            const res = await fetch(getApiUrl(`admin/packages/${pkgId}`), {
                method: 'DELETE',
                credentials: 'same-origin',
                headers: { 'Accept': 'application/json' },
            });
            const result = await res.json();

            if (!res.ok || result.status !== 'success') {
                throw new Error(result.error || result.message || 'Gagal menghapus paket');
            }

            showToast(result.message || `Paket "${pkgName}" berhasil dihapus`);
            await loadPackages();
        } catch (err) {
            console.error('Error deletePackage:', err);
            showToast(err.message, true);
        }
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
            let tierBadge = `STARTER · ${durasi % 30 === 0 ? months + ' BULAN' : durasi + ' HARI'}`;
            let tierSubtitle = 'Pilihan fleksibel bagi pemula untuk adaptasi latihan harian.';
            let equivalentText = `Rp ${formatRupiah(pkg.harga)} / bulan`;

            if (isPopular) {
                tierClass = 'tier-popular';
                tierBadge = `POPULER · ${durasi % 30 === 0 ? months + ' BULAN' : durasi + ' HARI'}`;
                tierSubtitle = 'Paket terlaris dengan total peminat terbanyak saat ini.';
                equivalentText = months > 1 ? `Setara Rp ${formatRupiah(perMonth)} / bulan` : equivalentText;
            } else if (durasi === 60 || pkg.nama_paket.toLowerCase().includes('2 bulan')) {
                tierClass = 'tier-regular';
                tierBadge = `ROUTINE · 2 BULAN`;
                tierSubtitle = 'Paket 2 bulan optimal untuk pembentukan kebiasaan latihan konsisten.';
                equivalentText = `Setara Rp ${formatRupiah(perMonth)} / bulan`;
            } else if (durasi >= 180 || pkg.nama_paket.toLowerCase().includes('6 bulan') || pkg.nama_paket.toLowerCase().includes('12 bulan') || pkg.nama_paket.toLowerCase().includes('tahun')) {
                tierClass = 'tier-vip';
                tierBadge = `VIP PRO · ${durasi % 30 === 0 ? months + ' BULAN' : durasi + ' HARI'}`;
                tierSubtitle = 'Pilihan hemat bernilai tinggi untuk komitmen jangka panjang.';
                equivalentText = `Setara Rp ${formatRupiah(perMonth)} / bulan (Hemat)`;
            } else if (months > 1) {
                tierBadge = `STANDARD · ${durasi % 30 === 0 ? months + ' BULAN' : durasi + ' HARI'}`;
                tierSubtitle = 'Paket membership pilihan untuk hasil kebugaran optimal.';
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
                            <span>Durasi Keanggotaan: <strong>${durasi} Hari${durasi % 30 === 0 ? ` (${months} Bulan)` : ''}</strong></span>
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
                        <div class="card-actions-group">
                            <button class="btn-pro-edit" type="button" data-id="${pkg.id}">
                                ✎ Edit Paket
                            </button>
                            <button class="btn-pro-delete" type="button" data-id="${pkg.id}" data-name="${escapeHtml(pkg.nama_paket)}" title="Hapus Paket">
                                <svg class="icon" aria-hidden="true" focusable="false"><use href="#icon-trash"/></svg>
                            </button>
                        </div>
                    </div>
                </article>
            `;
        }).join('');

        // Attach event listeners
        packageGrid.querySelectorAll('.btn-pro-edit').forEach((btn) => {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                const id = btn.getAttribute('data-id');
                editPackage(id);
            });
        });

        packageGrid.querySelectorAll('.btn-pro-delete').forEach((btn) => {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                const id = btn.getAttribute('data-id');
                const name = btn.getAttribute('data-name');
                deletePackage(id, name);
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

    // Submit Form (Tambah / Edit) to Backend
    formPackage?.addEventListener('submit', async (e) => {
        e.preventDefault();

        const id = packageId?.value ? parseInt(packageId.value, 10) : null;
        const isEdit = Boolean(id);

        const payload = {
            nama_paket: packageName.value.trim(),
            harga: parseFloat(packagePrice.value) || 0,
            durasi_hari: parseInt(packageDuration.value, 10) || 30,
            benefit: packageBenefits.value.trim(),
            tersedia: packageStatus.value === '1' ? 1 : 0,
        };

        if (btnSubmitPackage) {
            btnSubmitPackage.disabled = true;
            btnSubmitPackage.textContent = isEdit ? 'Menyimpan...' : 'Menambahkan...';
        }

        try {
            const endpoint = isEdit ? `admin/packages/${id}` : 'admin/packages';
            const method = isEdit ? 'PUT' : 'POST';

            const res = await fetch(getApiUrl(endpoint), {
                method: method,
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                },
                body: JSON.stringify(payload),
            });

            const result = await res.json();

            if (!res.ok || result.status !== 'success') {
                throw new Error(result.error || result.message || 'Gagal memproses paket');
            }

            closeModal();
            showToast(result.message || (isEdit ? `Paket "${payload.nama_paket}" berhasil diperbarui!` : `Paket "${payload.nama_paket}" berhasil ditambahkan!`));
            await loadPackages();
        } catch (err) {
            console.error('Error saving package:', err);
            showToast(err.message, true);
        } finally {
            if (btnSubmitPackage) {
                btnSubmitPackage.disabled = false;
                btnSubmitPackage.textContent = isEdit ? 'Simpan Perubahan' : 'Tambah Paket Baru';
            }
        }
    });

    // Initial load
    loadPackages();
}
