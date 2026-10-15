<?php
// frontend/user/settings/index.php
$page = 'settings';
$title = 'Pengaturan Akun';
$pageEyebrow = 'PENGATURAN · ' . strtoupper(date('d M Y'));
$pageHeading = 'Pengaturan Akun';
$pageSubtitle = 'Lihat informasi profil Anda dan kelola kata sandi login.';

ob_start();
?>
<!-- Header Identitas Singkat Pengguna -->
<section class="panel user-identity-card" aria-label="Identitas Akun">
    <div class="user-identity-inner">
        <div class="user-identity-avatar" id="identityAvatar">M</div>
        <div class="user-identity-info">
            <div class="identity-name-row">
                <h2 id="identityName">Gym Member</h2>
                <span class="status green-bg" id="identityStatusBadge">Member Aktif</span>
            </div>
            <p class="identity-meta">
                <span id="identityMemberCode" class="identity-code-chip">GYM-0001</span>
                <span class="meta-dot">·</span>
                <span id="identityEmail">user@gym.com</span>
                <span class="meta-dot">·</span>
                <span id="identityPackage">Paket Langganan</span>
            </p>
        </div>
    </div>
</section>

<!-- Settings Grid (2 Kolom: Profil & Keamanan) -->
<section class="settings-grid">
    <!-- PANEL 1: DATA PROFIL PRIBADI -->
    <article class="panel settings-panel">
        <div class="panel-heading">
            <div>
                <h2>Informasi Profil</h2>
                <p class="muted">Biodata dan kontak darurat Anda.</p>
            </div>
        </div>

        <div class="settings-form">
            <div class="readonly-note">
                <span class="tip-icon" aria-hidden="true">ℹ️</span>
                <p>Data diri hanya dapat diubah oleh admin. Hubungi resepsionis jika ada data yang perlu diperbarui.</p>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="inputName">Nama Lengkap</label>
                    <input type="text" id="inputName" name="name" readonly disabled class="input-disabled">
                </div>
                <div class="form-group">
                    <label for="inputMemberCode">Kode Member <small class="field-note">Sistem</small></label>
                    <input type="text" id="inputMemberCode" readonly disabled class="input-disabled" title="Kode member digenerate otomatis">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="inputPhone">Nomor WhatsApp / HP</label>
                    <input type="tel" id="inputPhone" name="phone" readonly disabled class="input-disabled">
                </div>
                <div class="form-group">
                    <label for="inputGender">Jenis Kelamin</label>
                    <input type="text" id="inputGender" name="gender" readonly disabled class="input-disabled">
                </div>
            </div>

            <div class="form-group">
                <label for="inputEmergency">Kontak Darurat (Keluarga / Kerabat)</label>
                <input type="text" id="inputEmergency" name="emergency_contact" readonly disabled class="input-disabled">
                <small class="form-hint">Dihubungi gym jika terjadi kondisi darurat saat berolahraga.</small>
            </div>
        </div>
    </article>

    <!-- PANEL 2: GANTI KATA SANDI -->
    <article class="panel settings-panel">
        <div class="panel-heading">
            <div>
                <h2>Keamanan & Kata Sandi</h2>
                <p class="muted">Ganti kata sandi untuk melindungi akses akun Anda.</p>
            </div>
        </div>

        <form id="passwordForm" class="settings-form">
            <div class="form-group">
                <label for="inputCurrentPassword">Kata Sandi Saat Ini <span class="required">*</span></label>
                <div class="password-input-wrap">
                    <input type="password" id="inputCurrentPassword" name="current_password" required placeholder="Masukkan kata sandi lama Anda">
                    <button type="button" class="btn-toggle-pw" data-target="inputCurrentPassword" aria-label="Lihat kata sandi">
                        <svg class="icon" aria-hidden="true" focusable="false"><use href="#icon-chevron"/></svg>
                    </button>
                </div>
            </div>

            <div class="form-group">
                <label for="inputNewPassword">Kata Sandi Baru <span class="required">*</span></label>
                <div class="password-input-wrap">
                    <input type="password" id="inputNewPassword" name="new_password" required minlength="6" placeholder="Minimal 6 karakter">
                    <button type="button" class="btn-toggle-pw" data-target="inputNewPassword" aria-label="Lihat kata sandi">
                        <svg class="icon" aria-hidden="true" focusable="false"><use href="#icon-chevron"/></svg>
                    </button>
                </div>
                <small class="form-hint">Gunakan kombinasi huruf dan angka agar lebih aman.</small>
            </div>

            <div class="form-group">
                <label for="inputConfirmPassword">Konfirmasi Kata Sandi Baru <span class="required">*</span></label>
                <div class="password-input-wrap">
                    <input type="password" id="inputConfirmPassword" name="confirm_password" required minlength="6" placeholder="Ulangi kata sandi baru">
                    <button type="button" class="btn-toggle-pw" data-target="inputConfirmPassword" aria-label="Lihat kata sandi">
                        <svg class="icon" aria-hidden="true" focusable="false"><use href="#icon-chevron"/></svg>
                    </button>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" id="btnChangePassword" class="primary-button">
                    <svg class="icon icon-sm" aria-hidden="true" focusable="false"><use href="#icon-check"/></svg>
                    Perbarui Kata Sandi
                </button>
            </div>
        </form>

        <div class="security-card-tips">
            <div class="tip-icon" aria-hidden="true">🔒</div>
            <div>
                <b>Keamanan Akun</b>
                <p>Jangan bagikan kata sandi Anda kepada orang lain. Petugas resepsionis hanya memerlukan QR Code atau Kode Member untuk absensi.</p>
            </div>
        </div>
    </article>
</section>

<!-- Modal Status Pembaruan Kata Sandi (Berhasil / Gagal) -->
<div id="modalPasswordResult" class="modal-overlay" aria-hidden="true">
    <div class="modal-dialog modal-result-dialog" role="dialog" aria-modal="true" aria-labelledby="modalResultTitle">
        <div class="modal-result-content">
            <div class="result-icon-badge success-badge" id="modalResultIconBadge" aria-hidden="true">
                <svg class="icon modal-icon" id="modalResultIcon" focusable="false"><use href="#icon-check"/></svg>
            </div>
            <h3 id="modalResultTitle" class="modal-result-title">Kata Sandi Berhasil Diperbarui!</h3>
            <p id="modalResultMessage" class="modal-result-desc">Kata sandi akun Anda telah berhasil diperbarui.</p>
        </div>
        <div class="modal-result-actions">
            <button id="btnModalResultClose" class="primary-button btn-full" type="button">OK, Selesai</button>
        </div>
    </div>
</div>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';
