// frontend/admin/dashboard/script.js
const rawUser = localStorage.getItem('user');

// ponytail: Deteksi otomatis URL API backend untuk dev-server port 3000 vs Apache LAMPP.
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
        const firstName = String(user.name || 'Admin').trim().split(/\s+/)[0];
        const userName = document.getElementById('userName');
        const profileName = document.getElementById('userProfileName');
        const avatar = document.getElementById('userAvatar');

        // Salam dinamis berdasarkan jam saat ini
        const currentHour = new Date().getHours();
        let greeting = 'Selamat pagi';
        if (currentHour >= 11 && currentHour < 15) greeting = 'Selamat siang';
        else if (currentHour >= 15 && currentHour < 18) greeting = 'Selamat sore';
        else if (currentHour >= 18 || currentHour < 4) greeting = 'Selamat malam';

        if (userName) {
            userName.textContent = firstName;
            const h1Title = userName.closest('h1');
            if (h1Title && h1Title.firstChild) {
                h1Title.firstChild.textContent = `${greeting}, `;
            }
        }
        if (profileName) profileName.textContent = user.name || 'Admin Gym';
        if (avatar) avatar.textContent = firstName.charAt(0).toUpperCase();

        document.getElementById('btnLogout')?.addEventListener('click', () => {
            localStorage.removeItem('user');
            window.location.replace('../../login/');
        });

        // Toast Helper
        const toast = document.getElementById('toast');
        let toastTimer;

        function showToast(message) {
            if (!toast) return;
            toast.textContent = message;
            toast.classList.add('show');
            window.clearTimeout(toastTimer);
            toastTimer = window.setTimeout(() => toast.classList.remove('show'), 3000);
        }

        function escapeHtml(str) {
            return String(str ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        // ====================================================
        // REAL-TIME DASHBOARD LOGIC
        // ====================================================
        const liveTimestamp = document.getElementById('liveTimestamp');
        const btnRefreshDashboard = document.getElementById('btnRefreshDashboard');
        const btnRefreshText = document.getElementById('btnRefreshText');
        const revenuePeriod = document.getElementById('revenuePeriod');
        const chartPeriodSubtitle = document.getElementById('chartPeriodSubtitle');

        let isFetching = false;
        let lastUpdated = null;

        // Render Grafik Pendapatan Dinamis (SVG)
        function renderRevenueChart(chart) {
            if (!chart || !Array.isArray(chart.series) || chart.series.length === 0) return;
            const { labels, series } = chart;
            const n = series.length;
            const svgWidth = 700;
            const svgHeight = 220;
            const paddingTop = 25;
            const paddingBottom = 25;
            const usableHeight = svgHeight - paddingTop - paddingBottom;

            const maxVal = Math.max(...series, 100000);

            const points = series.map((val, i) => {
                const x = n > 1 ? Math.round((i / (n - 1)) * svgWidth) : Math.round(svgWidth / 2);
                const y = Math.round(svgHeight - paddingBottom - (val / maxVal) * usableHeight);
                return { x, y, val, label: labels[i] || '' };
            });

            // Smooth Bezier Curve Path
            let linePath = `M ${points[0].x} ${points[0].y}`;
            for (let i = 0; i < points.length - 1; i++) {
                const p0 = points[i];
                const p1 = points[i + 1];
                const cpX = (p0.x + p1.x) / 2;
                linePath += ` C ${cpX} ${p0.y}, ${cpX} ${p1.y}, ${p1.x} ${p1.y}`;
            }

            const areaPath = `${linePath} L ${svgWidth} ${svgHeight} L 0 ${svgHeight} Z`;

            const areaEl = document.getElementById('chartArea');
            const lineEl = document.getElementById('chartLine');
            const pointsEl = document.getElementById('chartPoints');
            const labelsEl = document.getElementById('chartLabels');

            if (areaEl) areaEl.setAttribute('d', areaPath);
            if (lineEl) lineEl.setAttribute('d', linePath);

            if (pointsEl) {
                pointsEl.innerHTML = points.map(p => `
                    <circle cx="${p.x}" cy="${p.y}" r="4.5" class="chart-point-circle">
                        <title>${escapeHtml(p.label)}: Rp ${Number(p.val).toLocaleString('id-ID')}</title>
                    </circle>
                `).join('');
            }

            if (labelsEl) {
                labelsEl.innerHTML = labels.map(l => `<span>${escapeHtml(l)}</span>`).join('');
            }
        }

        // Muat data dashboard dari API Backend
        async function loadDashboardData(isManual = false) {
            if (isFetching) return;
            isFetching = true;

            if (isManual && btnRefreshDashboard) {
                btnRefreshDashboard.classList.add('is-refreshing');
                if (btnRefreshText) btnRefreshText.textContent = 'Memuat...';
            }

            const period = revenuePeriod?.value || '7months';

            try {
                const res = await fetch(getApiUrl(`admin/dashboard?period=${period}`), {
                    credentials: 'same-origin',
                });
                const json = await res.json();

                if (json.status !== 'success' || !json.data) {
                    if (isManual) showToast('Gagal memuat data dashboard.');
                    return;
                }

                const { stats, chart, recent_checkins, expiring_members, server_time } = json.data;

                // 1. Update 4 Kartu Statistik Ringkasan
                if (stats) {
                    const elTotal = document.getElementById('statTotalMembers');
                    const elActive = document.getElementById('statActiveMembers');
                    const elExpiring = document.getElementById('statExpiringMembers');
                    const elCheckins = document.getElementById('statTodayCheckins');

                    if (elTotal) elTotal.textContent = Number(stats.total_members).toLocaleString('id-ID');
                    if (elActive) elActive.textContent = Number(stats.active_members).toLocaleString('id-ID');
                    if (elExpiring) elExpiring.textContent = Number(stats.expiring_members).toLocaleString('id-ID');
                    if (elCheckins) elCheckins.textContent = Number(stats.today_checkins).toLocaleString('id-ID');

                    // Pendapatan & Pertumbuhan
                    const statRevAmount = document.getElementById('statRevenueAmount');
                    const statRevGrowth = document.getElementById('statRevenueGrowth');

                    if (statRevAmount) statRevAmount.textContent = stats.month_revenue_formatted || 'Rp 0';
                    if (statRevGrowth) {
                        statRevGrowth.textContent = stats.revenue_growth || '0%';
                        statRevGrowth.className = stats.revenue_growth_positive ? 'positive' : 'warning';
                    }
                }

                // 2. Update Subtitle Periode Grafik
                if (chartPeriodSubtitle) {
                    chartPeriodSubtitle.textContent = period === 'year'
                        ? `Ringkasan pendapatan tahun ${new Date().getFullYear()} (${chart?.total_formatted || 'Rp 0'})`
                        : `Ringkasan pendapatan 7 bulan terakhir (${chart?.total_formatted || 'Rp 0'})`;
                }

                // 3. Render Grafik Pendapatan Dinamis
                if (chart) {
                    renderRevenueChart(chart);
                }

                // 4. Update Check-in Terbaru
                const attendanceList = document.getElementById('attendanceList');
                if (attendanceList && Array.isArray(recent_checkins)) {
                    if (recent_checkins.length === 0) {
                        attendanceList.innerHTML = '<p class="muted" style="padding: 1.5rem; text-align: center;">Belum ada check-in hari ini</p>';
                    } else {
                        attendanceList.innerHTML = recent_checkins.map(item => `
                            <div class="attendance-row">
                                <div class="avatar ${item.avatar_color || 'peach'}" aria-hidden="true">${escapeHtml(item.initials || 'M')}</div>
                                <div>
                                    <a href="../member-detail/?id=${item.user_id}" style="color: inherit; text-decoration: none; font-weight: 700; transition: color 150ms ease;" onmouseover="this.style.color='#ff7a1a'" onmouseout="this.style.color='#fff'">${escapeHtml(item.name)}</a>
                                    <small>${escapeHtml(item.member_code)} · ${escapeHtml(item.nama_paket)}</small>
                                </div>
                                <time datetime="${escapeHtml(item.waktu)}">${escapeHtml(item.waktu)}</time>
                            </div>
                        `).join('');
                    }
                }

                // 5. Update Member Akan Berakhir
                const expiringTable = document.getElementById('expiringTableBody');
                if (expiringTable && Array.isArray(expiring_members)) {
                    if (expiring_members.length === 0) {
                        expiringTable.innerHTML = '<tr><td colspan="4" style="text-align: center; color: var(--color-muted, #888); padding: 1.5rem;">Tidak ada membership yang akan berakhir dalam 7 hari</td></tr>';
                    } else {
                        expiringTable.innerHTML = expiring_members.map(m => `
                            <tr>
                                <td>
                                    <a href="../member-detail/?id=${m.id}" style="color: inherit; text-decoration: none; font-weight: 700; transition: color 150ms ease;" onmouseover="this.style.color='#ff7a1a'" onmouseout="this.style.color='#fff'">${escapeHtml(m.name)}</a>
                                    <small>${escapeHtml(m.member_code)}</small>
                                </td>
                                <td>${escapeHtml(m.nama_paket)}</td>
                                <td>${escapeHtml(m.expires_at_formatted)}</td>
                                <td><span class="status warning-bg">${escapeHtml(m.status_badge)}</span></td>
                            </tr>
                        `).join('');
                    }
                }

                // 6. Update Status Timestamp Real-Time
                const now = new Date();
                const timeString = server_time || now.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
                if (liveTimestamp) {
                    liveTimestamp.textContent = `Diperbarui: ${timeString} WIB`;
                }

                lastUpdated = now;

                if (isManual) {
                    showToast('Data dashboard berhasil diperbarui!');
                }
            } catch (err) {
                console.warn('Gagal memuat data dashboard:', err);
                if (isManual) showToast('Gagal terhubung ke server backend.');
            } finally {
                isFetching = false;
                if (btnRefreshDashboard) {
                    btnRefreshDashboard.classList.remove('is-refreshing');
                    if (btnRefreshText) btnRefreshText.textContent = 'Segarkan';
                }
            }
        }

        // Listener Tombol Segarkan Manual
        btnRefreshDashboard?.addEventListener('click', () => {
            loadDashboardData(true);
        });

        // Listener Penggantian Periode Grafik
        revenuePeriod?.addEventListener('change', () => {
            loadDashboardData(false);
        });

        // ====================================================
        // BACKGROUND POLLING SETIAP 5 DETIK
        // ====================================================
        const POLLING_INTERVAL_MS = 5000;
        let pollInterval = setInterval(() => {
            if (!document.hidden) {
                loadDashboardData(false);
            }
        }, POLLING_INTERVAL_MS);

        // Jika user kembali ke tab browser, segera refresh data
        document.addEventListener('visibilitychange', () => {
            if (!document.hidden) {
                loadDashboardData(false);
            }
        });

        // ====================================================
        // MODAL TAMBAH MEMBER
        // ====================================================
        const modalAddMember = document.getElementById('modalAddMember');
        const btnOpenAddMember = document.getElementById('btnOpenAddMember');
        const btnCloseModal = document.getElementById('btnCloseModal');
        const btnCancelModal = document.getElementById('btnCancelModal');
        const formAddMember = document.getElementById('formAddMember');
        const memberNameInput = document.getElementById('memberName');

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
            if (e.target === modalAddMember) closeModal();
        });

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && modalAddMember?.classList.contains('is-active')) {
                closeModal();
            }
        });

        // Submit Pendaftaran Member Baru via API
        formAddMember?.addEventListener('submit', async (e) => {
            e.preventDefault();
            const submitBtn = formAddMember.querySelector('button[type="submit"]');
            if (submitBtn) submitBtn.disabled = true;

            const formData = new FormData(formAddMember);
            const payload = {
                nama_lengkap: formData.get('nama_lengkap'),
                nomor_telepon: formData.get('nomor_telepon'),
                jenis_kelamin: formData.get('jenis_kelamin'),
                kontak_darurat: formData.get('kontak_darurat'),
            };

            try {
                const res = await fetch(getApiUrl('admin/members'), {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    credentials: 'same-origin',
                    body: JSON.stringify(payload),
                });

                const data = await res.json();

                if (!res.ok) {
                    showToast(data.error || 'Gagal mendaftarkan member.');
                    return;
                }

                closeModal();
                formAddMember.reset();
                showToast(data.message || 'Member baru berhasil didaftarkan!');
                loadDashboardData(false);
            } catch (err) {
                showToast('Gagal terhubung ke server backend.');
            } finally {
                if (submitBtn) submitBtn.disabled = false;
            }
        });

        // Muat data saat halaman dibuka
        loadDashboardData(false);
    }
}
