<?php
// frontend/admin/packages/index.php
$page = 'packages';
$title = 'Paket Langganan';
$pageSubtitle = 'Kelola paket membership gym, tarif harga, dan benefit fasilitas.';

ob_start();
?>
<!-- Toolbar di luar card daftar paket -->
<div class="packages-toolbar">
    <div>
        <h2>Daftar Paket Langganan</h2>
        <p class="muted">Kelola pilihan durasi membership gym (1 Bulan, 2 Bulan, 3 Bulan, 6 Bulan, dan lainnya).</p>
    </div>
    <button id="btnOpenAddPackage" class="primary-button" type="button">
        Tambah Paket Baru
    </button>
</div>

<section class="panel page-panel">
    <!-- Cards Container: Populated from Database -->
    <div id="packageGrid" class="package-pro-grid" aria-label="Daftar paket membership gym">
        <div class="package-loading-state">
            <div class="loading-spinner"></div>
            <p>Memuat data paket langganan...</p>
        </div>
    </div>
</section>

<!-- Modal Tambah / Edit Paket Langganan -->
<div id="modalPackage" class="modal-overlay" aria-hidden="true">
    <div class="modal-dialog modal-dialog-package" role="dialog" aria-modal="true" aria-labelledby="modalPackageTitle">
        <div class="modal-header">
            <div>
                <h2 id="modalPackageTitle">Tambah Paket Langganan Baru</h2>
                <p class="muted" id="modalPackageSubtitle">Atur nama paket, durasi hari, harga, dan benefit fasilitas.</p>
            </div>
            <button id="btnCloseModal" class="icon-button modal-close-btn" type="button" aria-label="Tutup modal">
                <svg class="icon" aria-hidden="true" focusable="false"><use href="#icon-close"/></svg>
            </button>
        </div>

        <form id="formPackage" class="modal-form">
            <input type="hidden" id="packageId" name="id">

            <div class="form-group">
                <label for="packageName">Nama Paket</label>
                <input id="packageName" name="nama_paket" type="text" placeholder="Contoh: Paket 2 Bulan" required>
            </div>

            <!-- Pilihan Cepat Durasi Paket -->
            <div class="form-group">
                <label>Pilihan Durasi Paket</label>
                <div class="duration-presets" id="durationPresets">
                    <button type="button" class="preset-btn" data-months="1" data-days="30">1 Bulan (30 Hari)</button>
                    <button type="button" class="preset-btn active" data-months="2" data-days="60">2 Bulan (60 Hari)</button>
                    <button type="button" class="preset-btn" data-months="3" data-days="90">3 Bulan (90 Hari)</button>
                    <button type="button" class="preset-btn" data-months="6" data-days="180">6 Bulan (180 Hari)</button>
                    <button type="button" class="preset-btn" data-months="12" data-days="360">12 Bulan (360 Hari)</button>
                    <button type="button" class="preset-btn" data-months="0" data-days="custom">Kustom</button>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="packagePrice">Harga (Rp)</label>
                    <input id="packagePrice" name="harga" type="number" min="0" step="1000" placeholder="Contoh: 280000" required>
                    <small class="field-help">Nominal biaya langganan paket</small>
                </div>
                <div class="form-group">
                    <label for="packageDuration">Durasi (Hari)</label>
                    <input id="packageDuration" name="durasi_hari" type="number" min="1" placeholder="60" value="60" required>
                    <small class="field-help" id="durationHint">Dihitung otomatis: 60 hari = 2 bulan</small>
                </div>
            </div>

            <div class="form-group">
                <label for="packageBenefits">Benefit & Fasilitas Paket</label>
                <textarea id="packageBenefits" name="benefit" rows="4" placeholder="Tuliskan tiap fasilitas/benefit per baris, contoh:&#10;Akses seluruh fasilitas gym&#10;Free locker room & shower bersih&#10;1x Sesi konsultasi instruktur" required></textarea>
                <small class="field-help">Tips: Pisahkan setiap keuntungan dengan baris baru (Enter)</small>
            </div>

            <div class="form-group">
                <label for="packageStatus">Status Ketersediaan</label>
                <select id="packageStatus" name="tersedia" required>
                    <option value="1">Aktif (Tersedia Dijual)</option>
                    <option value="0">Nonaktif (Sembunyikan)</option>
                </select>
            </div>

            <div class="modal-actions">
                <button id="btnCancelModal" class="btn-cancel" type="button">Batal</button>
                <button id="btnSubmitPackage" class="btn-submit" type="submit">Tambah Paket Baru</button>
            </div>
        </form>
    </div>
</div>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';
