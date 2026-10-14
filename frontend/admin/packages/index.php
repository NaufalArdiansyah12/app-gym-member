<?php
// frontend/admin/packages/index.php
$page = 'packages';
$title = 'Paket Langganan';
$pageSubtitle = 'Kelola paket membership gym, tarif harga, dan benefit fasilitas.';

ob_start();
?>
<section class="panel page-panel">
    <div class="panel-heading">
        <div>
            <h2>Daftar Paket Langganan</h2>
            <p class="muted">Kelola 3 pilihan durasi membership gym (1 Bulan, 3 Bulan, dan 6 Bulan).</p>
        </div>
    </div>

    <!-- Cards Container: Populated from Database -->
    <div id="packageGrid" class="package-pro-grid" aria-label="Daftar 3 paket membership gym">
        <div class="package-loading-state">
            <div class="loading-spinner"></div>
            <p>Memuat data paket langganan...</p>
        </div>
    </div>
</section>

<!-- Modal Edit Paket Langganan -->
<div id="modalEditPackage" class="modal-overlay" aria-hidden="true">
    <div class="modal-dialog modal-dialog-package" role="dialog" aria-modal="true" aria-labelledby="modalEditTitle">
        <div class="modal-header">
            <div>
                <h2 id="modalEditTitle">Edit Paket Langganan</h2>
                <p class="muted">Ubah nama paket, harga, durasi, dan daftar benefit fasilitas</p>
            </div>
            <button id="btnCloseModal" class="icon-button modal-close-btn" type="button" aria-label="Tutup modal">
                <svg class="icon" aria-hidden="true" focusable="false"><use href="#icon-close"/></svg>
            </button>
        </div>

        <form id="formEditPackage" class="modal-form">
            <input type="hidden" id="editPackageId" name="id">

            <div class="form-group">
                <label for="editPackageName">Nama Paket</label>
                <input id="editPackageName" name="nama_paket" type="text" placeholder="Contoh: Paket 1 Bulan" required>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="editPackagePrice">Harga (Rp)</label>
                    <input id="editPackagePrice" name="harga" type="number" min="0" step="1000" placeholder="150000" required>
                    <small class="field-help">Nominal biaya langganan paket</small>
                </div>
                <div class="form-group">
                    <label for="editPackageDuration">Durasi (Hari)</label>
                    <input id="editPackageDuration" name="durasi_hari" type="number" min="1" placeholder="30" required>
                    <small class="field-help">30 (1 bln), 90 (3 bln), 180 (6 bln)</small>
                </div>
            </div>

            <div class="form-group">
                <label for="editPackageBenefits">Benefit & Fasilitas Paket</label>
                <textarea id="editPackageBenefits" name="benefit" rows="5" placeholder="Tuliskan tiap fasilitas/benefit per baris, contoh:&#10;Akses seluruh fasilitas gym&#10;Free locker room & shower&#10;1x Sesi Personal Trainer" required></textarea>
                <small class="field-help">Tips: Pisahkan setiap keuntungan dengan baris baru (Enter)</small>
            </div>

            <div class="form-group">
                <label for="editPackageStatus">Status Ketersediaan</label>
                <select id="editPackageStatus" name="tersedia" required>
                    <option value="1">Aktif (Tersedia Dijual)</option>
                    <option value="0">Nonaktif (Sembunyikan)</option>
                </select>
            </div>

            <div class="modal-actions">
                <button id="btnCancelModal" class="btn-cancel" type="button">Batal</button>
                <button id="btnSubmitPackage" class="btn-submit" type="submit">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';
