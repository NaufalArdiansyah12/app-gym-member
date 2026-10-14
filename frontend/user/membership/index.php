<?php
// frontend/user/membership/index.php
$page = 'membership';
$title = 'Status & Sisa Waktu Membership';
$pageEyebrow = 'HALAMAN MEMBER · ' . strtoupper(date('d M Y'));
$pageHeading = 'Status & Sisa Waktu Membership';
$pageSubtitle = 'Informasi detail paket aktif dan sisa durasi membership Anda.';

ob_start();
?>
<!-- Top Section: Split Kiri - Kanan (Kartu Member & QR di Kiri, Status & Sisa Waktu di Kanan) -->
<section class="membership-split-grid" aria-label="Ringkasan dan Kartu Member">
    <!-- SISI KIRI: Kartu Digital Member & QR Code Presensi -->
    <article class="panel member-card-panel">
        <div class="panel-heading">
            <div>
                <h2>Kartu Member Digital</h2>
                <p class="muted">Tunjukkan QR Code ini kepada resepsionis saat tiba di gym.</p>
            </div>
            <div class="heading-actions">
                <button type="button" id="btnDownloadQr" class="btn-action-download" title="Unduh QR Code Member">
                    <svg class="icon" aria-hidden="true" focusable="false"><use href="#icon-download"/></svg>
                    <span>Download QR</span>
                </button>
            </div>
        </div>

        <!-- Digital Membership Badge / Pass -->
        <div class="member-pass-card">
            <div class="pass-card-glow" aria-hidden="true"></div>
            <div class="pass-card-content">
                <div class="pass-header">
                    <div class="pass-brand">
                        <svg class="icon" aria-hidden="true" focusable="false"><use href="#icon-gym"/></svg>
                        <span>USER <strong>GYM</strong> PASS</span>
                    </div>
                    <span class="pass-chip-status active" id="passCardStatus">MEMBER AKTIF</span>
                </div>

                <div class="pass-body">
                    <div class="pass-qr-wrap">
                        <div id="memberQrCode" class="pass-qr-canvas" aria-label="QR Code Presensi Member"></div>
                        <small class="pass-scan-hint">Scan untuk Presensi</small>
                    </div>
                    <div class="pass-meta">
                        <div class="pass-meta-group">
                            <span class="pass-label">NAMA MEMBER</span>
                            <strong class="pass-value" id="passMemberName">Gym Member</strong>
                        </div>
                        <div class="pass-meta-group">
                            <span class="pass-label">KODE MEMBER</span>
                            <div class="pass-code-row">
                                <span class="pass-code-badge" id="passMemberCode">GYM-0102</span>
                                <button type="button" id="btnCopyMemberCode" class="pass-copy-btn" title="Salin Kode Member">
                                    <svg class="icon" aria-hidden="true" focusable="false"><use href="#icon-receipt"/></svg>
                                    <span>Salin</span>
                                </button>
                                <button type="button" id="btnDownloadQrInline" class="pass-download-btn" title="Unduh QR Code PNG">
                                    <svg class="icon" aria-hidden="true" focusable="false"><use href="#icon-download"/></svg>
                                    <span>Download QR</span>
                                </button>
                            </div>
                        </div>
                        <div class="pass-meta-row">
                            <div class="pass-meta-group">
                                <span class="pass-label">PAKET AKTIF</span>
                                <span class="pass-subvalue" id="passPackageName">Platinum</span>
                            </div>
                            <div class="pass-meta-group">
                                <span class="pass-label">ID MEMBER</span>
                                <span class="pass-subvalue" id="passFooterId">ID: #0102</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="pass-footer">
                    <span class="pass-footer-tip">
                        <svg class="icon" aria-hidden="true" focusable="false"><use href="#icon-check"/></svg>
                        Tunjukkan kartu ini di resepsionis untuk akses & presensi
                    </span>
                    <span class="pass-footer-id" id="memActiveCode">GYM-0102</span>
                </div>
            </div>
        </div>
    </article>

    <!-- SISI KANAN: Status & Sisa Waktu Membership -->
    <article class="panel membership-status-panel">
        <div class="panel-heading">
            <div>
                <h2>Status & Sisa Waktu</h2>
                <p class="muted">Detail masa aktif dan akses paket langganan Anda</p>
            </div>
            <span class="status green-bg" id="memActiveStatus">Aktif</span>
        </div>

        <div class="membership-status-body">
            <!-- Big Days Remaining Counter Card -->
            <div class="days-remaining-card">
                <div class="days-card-icon" aria-hidden="true">
                    <svg class="icon" focusable="false"><use href="#icon-warning"/></svg>
                </div>
                <div class="days-card-info">
                    <span class="days-card-label">Sisa Waktu Membership</span>
                    <div class="days-card-value">
                        <strong id="memActiveDays">36</strong>
                        <span>Hari Lagi</span>
                    </div>
                    <small class="days-card-sub" id="memPeriodSub">Masa aktif langganan sedang berjalan</small>
                </div>
            </div>

            <!-- Mini Stats 2 Cards -->
            <div class="status-meta-grid">
                <div class="status-meta-card">
                    <div class="stat-icon orange" aria-hidden="true">
                        <svg class="icon" focusable="false"><use href="#icon-package"/></svg>
                    </div>
                    <div>
                        <span class="meta-label">Paket Langganan</span>
                        <strong id="memActiveName">Platinum</strong>
                        <small class="positive" id="memActiveDuration">2 Bulan Durasi</small>
                    </div>
                </div>
                <div class="status-meta-card">
                    <div class="stat-icon green" aria-hidden="true">
                        <svg class="icon" focusable="false"><use href="#icon-check"/></svg>
                    </div>
                    <div>
                        <span class="meta-label">Akses Fasilitas</span>
                        <strong>Full Access</strong>
                        <small class="positive">Seluruh Area & Alat</small>
                    </div>
                </div>
            </div>

            <!-- Benefit & Access Facilities -->
            <div class="benefit-list-wrap">
                <div class="benefit-item">
                    <svg class="icon icon-benefit" aria-hidden="true" focusable="false"><use href="#icon-check"/></svg>
                    <div>
                        <b>Akses Gym & Loker Harian</b>
                        <p>Bebas memakai seluruh fasilitas cardio, weight training, dan loker harian.</p>
                    </div>
                </div>
                <div class="benefit-item">
                    <svg class="icon icon-benefit" aria-hidden="true" focusable="false"><use href="#icon-check"/></svg>
                    <div>
                        <b>Presensi Cepat via Scan QR</b>
                        <p>Resepsionis memverifikasi keaktifan member langsung dari scan QR kartu digital Anda.</p>
                    </div>
                </div>
            </div>
        </div>
    </article>
</section>

<!-- Panel: Daftar Riwayat Paket Langganan -->
<article class="panel">
    <div class="panel-heading">
        <div>
            <h2>Daftar Riwayat Paket Langganan</h2>
            <p class="muted">Informasi paket membership yang pernah Anda beli atau aktifkan.</p>
        </div>
    </div>
    <div class="table-wrap" role="region" aria-label="Riwayat Semua Membership" tabindex="0">
        <table>
            <thead>
                <tr>
                    <th scope="col">NAMA PAKET</th>
                    <th scope="col">PERIODE AKTIF</th>
                    <th scope="col">DURASI</th>
                    <th scope="col">BIAYA</th>
                    <th scope="col">METODE PEMBAYARAN</th>
                    <th scope="col">STATUS</th>
                </tr>
            </thead>
            <tbody id="membershipFullTableBody">
                <!-- Rendered by JS -->
            </tbody>
        </table>
    </div>
</article>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';
