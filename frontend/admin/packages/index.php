<?php
// frontend/admin/packages/index.php
$page = 'packages';
$title = 'Paket Langganan';
$pageSubtitle = 'Kelola paket membership dan promosi aktif gym.';

ob_start();
?>
<section class="panel page-panel">
    <div class="panel-heading">
        <div>
            <h2>Daftar Paket Langganan</h2>
            <p class="muted">Data paket membership dan fitur yang tersedia.</p>
        </div>
    </div>

    <div class="toolbar package-toolbar">
        <label class="visually-hidden" for="packageSearch">Cari paket</label>
        <input id="packageSearch" class="search-input" type="search" placeholder="Cari nama paket atau durasi…">
        <label class="visually-hidden" for="packageStatus">Filter status paket</label>
        <!-- <select id="packageStatus" class="filter-select">
            <option value="all">Semua status</option>
            <option value="active">Aktif</option>
            <option value="promo">Promo</option>
            <option value="draft">Draft</option>
            <option value="inactive">Nonaktif</option>
        </select> -->
        <button class="soft-button demo-action" type="button">⇩ Export</button>
    </div>

    <!-- <div class="package-groups" aria-label="Kelompok paket">
        <button class="glass-package-card active" type="button" data-duration="all">
            <span class="group-no">All</span>
            <span class="group-label">Semua</span>
        </button>
        <button class="glass-package-card" type="button" data-duration="1 bulan">
            <span class="group-no">01</span>
            <span class="group-label">1 Bulan</span>
        </button>
        <button class="glass-package-card" type="button" data-duration="3 bulan">
            <span class="group-no">03</span>
            <span class="group-label">3 Bulan</span>
        </button>
        <button class="glass-package-card" type="button" data-duration="6 bulan">
            <span class="group-no">06</span>
            <span class="group-label">6 Bulan</span>
        </button>
    </div> -->

    <div class="package-card-list" aria-label="Daftar paket langganan">
        <article class="package-card-item active" data-status="active" data-duration="1 bulan">
            <div class="package-card-head">
                <div>
                    <h3>Starter Flex</h3>
                    <small>Membership dasar</small>
                </div>
                <span class="status success-bg">Aktif</span>
            </div>
            <div class="package-card-body">
                <div class="package-field">
                    <span class="field-label">Harga</span>
                    <strong>Rp 150.000</strong>
                </div>
                <div class="package-field">
                    <span class="field-label">Durasi</span>
                    <strong>1 Bulan</strong>
                </div>
                <div class="package-field wide">
                    <span class="field-label">Fitur</span>
                    <strong>Gym & akses kelas</strong>
                </div>
            </div>
            <div class="package-card-actions">
                <button class="package-edit-button" type="button">✎ Edit Paket</button>
            </div>
        </article>

        <article class="package-card-item promo" data-status="promo" data-duration="3 bulan">
            <div class="package-card-head">
                <div>
                    <h3>Prime 3M</h3>
                    <small>Fleksibel untuk pemula</small>
                </div>
                <span class="status warning-bg">Promo</span>
            </div>
            <div class="package-card-body">
                <div class="package-field">
                    <span class="field-label">Harga</span>
                    <strong>Rp 400.000</strong>
                </div>
                <div class="package-field">
                    <span class="field-label">Durasi</span>
                    <strong>3 Bulan</strong>
                </div>
                <div class="package-field wide">
                    <span class="field-label">Fitur</span>
                    <strong>Gym, locker, & 1 sesi personal</strong>
                </div>
            </div>
            <div class="package-card-actions">
                <button class="package-edit-button" type="button">✎ Edit Paket</button>
            </div>
        </article>

        <article class="package-card-item active" data-status="active" data-duration="6 bulan">
            <div class="package-card-head">
                <div>
                    <h3>Power 6M</h3>
                    <small>Untuk target jangka panjang</small>
                </div>
                <span class="status success-bg">Aktif</span>
            </div>
            <div class="package-card-body">
                <div class="package-field">
                    <span class="field-label">Harga</span>
                    <strong>Rp 750.000</strong>
                </div>
                <div class="package-field">
                    <span class="field-label">Durasi</span>
                    <strong>6 Bulan</strong>
                </div>
                <div class="package-field wide">
                    <span class="field-label">Fitur</span>
                    <strong>Gym + coach & diet ringan</strong>
                </div>
            </div>
            <div class="package-card-actions">
                <button class="package-edit-button" type="button">✎ Edit Paket</button>
            </div>
        </article>
    </div>

    <div class="table-footer">
        <span id="packageCount" aria-live="polite">Menampilkan 3 paket</span>
        <button class="soft-button" type="button" disabled aria-label="Halaman 1">‹ &nbsp; 1 &nbsp; ›</button>
    </div>
</section>

<div id="modalAddPackage" class="modal-overlay" aria-hidden="true">
    <div class="modal-dialog" role="dialog" aria-modal="true" aria-labelledby="modalPackageTitle">
        <div class="modal-header">
            <div>
                <h2 id="modalPackageTitle">Tambah Paket Baru</h2>
                <p class="muted">Isi detail paket yang akan dijual</p>
            </div>
            <button id="btnClosePackageModal" class="icon-button modal-close-btn" type="button" aria-label="Tutup modal">
                <svg class="icon" aria-hidden="true" focusable="false"><use href="#icon-close"/></svg>
            </button>
        </div>

        <form id="formAddPackage" class="modal-form">
            <div class="form-group">
                <label for="packageName">Nama Paket</label>
                <input id="packageName" name="nama_paket" type="text" placeholder="Contoh: Gold 6M" required>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="packagePrice">Harga</label>
                    <input id="packagePrice" name="harga" type="number" min="0" placeholder="750000" required>
                </div>
                <div class="form-group">
                    <label for="packageDuration">Durasi</label>
                    <select id="packageDuration" name="durasi" required>
                        <option value="1 bulan">1 Bulan</option>
                        <option value="3 bulan" selected>3 Bulan</option>
                        <option value="6 bulan">6 Bulan</option>
                        <option value="12 bulan">12 Bulan</option>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="packageStatusSelect">Status</label>
                    <select id="packageStatusSelect" name="status" required>
                        <option value="active" selected>Aktif</option>
                        <option value="promo">Promo</option>
                        <option value="draft">Draft</option>
                        <option value="inactive">Nonaktif</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="packageDiscount">Diskon (%)</label>
                    <input id="packageDiscount" name="diskon" type="number" min="0" max="100" value="0" placeholder="0">
                </div>
            </div>

            <div class="form-group">
                <label for="packageFeatures">Fitur Paket</label>
                <textarea id="packageFeatures" name="fitur" rows="3" placeholder="Contoh: Gym, locker, dan 1 sesi personal" required></textarea>
            </div>

            <div class="modal-actions">
                <button id="btnCancelPackageModal" class="btn-cancel" type="button">Batal</button>
                <button class="btn-submit" type="submit">Simpan Paket</button>
            </div>
        </form>
    </div>
</div>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';
