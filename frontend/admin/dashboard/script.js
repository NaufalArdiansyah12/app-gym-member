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

        if (userName) userName.textContent = firstName;
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

        // Modal Tambah Member
        const modalAddMember = document.getElementById('modalAddMember');
        const btnOpenAddMember = document.getElementById('btnOpenAddMember');
        const btnCloseModal = document.getElementById('btnCloseModal');
        const btnCancelModal = document.getElementById('btnCancelModal');
        const formAddMember = document.getElementById('formAddMember');
        const memberNameInput = document.getElementById('memberName');
        const memberPackageSelect = document.getElementById('memberPackage');
        const packageActivationFields = document.getElementById('packageActivationFields');
        const memberStartDate = document.getElementById('memberStartDate');
        const packageInfoBadge = document.getElementById('packageInfoBadge');

        let availablePackages = [];

        async function loadPackagesForModal() {
            try {
                const res = await fetch(getApiUrl('admin/packages'), { credentials: 'same-origin' });
                const json = await res.json();
                if (json.status === 'success' && Array.isArray(json.data)) {
                    availablePackages = json.data.filter(p => p.tersedia);
                    if (memberPackageSelect) {
                        memberPackageSelect.innerHTML = '<option value="">-- Tanpa Paket (Registrasi Saja) --</option>' +
                            availablePackages.map(p => {
                                const price = p.harga_formatted || ('Rp ' + Number(p.harga).toLocaleString('id-ID'));
                                return `<option value="${p.id}">${escapeHtml(p.nama_paket)} — ${price} (${p.durasi_hari} hari)</option>`;
                            }).join('');
                    }
                }
            } catch (err) {
                console.warn('Gagal memuat paket:', err);
            }
        }

        function updatePackageFields() {
            const pkgId = Number(memberPackageSelect?.value || 0);
            const selected = availablePackages.find(p => p.id === pkgId);

            if (selected) {
                if (packageActivationFields) packageActivationFields.style.display = 'block';
                if (memberStartDate && !memberStartDate.value) {
                    memberStartDate.value = new Date().toISOString().slice(0, 10);
                }
                if (packageInfoBadge) {
                    packageInfoBadge.hidden = false;
                    const price = selected.harga_formatted || ('Rp ' + Number(selected.harga).toLocaleString('id-ID'));
                    packageInfoBadge.innerHTML = `<strong>${escapeHtml(selected.nama_paket)}</strong> · Durasi ${selected.durasi_hari} Hari · Biaya: <strong>${price}</strong>`;
                }
            } else {
                if (packageActivationFields) packageActivationFields.style.display = 'none';
                if (packageInfoBadge) packageInfoBadge.hidden = true;
            }
        }

        memberPackageSelect?.addEventListener('change', updatePackageFields);

        function openModal() {
            if (!modalAddMember) return;
            if (formAddMember) formAddMember.reset();
            if (memberStartDate) memberStartDate.value = new Date().toISOString().slice(0, 10);
            updatePackageFields();
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

        // Data & Renderer Grafik Dashboard (Member vs Pendapatan)
        const dashboardCharts = { member: null, revenue: null };
        let currentChartPoints = [];
        let currentChartType = 'member';

        const chartContainer = document.getElementById('dashboardChartContainer');
        const chartSvg = document.getElementById('dashboardChartSvg');
        const hoverLine = document.getElementById('hoverLine');
        const hoverPoint = document.getElementById('hoverPoint');
        const chartTooltip = document.getElementById('chartTooltip');
        const tooltipMonth = document.getElementById('tooltipMonth');
        const tooltipValue = document.getElementById('tooltipValue');
        const chartLabels = document.getElementById('chartLabels');

        if (chartContainer?.dataset?.init) {
            try {
                currentChartPoints = JSON.parse(chartContainer.dataset.init);
            } catch {}
        }

        function showHoverAt(index) {
            if (!currentChartPoints || !currentChartPoints[index]) return;
            const p = currentChartPoints[index];
            const containerRect = chartContainer?.getBoundingClientRect();
            const svgRect = chartSvg?.getBoundingClientRect();
            if (!containerRect || !svgRect) return;

            if (hoverLine) {
                hoverLine.setAttribute('x1', String(p.x));
                hoverLine.setAttribute('x2', String(p.x));
                hoverLine.style.display = 'block';
            }
            if (hoverPoint) {
                hoverPoint.setAttribute('cx', String(p.x));
                hoverPoint.setAttribute('cy', String(p.y));
                hoverPoint.style.display = 'block';
            }

            if (chartTooltip && tooltipMonth && tooltipValue) {
                tooltipMonth.textContent = p.label + ' 2026';
                tooltipValue.textContent = currentChartType === 'revenue'
                    ? ('Rp ' + Number(p.val).toLocaleString('id-ID'))
                    : (p.val + ' Member');

                const svgScaleX = svgRect.width / 700;
                const svgScaleY = svgRect.height / 220;
                const pixelX = (svgRect.left - containerRect.left) + (p.x * svgScaleX);
                const pixelY = (svgRect.top - containerRect.top) + (p.y * svgScaleY);

                chartTooltip.style.left = `${pixelX}px`;
                chartTooltip.style.top = `${pixelY}px`;
                chartTooltip.classList.add('is-active');
                chartTooltip.setAttribute('aria-hidden', 'false');
            }

            const labelSpans = chartLabels?.querySelectorAll('span');
            labelSpans?.forEach((s, i) => {
                s.classList.toggle('is-active', i === index);
            });
        }

        function hideHover() {
            if (hoverLine) hoverLine.style.display = 'none';
            if (hoverPoint) hoverPoint.style.display = 'none';
            if (chartTooltip) {
                chartTooltip.classList.remove('is-active');
                chartTooltip.setAttribute('aria-hidden', 'true');
            }
            const labelSpans = chartLabels?.querySelectorAll('span');
            labelSpans?.forEach(s => s.classList.remove('is-active'));
        }

        chartContainer?.addEventListener('mousemove', (e) => {
            if (!currentChartPoints.length || !chartSvg) return;
            const svgRect = chartSvg.getBoundingClientRect();
            const mouseX = e.clientX - svgRect.left;
            const svgScaleX = svgRect.width / 700;
            const svgX = Math.max(0, Math.min(700, mouseX / svgScaleX));

            let closestIndex = 0;
            let minDiff = Infinity;
            currentChartPoints.forEach((p, i) => {
                const diff = Math.abs(p.x - svgX);
                if (diff < minDiff) {
                    minDiff = diff;
                    closestIndex = i;
                }
            });

            showHoverAt(closestIndex);
        });

        chartContainer?.addEventListener('mouseleave', hideHover);

        chartLabels?.addEventListener('mouseover', (e) => {
            const span = e.target.closest('span[data-index]');
            if (span) {
                const idx = Number(span.dataset.index);
                showHoverAt(idx);
            }
        });

        function renderChart(chartData, type = 'member') {
            const heading = document.getElementById('chartHeading');
            const subheading = document.getElementById('chartSubheading');
            const summaryVal = document.getElementById('chartSummaryValue');
            const chartLine = document.getElementById('chartLine');
            const chartArea = document.getElementById('chartArea');

            if (!chartData || !Array.isArray(chartData.labels) || !Array.isArray(chartData.series)) return;

            currentChartType = type;
            if (heading) heading.textContent = type === 'revenue' ? 'Pendapatan' : 'Pendaftaran Member';
            if (subheading) subheading.textContent = type === 'revenue' ? 'Tren pendapatan 7 bulan terakhir' : 'Tren member baru 7 bulan terakhir';
            if (summaryVal) summaryVal.textContent = chartData.total_formatted || '0';

            const labels = chartData.labels;
            const series = chartData.series.map(Number);
            const maxVal = Math.max(...series, 1);
            const count = labels.length;

            if (chartLabels) {
                chartLabels.innerHTML = labels.map((l, i) => `<span data-index="${i}">${escapeHtml(l)}</span>`).join('');
            }

            const width = 700;
            const height = 220;
            const bottomPad = 195;
            const usableH = 165;

            const points = series.map((val, idx) => {
                const x = count > 1 ? (idx / (count - 1)) * width : width / 2;
                const y = bottomPad - (val / maxVal) * usableH;
                return { x: Math.round(x), y: Math.round(y), val, label: labels[idx] };
            });

            currentChartPoints = points;
            hideHover();

            if (points.length === 0) return;

            let pathD = `M ${points[0].x} ${points[0].y}`;
            for (let i = 1; i < points.length; i++) {
                const prev = points[i - 1];
                const curr = points[i];
                const cX = Math.round(prev.x + (curr.x - prev.x) / 2);
                pathD += ` C ${cX} ${prev.y} ${cX} ${curr.y} ${curr.x} ${curr.y}`;
            }

            if (chartLine) chartLine.setAttribute('d', pathD);
            if (chartArea) chartArea.setAttribute('d', `${pathD} L ${width} ${height} L 0 ${height} Z`);
        }

        const chartMetricSelect = document.getElementById('chartMetricSelect');
        chartMetricSelect?.addEventListener('change', (e) => {
            const selected = e.target.value;
            if (dashboardCharts[selected]) {
                renderChart(dashboardCharts[selected], selected);
            }
        });

        // Muat data dashboard dari API Backend
        async function loadDashboardData() {
            try {
                const res = await fetch(getApiUrl('admin/dashboard'), { credentials: 'same-origin' });
                const json = await res.json();

                if (json.status !== 'success' || !json.data) return;

                const { stats, member_chart, revenue_chart, chart, recent_checkins, expiring_members } = json.data;

                // 1. Update Kartu Statistik
                if (stats) {
                    const elTotal = document.getElementById('statTotalMembers');
                    const elActive = document.getElementById('statActiveMembers');
                    const elExpiring = document.getElementById('statExpiringMembers');
                    const elCheckins = document.getElementById('statTodayCheckins');

                    if (elTotal) elTotal.textContent = Number(stats.total_members).toLocaleString('id-ID');
                    if (elActive) elActive.textContent = Number(stats.active_members).toLocaleString('id-ID');
                    if (elExpiring) elExpiring.textContent = Number(stats.expiring_members).toLocaleString('id-ID');
                    if (elCheckins) elCheckins.textContent = Number(stats.today_checkins).toLocaleString('id-ID');
                }

                // 2. Update Check-in Terbaru
                const attendanceList = document.getElementById('attendanceList');
                if (attendanceList && Array.isArray(recent_checkins)) {
                    if (recent_checkins.length === 0) {
                        attendanceList.innerHTML = '<p class="muted" style="padding: 1rem; text-align: center;">Belum ada check-in hari ini</p>';
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

                // 3. Update Member Akan Berakhir
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

                // 4. Update Grafik (Default: Pendaftaran Member)
                dashboardCharts.member = member_chart || chart;
                dashboardCharts.revenue = revenue_chart;
                const activeMetric = chartMetricSelect?.value || 'member';
                renderChart(dashboardCharts[activeMetric] || dashboardCharts.member, activeMetric);
            } catch (err) {
                console.warn('Gagal memuat data dashboard:', err);
            }
        }

        function escapeHtml(str) {
            return String(str ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

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
                id_paket: Number(formData.get('id_paket')) || 0,
                tanggal_mulai: formData.get('tanggal_mulai') || undefined,
                metode_pembayaran: formData.get('metode_pembayaran') || 'tunai',
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
                updatePackageFields();
                showToast(data.message || 'Member baru berhasil didaftarkan!');
                if (data.data && data.data.id) {
                    setTimeout(() => {
                        window.location.href = `../member-detail/?id=${data.data.id}`;
                    }, 800);
                } else {
                    loadDashboardData();
                }
            } catch (err) {
                showToast('Gagal terhubung ke server backend.');
            } finally {
                if (submitBtn) submitBtn.disabled = false;
            }
        });

        loadDashboardData();
        loadPackagesForModal();
    }
}
