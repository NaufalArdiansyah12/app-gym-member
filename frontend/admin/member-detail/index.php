<?php
// frontend/admin/member-detail/index.php
$page = 'members';
$title = 'Detail Member';
$pageSubtitle = 'Informasi lengkap profil, langganan, dan riwayat aktivitas member.';

ob_start();
?>
<div class="detail-container">
    <div class="top-navigation">
        <a href="../members/" class="back-link">
            <svg class="icon" aria-hidden="true" focusable="false" style="width: 16px; height: 16px;"><use href="#icon-chevron"/></svg>
            Kembali ke Daftar Member
        </a>
    </div>

    <!-- Header Kartu Profil Member -->
    <section class="panel detail-header-card" id="memberHeaderCard">
        <div class="detail-identity">
            <div class="avatar peach large-avatar" id="detailAvatar">M</div>
            <div class="identity-info">
                <div class="title-row">
                    <h2 id="detailName">Memuat...</h2>
                    <span id="detailBadge" class="status muted-bg">Memuat...</span>
                </div>
                <p class="muted">
                    <span id="detailCode" class="member-code-tag">GYM-...</span> · 
                    Terdaftar sejak <span id="detailCreatedAt">-</span>
                </p>
            </div>
        </div>
        <div class="header-actions">
            <button id="btnOpenRenew" class="primary-button" type="button">＋ Perpanjang Paket</button>
            <button id="btnQuickCheckin" class="soft-button" type="button">✓ Check-in Cepat</button>
            <button id="btnOpenEdit" class="soft-button" type="button">
                <svg class="icon" aria-hidden="true" focusable="false" style="width: 14px; height: 14px; display: inline-block; vertical-align: -2px; margin-right: 4px;"><use href="#icon-edit"/></svg>
                Edit Profil
            </button>
            <button id="btnDeleteMember" class="soft-button danger-button" type="button">
                <svg class="icon" aria-hidden="true" focusable="false" style="width: 14px; height: 14px; display: inline-block; vertical-align: -2px; margin-right: 4px;"><use href="#icon-trash"/></svg>
                Hapus
            </button>
        </div>
    </section>

    <!-- Grid Profil & Status Membership -->
    <div class="detail-main-grid">
        <!-- Kolom Profil -->
        <article class="panel info-panel">
            <div class="panel-heading">
                <div>
                    <h2>Informasi Pribadi</h2>
                    <p class="muted">Data kontak dan identitas member</p>
                </div>
            </div>
            <div class="info-list">
                <div class="info-item">
                    <span class="info-label">Nomor WhatsApp / HP</span>
                    <span class="info-value" id="detailPhone">-</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Email</span>
                    <span class="info-value" id="detailEmail">-</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Jenis Kelamin</span>
                    <span class="info-value" id="detailGender">-</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Kontak Darurat</span>
                    <span class="info-value" id="detailEmergency">-</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Alamat Lengkap</span>
                    <span class="info-value" id="detailAddress">-</span>
                </div>
            </div>
        </article>

        <!-- Kolom Status Langganan & Metrik -->
        <article class="panel info-panel">
            <div class="panel-heading">
                <div>
                    <h2>Ringkasan Membership</h2>
                    <p class="muted">Status paket dan metrik kehadiran</p>
                </div>
            </div>
            <div class="summary-stats-grid">
                <div class="summary-stat-box">
                    <small>Paket Aktif / Terakhir</small>
                    <strong id="detailActivePackage">-</strong>
                </div>
                <div class="summary-stat-box">
                    <small>Masa Berlaku Berakhir</small>
                    <strong id="detailExpiresAt">-</strong>
                </div>
                <div class="summary-stat-box">
                    <small>Total Presensi (Kunjungan)</small>
                    <strong id="detailTotalCheckins">0</strong>
                </div>
                <div class="summary-stat-box">
                    <small>Total Transaksi Pembayaran</small>
                    <strong id="detailTotalSpent">Rp 0</strong>
                </div>
            </div>
        </article>

        <!-- Kolom QR Code Presensi -->
        <article class="panel info-panel qr-detail-panel">
            <div class="panel-heading">
                <div>
                    <h2>QR Code Presensi</h2>
                    <p class="muted">Kode digital absensi kehadiran member</p>
                </div>
                <button id="btnPrintMemberQr" class="soft-button" type="button" title="Cetak Kartu Member">
                    <svg class="icon" aria-hidden="true" focusable="false" style="width: 14px; height: 14px; display: inline-block; vertical-align: -2px; margin-right: 4px;"><use href="#icon-printer"/></svg>
                    Cetak QR
                </button>
            </div>
            <div class="detail-qr-container">
                <div class="qr-canvas-holder" id="detailMemberQr"></div>
                <div class="detail-qr-info">
                    <strong class="member-code-tag" id="detailMemberQrCode" style="font-size: 16px;">GYM-...</strong>
                    <p class="muted" style="margin: 6px 0 0; font-size: 12px;">Gunakan QR ini untuk scanner absensi kehadiran.</p>
                </div>
            </div>
        </article>
    </div>

    <!-- Riwayat Aktivitas & Tab Navigasi -->
    <section class="panel history-panel">
        <div class="history-tabs" role="tablist">
            <button class="tab-button active" role="tab" aria-selected="true" data-tab="tab-subscriptions">
                Riwayat Langganan (<span id="countSubscriptions">0</span>)
            </button>
            <button class="tab-button" role="tab" aria-selected="false" data-tab="tab-transactions">
                Riwayat Transaksi (<span id="countTransactions">0</span>)
            </button>
            <button class="tab-button" role="tab" aria-selected="false" data-tab="tab-attendances">
                Riwayat Presensi (<span id="countAttendances">0</span>)
            </button>
        </div>

        <!-- Tab 1: Langganan -->
        <div class="tab-content active" id="tab-subscriptions" role="tabpanel">
            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th scope="col">PAKET</th>
                            <th scope="col">DURASI</th>
                            <th scope="col">HARGA</th>
                            <th scope="col">TANGGAL MULAI</th>
                            <th scope="col">TANGGAL SELESAI</th>
                            <th scope="col">STATUS</th>
                        </tr>
                    </thead>
                    <tbody id="tableSubscriptionsBody">
                        <tr><td colspan="6" class="table-empty">Memuat data...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Tab 2: Transaksi -->
        <div class="tab-content" id="tab-transactions" role="tabpanel" hidden>
            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th scope="col">ID TRANSAKSI</th>
                            <th scope="col">PAKET</th>
                            <th scope="col">JUMLAH BAYAR</th>
                            <th scope="col">METODE</th>
                            <th scope="col">WAKTU PEMBAYARAN</th>
                        </tr>
                    </thead>
                    <tbody id="tableTransactionsBody">
                        <tr><td colspan="5" class="table-empty">Memuat data...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Tab 3: Presensi -->
        <div class="tab-content" id="tab-attendances" role="tabpanel" hidden>
            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th scope="col">TANGGAL</th>
                            <th scope="col">JAM KEDATANGAN</th>
                            <th scope="col">WAKTU LENGKAP</th>
                        </tr>
                    </thead>
                    <tbody id="tableAttendancesBody">
                        <tr><td colspan="3" class="table-empty">Memuat data...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </section>
</div>

<!-- Modal Perpanjang Paket (Renew) -->
<div id="modalRenew" class="modal-overlay" aria-hidden="true">
    <div class="modal-dialog" role="dialog" aria-modal="true" aria-labelledby="modalRenewTitle">
        <div class="modal-header">
            <div>
                <h2 id="modalRenewTitle">Perpanjang Membership</h2>
                <p class="muted">Pilih paket langganan dan metode pembayaran</p>
            </div>
            <button id="btnCloseModalRenew" class="icon-button modal-close-btn" type="button" aria-label="Tutup modal">
                <svg class="icon" aria-hidden="true" focusable="false"><use href="#icon-close"/></svg>
            </button>
        </div>
        <form id="formRenew" class="modal-form">
            <div class="form-group">
                <label for="renewPackage">Pilihan Paket Langganan</label>
                <select id="renewPackage" name="id_paket" required>
                    <!-- Dimuat dinamis via JS -->
                </select>
            </div>

            <div class="form-group">
                <label for="renewPaymentMethod">Metode Pembayaran</label>
                <select id="renewPaymentMethod" name="metode_pembayaran" required>
                    <option value="tunai">Tunai (Cash)</option>
                    <option value="qris">QRIS</option>
                    <option value="transfer">Transfer Bank</option>
                </select>
            </div>

            <div class="modal-actions">
                <button id="btnCancelModalRenew" class="btn-cancel" type="button">Batal</button>
                <button class="btn-submit" type="submit">Proses Perpanjangan</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Edit Profil Member -->
<div id="modalEdit" class="modal-overlay" aria-hidden="true">
    <div class="modal-dialog" role="dialog" aria-modal="true" aria-labelledby="modalEditTitle">
        <div class="modal-header">
            <div>
                <h2 id="modalEditTitle">Edit Data Member</h2>
                <p class="muted">Perbarui data profil member</p>
            </div>
            <button id="btnCloseModalEdit" class="icon-button modal-close-btn" type="button" aria-label="Tutup modal">
                <svg class="icon" aria-hidden="true" focusable="false"><use href="#icon-close"/></svg>
            </button>
        </div>
        <form id="formEdit" class="modal-form">
            <div class="form-group">
                <label for="editName">Nama Lengkap</label>
                <input id="editName" name="name" type="text" required>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="editPhone">Nomor WhatsApp / HP</label>
                    <input id="editPhone" name="phone" type="tel" required>
                </div>
                <div class="form-group">
                    <label for="editGender">Jenis Kelamin</label>
                    <select id="editGender" name="gender" required>
                        <option value="pria">Pria</option>
                        <option value="wanita">Wanita</option>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="editStatus">Status Membership</label>
                    <select id="editStatus" name="membership_status" required>
                        <option value="active">Aktif</option>
                        <option value="expired">Kedaluwarsa</option>
                        <option value="inactive">Tidak Aktif</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="editExpiresAt">Tanggal Kedaluwarsa</label>
                    <input id="editExpiresAt" name="membership_expires_at" type="date">
                </div>
            </div>

            <div class="form-group">
                <label for="editEmergency">Kontak Darurat (Opsional)</label>
                <input id="editEmergency" name="emergency_contact" type="tel" placeholder="Nomor kerabat">
            </div>

            <div class="form-group">
                <label for="editAddress">Alamat Lengkap</label>
                <textarea id="editAddress" name="address" rows="2" placeholder="Alamat domisili"></textarea>
            </div>

            <div class="modal-actions">
                <button id="btnCancelModalEdit" class="btn-cancel" type="button">Batal</button>
                <button class="btn-submit" type="submit">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Konfirmasi Hapus Member -->
<div id="modalDeleteMember" class="modal-overlay" aria-hidden="true">
    <div class="modal-dialog" role="dialog" aria-modal="true" aria-labelledby="modalDeleteTitle" style="max-width: 440px;">
        <div class="modal-header">
            <div style="display: flex; align-items: center; gap: 10px;">
                <div style="width: 36px; height: 36px; border-radius: 10px; background: rgba(239, 68, 68, 0.15); display: flex; align-items: center; justify-content: center; color: #ef4444; flex-shrink: 0;">
                    <svg class="icon" aria-hidden="true" focusable="false" style="width: 20px; height: 20px;"><use href="#icon-warning"/></svg>
                </div>
                <div>
                    <h2 id="modalDeleteTitle" style="font-size: 16px; margin: 0; color: #fff;">Hapus Member</h2>
                    <p class="muted" style="margin: 2px 0 0; font-size: 12px;">Tindakan permanen</p>
                </div>
            </div>
            <button id="btnCloseModalDelete" class="icon-button modal-close-btn" type="button" aria-label="Tutup modal">
                <svg class="icon" aria-hidden="true" focusable="false"><use href="#icon-close"/></svg>
            </button>
        </div>
        <div style="padding: 18px 24px;">
            <p style="margin: 0 0 10px; color: #f3f4f6; font-size: 14px; line-height: 1.5;">
                Apakah Anda yakin ingin menghapus member <strong id="deleteMemberName" style="color: #ff9142;">-</strong>?
            </p>
            <p style="margin: 0; color: #9ca3af; font-size: 12px; line-height: 1.5;">
                Semua data langganan, transaksi, dan riwayat presensi member ini akan dihapus secara permanen dari database.
            </p>
        </div>
        <div class="modal-actions" style="display: flex; justify-content: flex-end; gap: 10px; padding: 14px 24px; border-top: 1px solid rgba(255, 255, 255, 0.08);">
            <button id="btnCancelDelete" class="btn-cancel" type="button">Batal</button>
            <button id="btnConfirmDelete" class="btn-submit" type="button" style="background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); border-color: #f87171; box-shadow: 0 4px 14px rgba(239, 68, 68, 0.4);">Ya, Hapus</button>
        </div>
    </div>
</div>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';

