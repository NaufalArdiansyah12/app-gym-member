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
        <!-- <button class="soft-button demo-action" type="button">⇩ Export</button> -->
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
    <div class="modal-dialog modal-form-lg" role="dialog" aria-modal="true" aria-labelledby="modalTitle">
        <div class="modal-header">
            <div>
                <h2 id="modalTitle">Tambah Member Baru</h2>
                <p class="muted">Isi identitas KTP dan foto verifikasi member</p>
            </div>
            <button id="btnCloseModal" class="icon-button modal-close-btn" type="button" aria-label="Tutup modal">
                <svg class="icon" aria-hidden="true" focusable="false"><use href="#icon-close"/></svg>
            </button>
        </div>
        <form id="formAddMember" class="modal-form">
            <div class="member-auth-callout">
                <strong>Akses Login Member:</strong> Kode member dan password dibuat otomatis. Password bisa dilihat &amp; direset dari tombol aksi di tabel.
            </div>

            <!-- SEKSI 1: FOTO VERIFIKASI MEMBER -->
            <div class="form-section-card">
                <div class="form-section-header">
                    <span class="section-badge">1</span>
                    <div>
                        <h3 class="section-title">Foto Verifikasi Member</h3>
                        <p class="section-desc">Foto setengah badan untuk verifikasi identitas. Ambil lewat kamera atau upload berkas.</p>
                    </div>
                </div>

                <div class="photo-capture-layout">
                    <!-- Frame Portrait Setengah Badan -->
                    <div class="photo-portrait-wrapper" id="photoWrapper">
                        <!-- State 1: Placeholder kosong -->
                        <div class="photo-placeholder" id="photoPlaceholder">
                            <div class="placeholder-icon">
                                <svg width="44" height="44" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                    <circle cx="12" cy="7" r="4"/>
                                    <path d="M5.5 21a8.38 8.38 0 0 1 13 0"/>
                                </svg>
                            </div>
                            <span class="placeholder-text">Belum ada foto</span>
                            <small class="placeholder-hint">Format setengah badan</small>
                        </div>

                        <!-- State 2: Live Video Stream Kamera -->
                        <div class="camera-stream-container" id="cameraStreamContainer" style="display: none;">
                            <video id="cameraVideo" autoplay playsinline muted></video>
                            <div class="camera-guide-overlay">
                                <div class="guide-face-oval"></div>
                                <div class="guide-torso-line"></div>
                            </div>
                            <div class="camera-live-badge">● LIVE</div>
                        </div>

                        <!-- State 3: Preview Gambar Hasil Jepretan / Upload -->
                        <img id="photoPreview" class="photo-result-img" alt="Foto Verifikasi Member" style="display: none;">
                        <canvas id="photoCanvas" style="display: none;"></canvas>
                    </div>

                    <!-- Tombol Aksi Kontrol Kamera & Foto -->
                    <div class="photo-controls">
                        <!-- Kontrol Awal (Kamera mati & belum ada foto) -->
                        <div id="initialPhotoControls" class="control-group-buttons">
                            <button type="button" id="btnStartCamera" class="btn-camera-action primary-camera">
                                <svg class="camera-btn-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M14.5 4h-5L7 7H4a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-3l-2.5-3z"/>
                                    <circle cx="12" cy="13" r="3"/>
                                </svg>
                                Buka Kamera
                            </button>
                            <label class="btn-camera-action soft-camera" for="memberPhotoFile">
                                <svg class="camera-btn-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                                    <polyline points="17 8 12 3 7 8"/>
                                    <line x1="12" y1="3" x2="12" y2="15"/>
                                </svg>
                                Upload Berkas
                            </label>
                            <input type="file" id="memberPhotoFile" accept="image/*" style="display: none;">
                        </div>

                        <!-- Kontrol Saat Kamera Live -->
                        <div id="activeCameraControls" class="control-group-buttons" style="display: none;">
                            <button type="button" id="btnCapturePhoto" class="btn-camera-action capture-camera">
                                📸 Jepret Foto
                            </button>
                            <button type="button" id="btnStopCamera" class="btn-camera-action cancel-camera">
                                ✕ Tutup Kamera
                            </button>
                        </div>

                        <!-- Kontrol Setelah Foto Diambil / Diunggah -->
                        <div id="capturedPhotoControls" class="control-group-buttons" style="display: none;">
                            <button type="button" id="btnRetakePhoto" class="btn-camera-action primary-camera">
                                🔄 Ambil Ulang
                            </button>
                            <button type="button" id="btnRemovePhoto" class="btn-camera-action danger-camera">
                                🗑 Hapus Foto
                            </button>
                        </div>

                        <p class="photo-guide-note">
                            💡 Posisikan kepala &amp; bahu tegak di dalam bingkai. Foto dipakai untuk verifikasi identitas.
                        </p>
                        <input type="hidden" id="memberPhotoData" name="foto">
                    </div>
                </div>
            </div>

            <!-- SEKSI 2: IDENTITAS SESUAI KTP -->
            <div class="form-section-card">
                <div class="form-section-header">
                    <span class="section-badge">2</span>
                    <div>
                        <h3 class="section-title">Identitas Sesuai KTP</h3>
                        <p class="section-desc">Lengkapi data kependudukan member sesuai Kartu Tanda Penduduk (KTP).</p>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="memberNik">Nomor Induk Kependudukan (NIK)</label>
                        <input id="memberNik" name="nik" type="text" maxlength="16" inputmode="numeric" pattern="\d{16}" autocomplete="off" placeholder="16 digit angka NIK KTP">
                        <small class="field-hint">Wajib 16 digit angka</small>
                    </div>
                    <div class="form-group">
                        <label for="memberName">Nama Lengkap Sesuai KTP <span class="required-star">*</span></label>
                        <input id="memberName" name="nama_lengkap" type="text" placeholder="Contoh: Budi Pratama" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="memberBirthPlace">Tempat Lahir</label>
                        <input id="memberBirthPlace" name="tempat_lahir" type="text" placeholder="Kota / Kabupaten lahir">
                    </div>
                    <div class="form-group">
                        <label for="memberBirthDate">Tanggal Lahir</label>
                        <input id="memberBirthDate" name="tanggal_lahir" type="date">
                    </div>
                </div>

                <div class="form-group">
                    <label for="memberGender">Jenis Kelamin <span class="required-star">*</span></label>
                    <select id="memberGender" name="jenis_kelamin" required>
                        <option value="pria">Pria</option>
                        <option value="wanita">Wanita</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="memberAddress">Alamat Lengkap KTP</label>
                    <textarea id="memberAddress" name="alamat" rows="2" placeholder="Nama Jalan, RT/RW, Kelurahan, Kecamatan, Kota/Kabupaten"></textarea>
                </div>
            </div>

            <!-- SEKSI 3: KONTAK & PAKET MEMBERSHIP -->
            <div class="form-section-card">
                <div class="form-section-header">
                    <span class="section-badge">3</span>
                    <div>
                        <h3 class="section-title">Kontak &amp; Paket Langganan</h3>
                        <p class="section-desc">Nomor kontak aktif dan pilihan aktivasi paket membership awal.</p>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="memberPhone">Nomor WhatsApp / HP <span class="required-star">*</span></label>
                        <input id="memberPhone" name="nomor_telepon" type="tel" maxlength="13" inputmode="numeric" autocomplete="off" placeholder="08xxxxxxxxxx" required>
                        <small class="field-hint">Hanya angka, maksimal 13 digit</small>
                    </div>
                    <div class="form-group">
                        <label for="memberEmergency">Kontak Darurat (Opsional)</label>
                        <input id="memberEmergency" name="kontak_darurat" type="tel" maxlength="13" inputmode="numeric" autocomplete="off" placeholder="Nomor kerabat / keluarga">
                    </div>
                </div>

                <!-- Aktivasi Paket Membership Sekalian -->
                <div class="form-group">
                    <label for="memberPackage">Aktivasi Paket Membership</label>
                    <select id="memberPackage" name="id_paket" required>
                        <option value="">-- Pilih Paket --</option>
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
            <div class="modal-info-box modal-photo-box">
                <h3 style="font-size: 11px; color: #9ca3af; text-transform: uppercase; margin: 0 0 10px; letter-spacing: 0.5px;">Foto Verifikasi</h3>
                <div class="detail-photo-holder">
                    <img id="mdPhoto" class="detail-photo-img" alt="Foto Verifikasi Member" style="display: none;">
                    <div id="mdPhotoEmpty" class="detail-photo-empty">
                        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="7" r="4"/>
                            <path d="M5.5 21a8.38 8.38 0 0 1 13 0"/>
                        </svg>
                        <small>Belum ada foto</small>
                    </div>
                </div>
            </div>

            <div class="modal-info-box">
                <h3 style="font-size: 11px; color: #9ca3af; text-transform: uppercase; margin: 0 0 10px; letter-spacing: 0.5px;">Data Identitas KTP</h3>
                <div class="info-list">
                    <div class="info-item">
                        <span class="info-label">NIK (KTP)</span>
                        <span class="info-value" id="mdNik" style="font-family: monospace; color: #ff9142;">-</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Tempat, Tgl Lahir</span>
                        <span class="info-value" id="mdBirth">-</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Jenis Kelamin</span>
                        <span class="info-value" id="mdGender">-</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Alamat KTP</span>
                        <span class="info-value" id="mdAddress">-</span>
                    </div>
                </div>
            </div>

            <div class="modal-info-box">
                <h3 style="font-size: 11px; color: #9ca3af; text-transform: uppercase; margin: 0 0 10px; letter-spacing: 0.5px;">Kontak &amp; Informasi Akun</h3>
                <div class="info-list">
                    <div class="info-item">
                        <span class="info-label">WhatsApp / HP</span>
                        <span class="info-value" id="mdPhone">-</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Kontak Darurat</span>
                        <span class="info-value" id="mdEmergency">-</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Kode Member</span>
                        <span class="info-value" id="mdInfoCode" style="color: #ff9142; font-family: monospace;">-</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Password</span>
                        <span class="info-value" style="display: flex; align-items: center; gap: 8px;">
                            <code id="mdPassword" style="font-family: monospace; color: #fed7aa; font-weight: 600;">-</code>
                            <button type="button" id="mdBtnTogglePassword" class="icon-button" title="Lihat/sembunyikan password" aria-label="Lihat/sembunyikan password" style="width: 26px; height: 26px;">
                                <svg class="icon" aria-hidden="true" focusable="false" style="width: 13px; height: 13px;"><use href="#icon-eye"/></svg>
                            </button>
                            <button type="button" id="mdBtnShowPasswordModal" class="icon-button" title="Kelola password member" aria-label="Kelola password member" style="width: 26px; height: 26px;">
                                <svg class="icon" aria-hidden="true" focusable="false" style="width: 13px; height: 13px;"><use href="#icon-refresh"/></svg>
                            </button>
                        </span>
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
    <div class="modal-dialog modal-form-lg" role="dialog" aria-modal="true" aria-labelledby="modalEditTitle">
        <div class="modal-header">
            <div>
                <h2 id="modalEditTitle">Edit Data Member</h2>
                <p class="muted">Perbarui data identitas KTP dan profil member</p>
            </div>
            <button id="btnCloseModalEdit" class="icon-button modal-close-btn" type="button" aria-label="Tutup modal">
                <svg class="icon" aria-hidden="true" focusable="false"><use href="#icon-close"/></svg>
            </button>
        </div>
        <form id="formEdit" class="modal-form">
            <input type="hidden" id="editMemberId" name="member_id">

            <!-- FOTO MEMBER SETENGAH BADAN -->
            <div class="form-section-card">
                <div class="form-section-header">
                    <span class="section-badge">📷</span>
                    <div>
                        <h3 class="section-title">Foto Verifikasi Member</h3>
                        <p class="section-desc">Ganti atau tambahkan foto verifikasi member. Ambil lewat kamera atau upload berkas.</p>
                    </div>
                </div>

                <div class="photo-capture-layout">
                    <div class="photo-portrait-wrapper" id="editPhotoWrapper">
                        <div class="photo-placeholder" id="editPhotoPlaceholder">
                            <div class="placeholder-icon">
                                <svg width="44" height="44" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                    <circle cx="12" cy="7" r="4"/>
                                    <path d="M5.5 21a8.38 8.38 0 0 1 13 0"/>
                                </svg>
                            </div>
                            <span class="placeholder-text">Belum ada foto</span>
                            <small class="placeholder-hint">Format setengah badan</small>
                        </div>

                        <div class="camera-stream-container" id="editCameraStreamContainer" style="display: none;">
                            <video id="editCameraVideo" autoplay playsinline muted></video>
                            <div class="camera-guide-overlay">
                                <div class="guide-face-oval"></div>
                                <div class="guide-torso-line"></div>
                            </div>
                            <div class="camera-live-badge">● LIVE</div>
                        </div>

                        <img id="editPhotoPreview" class="photo-result-img" alt="Foto Verifikasi Member" style="display: none;">
                        <canvas id="editPhotoCanvas" style="display: none;"></canvas>
                    </div>

                    <div class="photo-controls">
                        <div id="editInitialPhotoControls" class="control-group-buttons">
                            <button type="button" id="btnEditStartCamera" class="btn-camera-action primary-camera">
                                <svg class="camera-btn-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M14.5 4h-5L7 7H4a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-3l-2.5-3z"/>
                                    <circle cx="12" cy="13" r="3"/>
                                </svg>
                                Buka Kamera
                            </button>
                            <label class="btn-camera-action soft-camera" for="editMemberPhotoFile">
                                <svg class="camera-btn-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                                    <polyline points="17 8 12 3 7 8"/>
                                    <line x1="12" y1="3" x2="12" y2="15"/>
                                </svg>
                                Upload Berkas
                            </label>
                            <input type="file" id="editMemberPhotoFile" accept="image/*" style="display: none;">
                        </div>

                        <div id="editActiveCameraControls" class="control-group-buttons" style="display: none;">
                            <button type="button" id="btnEditCapturePhoto" class="btn-camera-action capture-camera">
                                📸 Jepret Foto
                            </button>
                            <button type="button" id="btnEditStopCamera" class="btn-camera-action cancel-camera">
                                ✕ Tutup Kamera
                            </button>
                        </div>

                        <div id="editCapturedPhotoControls" class="control-group-buttons" style="display: none;">
                            <button type="button" id="btnEditRetakePhoto" class="btn-camera-action primary-camera">
                                🔄 Ambil Ulang
                            </button>
                            <button type="button" id="btnEditRemovePhoto" class="btn-camera-action danger-camera">
                                🗑 Hapus Foto
                            </button>
                        </div>

                        <p class="photo-guide-note">
                            💡 Posisikan kepala &amp; bahu tegak di dalam bingkai. Foto dipakai untuk verifikasi identitas.
                        </p>
                        <input type="hidden" id="editMemberPhotoData" name="foto">
                    </div>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="editNik">NIK KTP (16 Digit)</label>
                    <input id="editNik" name="nik" type="text" maxlength="16" inputmode="numeric" pattern="\d{16}" autocomplete="off" placeholder="16 digit angka NIK">
                </div>
                <div class="form-group">
                    <label for="editName">Nama Lengkap</label>
                    <input id="editName" name="name" type="text" required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="editBirthPlace">Tempat Lahir</label>
                    <input id="editBirthPlace" name="tempat_lahir" type="text" placeholder="Kota lahir">
                </div>
                <div class="form-group">
                    <label for="editBirthDate">Tanggal Lahir</label>
                    <input id="editBirthDate" name="tanggal_lahir" type="date">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="editPhone">Nomor WhatsApp / HP</label>
                    <input id="editPhone" name="phone" type="tel" maxlength="13" inputmode="numeric" autocomplete="off" required>
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
                <input id="editEmergency" name="emergency_contact" type="tel" maxlength="13" inputmode="numeric" autocomplete="off" placeholder="Nomor kerabat">
            </div>

            <div class="form-group">
                <label for="editAddress">Alamat Lengkap KTP</label>
                <textarea id="editAddress" name="address" rows="2" placeholder="Nama Jalan, RT/RW, Kelurahan, Kecamatan, Kota"></textarea>
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

<!-- Modal Password Member (Lihat & Reset) -->
<div id="modalMemberPassword" class="modal-overlay" aria-hidden="true">
    <div class="modal-dialog" role="dialog" aria-modal="true" aria-labelledby="modalPasswordTitle" style="max-width: 440px;">
        <div class="modal-header">
            <div>
                <h2 id="modalPasswordTitle" style="font-size: 16px; margin: 0; color: #fff;">Password Member</h2>
                <p class="muted" style="margin: 2px 0 0; font-size: 12px;">Bagikan ke member untuk login aplikasi</p>
            </div>
            <button id="btnCloseModalPassword" class="icon-button modal-close-btn" type="button" aria-label="Tutup modal">
                <svg class="icon" aria-hidden="true" focusable="false"><use href="#icon-close"/></svg>
            </button>
        </div>
        <div style="padding: 18px 24px;">
            <div class="info-list" style="gap: 12px;">
                <div class="info-item">
                    <span class="info-label">Nama Member</span>
                    <span class="info-value" id="pwMemberName">-</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Kode Member</span>
                    <span class="info-value" id="pwMemberCode" style="color: #ff9142; font-family: monospace;">-</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Password</span>
                    <span class="info-value" style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                        <code id="pwMemberValue" style="font-family: monospace; font-size: 16px; letter-spacing: 1px; color: #fed7aa; font-weight: 700;">-</code>
                        <button type="button" id="btnTogglePw" class="icon-button" title="Lihat/sembunyikan password" aria-label="Lihat/sembunyikan password" style="width: 28px; height: 28px;">
                            <svg class="icon" aria-hidden="true" focusable="false" style="width: 14px; height: 14px;"><use href="#icon-eye"/></svg>
                        </button>
                        <button type="button" id="btnCopyPw" class="icon-button" title="Salin password" aria-label="Salin password" style="width: auto; height: 28px; padding: 0 10px; font-size: 11px;">Salin</button>
                    </span>
                </div>
            </div>
            <p class="muted" id="pwHint" style="margin: 12px 0 0; font-size: 11px; line-height: 1.5;">
                Member dapat login memakai Kode Member atau Email dengan password di atas.
            </p>
        </div>
        <div class="modal-actions" style="display: flex; justify-content: flex-end; gap: 10px; padding: 14px 24px; border-top: 1px solid rgba(255, 255, 255, 0.08);">
            <button id="btnResetPassword" class="btn-cancel" type="button" style="display: flex; align-items: center; gap: 6px;">
                <svg class="icon" aria-hidden="true" focusable="false" style="width: 13px; height: 13px;"><use href="#icon-refresh"/></svg>
                Reset Password
            </button>
            <button id="btnDonePassword" class="btn-submit" type="button">Selesai</button>
        </div>
    </div>
</div>

<!-- Modal Status Feedback (Berhasil / Gagal) -->
<div id="modalStatusResult" class="modal-overlay" aria-hidden="true">
    <div class="modal-dialog modal-result-dialog" role="dialog" aria-modal="true" aria-labelledby="modalResultTitle">
        <div class="modal-result-content">
            <div class="result-icon-badge success-badge" id="modalResultIconBadge" aria-hidden="true">
                <svg class="icon modal-icon" id="modalResultIcon" focusable="false"><use href="#icon-check"/></svg>
            </div>
            <h3 id="modalResultTitle" class="modal-result-title">Berhasil!</h3>
            <p id="modalResultMessage" class="modal-result-desc">Pesan status operasi.</p>
        </div>
        <div class="modal-result-actions">
            <button id="btnModalResultClose" class="primary-button btn-full" type="button">OK, Mengerti</button>
        </div>
    </div>
</div>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';

