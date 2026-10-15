<?php
// frontend/admin/members/index.php
$page = 'members';
$title = 'Member';
$pageSubtitle = 'Kelola informasi operasional gym.';

ob_start();
?>
<section class="stats-grid" aria-label="Ringkasan member">
    <article class="stat-card">
        <div class="stat-icon orange" aria-hidden="true">♙</div>
        <p>Total Member</p>
        <strong id="statTotalMembers">0</strong>
        <small>Semua terdaftar</small>
    </article>
    <article class="stat-card">
        <div class="stat-icon blue" aria-hidden="true" style="color: #60a5fa;">♂</div>
        <p>Member Pria</p>
        <strong id="statMaleMembers">0</strong>
        <small>Laki-laki</small>
    </article>
    <article class="stat-card">
        <div class="stat-icon pink" aria-hidden="true" style="color: #f472b6;">♀</div>
        <p>Member Wanita</p>
        <strong id="statFemaleMembers">0</strong>
        <small>Perempuan</small>
    </article>
    <article class="stat-card">
        <div class="stat-icon green" aria-hidden="true">☎</div>
        <p>Kontak Darurat</p>
        <strong id="statEmergencyMembers">0</strong>
        <small>Kontak terdaftar</small>
    </article>
</section>

<section class="panel page-panel">
    <div class="panel-heading">
        <div>
            <h2>Daftar Member</h2>
            <p class="muted">Data identitas dan profil member gym.</p>
        </div>
        <button id="btnOpenAddMember" class="primary-button" type="button">＋ Tambah Member</button>
    </div>

    <div class="toolbar">
        <label class="visually-hidden" for="memberSearch">Cari member</label>
        <input id="memberSearch" class="search-input" type="search" placeholder="Cari nama, kode member, atau no. HP…">
        <label class="visually-hidden" for="memberGenderFilter">Filter jenis kelamin</label>
        <select id="memberGenderFilter" class="filter-select">
            <option value="all">Semua Jenis Kelamin</option>
            <option value="pria">Pria</option>
            <option value="wanita">Wanita</option>
        </select>
        <button class="soft-button demo-action" type="button">⇩ Export</button>
    </div>

    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th scope="col">MEMBER</th>
                    <th scope="col">TELEPON</th>
                    <th scope="col">GENDER</th>
                    <th scope="col">KONTAK DARURAT</th>
                    <th scope="col">TERDAFTAR</th>
                    <th scope="col" class="col-aksi">AKSI</th>
                </tr>
            </thead>
            <tbody id="memberTableBody">
                <tr>
                    <td colspan="6" style="text-align: center; color: var(--color-muted, #888); padding: 2rem;">Memuat data member…</td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="table-footer">
        <span id="memberCount" aria-live="polite">Memuat data…</span>
        <div id="memberPagination" class="pagination" role="navigation" aria-label="Navigasi Halaman Member"></div>
    </div>
</section>

<!-- Modal Tambah Member Baru -->
<div id="modalAddMember" class="modal-overlay" aria-hidden="true">
    <div class="modal-dialog" role="dialog" aria-modal="true" aria-labelledby="modalTitle">
        <div class="modal-header">
            <div>
                <h2 id="modalTitle">Tambah Member Baru</h2>
                <p class="muted">Isi formulir untuk mendaftarkan member baru</p>
            </div>
            <button id="btnCloseModal" class="icon-button modal-close-btn" type="button" aria-label="Tutup modal">
                <svg class="icon" aria-hidden="true" focusable="false"><use href="#icon-close"/></svg>
            </button>
        </div>
        <form id="formAddMember" class="modal-form">
            <div style="margin-bottom: 14px; padding: 10px 14px; font-size: 12px; background: rgba(59, 130, 246, 0.08); border: 1px solid rgba(59, 130, 246, 0.25); border-radius: 10px; color: #93c5fd;">
                <strong>Akses Login Member:</strong> Kode member dibuat otomatis dan password default awal: <code style="color: #fed7aa; font-weight: bold;">12345678</code>. Member masuk menggunakan Kode Member &amp; Password.
            </div>

            <div class="form-group">
                <label for="memberName">Nama Lengkap</label>
                <input id="memberName" name="nama_lengkap" type="text" placeholder="Contoh: Budi Pratama" required>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="memberPhone">Nomor WhatsApp / HP</label>
                    <input id="memberPhone" name="nomor_telepon" type="tel" placeholder="08xxxxxxxxxx" required>
                </div>
                <div class="form-group">
                    <label for="memberGender">Jenis Kelamin</label>
                    <select id="memberGender" name="jenis_kelamin" required>
                        <option value="pria">Pria</option>
                        <option value="wanita">Wanita</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label for="memberEmergency">Kontak Darurat (Opsional)</label>
                <input id="memberEmergency" name="kontak_darurat" type="tel" placeholder="Nomor kerabat / keluarga">
            </div>

            <!-- Aktivasi Paket Membership Sekalian -->
            <div class="form-group">
                <label for="memberPackage">Aktivasi Paket Membership (Opsional)</label>
                <select id="memberPackage" name="id_paket">
                    <option value="">-- Tanpa Paket (Registrasi Saja) --</option>
                </select>
            </div>

            <div id="packageActivationFields" style="display: none;">
                <div class="form-row">
                    <div class="form-group">
                        <label for="memberStartDate">Tanggal Mulai Aktif</label>
                        <input id="memberStartDate" name="tanggal_mulai" type="date">
                    </div>
                    <div class="form-group">
                        <label for="memberPaymentMethod">Metode Pembayaran</label>
                        <select id="memberPaymentMethod" name="metode_pembayaran">
                            <option value="tunai">Tunai</option>
                            <option value="qris">QRIS</option>
                            <option value="transfer">Transfer Bank</option>
                        </select>
                    </div>
                </div>
                <div id="packageInfoBadge" style="margin-top: 4px; padding: 10px 14px; font-size: 12px; background: rgba(255, 122, 26, 0.08); border: 1px solid rgba(255, 122, 26, 0.25); border-radius: 10px; color: #fed7aa;" hidden></div>
            </div>

            <div class="modal-actions">
                <button id="btnCancelModal" class="btn-cancel" type="button">Batal</button>
                <button class="btn-submit" type="submit">Simpan Member</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Detail Member (Show Modal - Hanya Data Orang & Presensi) -->
<div id="modalDetailMember" class="modal-overlay" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="dialog" aria-modal="true" aria-labelledby="modalDetailName">
        <div class="modal-header">
            <div class="modal-detail-profile-header">
                <div class="avatar peach large-avatar" id="mdAvatar">M</div>
                <div>
                    <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                        <h2 id="modalDetailName">Nama Member</h2>
                        <span id="mdBadge" class="status blue-bg">Pria</span>
                    </div>
                    <p class="muted" style="margin-top: 4px; font-size: 12px;">
                        <span id="mdCode" class="member-code-tag" style="color: #ff9142; font-family: monospace; font-weight: 600;">GYM-...</span> · 
                        Bergabung: <span id="mdCreatedAt">-</span>
                    </p>
                </div>
            </div>
            <button id="btnCloseModalDetail" class="icon-button modal-close-btn" type="button" aria-label="Tutup modal detail">
                <svg class="icon" aria-hidden="true" focusable="false"><use href="#icon-close"/></svg>
            </button>
        </div>



        <!-- Profil & Ringkasan Grid -->
        <div class="modal-detail-grid">
            <div class="modal-info-box">
                <h3 style="font-size: 11px; color: #9ca3af; text-transform: uppercase; margin: 0 0 10px; letter-spacing: 0.5px;">Data Pribadi &amp; Kontak</h3>
                <div class="info-list">
                    <div class="info-item">
                        <span class="info-label">WhatsApp / HP</span>
                        <span class="info-value" id="mdPhone">-</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Jenis Kelamin</span>
                        <span class="info-value" id="mdGender">-</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Kontak Darurat</span>
                        <span class="info-value" id="mdEmergency">-</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Alamat Lengkap</span>
                        <span class="info-value" id="mdAddress">-</span>
                    </div>
                </div>
            </div>

            <div class="modal-info-box">
                <h3 style="font-size: 11px; color: #9ca3af; text-transform: uppercase; margin: 0 0 10px; letter-spacing: 0.5px;">Informasi Akun &amp; Membership</h3>
                <div class="info-list">
                    <div class="info-item">
                        <span class="info-label">Kode Member</span>
                        <span class="info-value" id="mdInfoCode" style="color: #ff9142; font-family: monospace;">-</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Password Default</span>
                        <span class="info-value" style="font-family: monospace; color: #fed7aa; font-weight: 600;">12345678</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Status &amp; Paket</span>
                        <span class="info-value" id="mdInfoPackage">-</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Masa Berlaku</span>
                        <span class="info-value" id="mdInfoExpires">-</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Tanggal Terdaftar</span>
                        <span class="info-value" id="mdInfoJoined">-</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Total Presensi</span>
                        <span class="info-value" id="mdTotalCheckins" style="color: #4ade80;">0 kali</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Presensi Terakhir</span>
                        <span class="info-value" id="mdLastCheckin">-</span>
                    </div>
                </div>
            </div>

            <div class="modal-info-box modal-qr-box">
                <h3 style="font-size: 11px; color: #9ca3af; text-transform: uppercase; margin: 0 0 10px; letter-spacing: 0.5px;">QR Code Presensi</h3>
                <div class="modal-qr-container" style="display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 4px 0;">
                    <div class="qr-canvas-holder" id="mdQrHolder"></div>
                    <small class="muted" style="margin-top: 8px; font-size: 11px; text-align: center;">Scan untuk absensi kedatangan</small>
                </div>
            </div>
        </div>

        <!-- Bagian Riwayat Presensi Kehadiran Member -->
        <div style="margin-top: 16px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                <h3 style="font-size: 12px; color: #e5e7eb; margin: 0; font-weight: 600;">Riwayat Presensi (Check-in)</h3>
                <span class="muted" style="font-size: 11px;"><span id="mdCountAtt">0</span> kehadiran tercatat</span>
            </div>
            <div class="table-wrap" style="max-height: 180px; overflow-y: auto;">
                <table class="data-table" style="min-width: 100%; font-size: 11px;">
                    <thead>
                        <tr>
                            <th>TANGGAL</th>
                            <th>JAM CHECK-IN</th>
                        </tr>
                    </thead>
                    <tbody id="mdTableAttBody">
                        <tr><td colspan="2" class="table-empty">Belum ada riwayat presensi</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Edit Profil Member -->
<div id="modalEdit" class="modal-overlay" aria-hidden="true">
    <div class="modal-dialog" role="dialog" aria-modal="true" aria-labelledby="modalEditTitle">
        <div class="modal-header">
            <div>
                <h2 id="modalEditTitle">Edit Data Member</h2>
                <p class="muted">Perbarui data identitas profil member</p>
            </div>
            <button id="btnCloseModalEdit" class="icon-button modal-close-btn" type="button" aria-label="Tutup modal">
                <svg class="icon" aria-hidden="true" focusable="false"><use href="#icon-close"/></svg>
            </button>
        </div>
        <form id="formEdit" class="modal-form">
            <input type="hidden" id="editMemberId" name="member_id">
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

            <div class="form-group">
                <label for="editEmergency">Kontak Darurat (Opsional)</label>
                <input id="editEmergency" name="emergency_contact" type="tel" placeholder="Nomor kerabat">
            </div>

            <div class="modal-actions">
                <button id="btnCancelModalEdit" class="btn-cancel" type="button">Batal</button>
                <button class="btn-submit" type="submit">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';

