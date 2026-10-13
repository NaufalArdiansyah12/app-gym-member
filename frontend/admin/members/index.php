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
        <strong>1,248</strong>
        <small>Semua terdaftar</small>
    </article>
    <article class="stat-card">
        <div class="stat-icon green" aria-hidden="true">✓</div>
        <p>Aktif</p>
        <strong>986</strong>
        <small>Membership berjalan</small>
    </article>
    <article class="stat-card">
        <div class="stat-icon yellow" aria-hidden="true">!</div>
        <p>Akan Berakhir</p>
        <strong>42</strong>
        <small>≤ 7 hari</small>
    </article>
    <article class="stat-card">
        <div class="stat-icon purple" aria-hidden="true">◷</div>
        <p>Kedaluwarsa</p>
        <strong>220</strong>
        <small>Perlu perpanjangan</small>
    </article>
</section>

<section class="panel page-panel">
    <div class="panel-heading">
        <div>
            <h2>Daftar Member</h2>
            <p class="muted">Data profil dan masa aktif member.</p>
        </div>
        <button id="btnOpenAddMember" class="primary-button" type="button">＋ Tambah Member</button>
    </div>

    <div class="toolbar">
        <label class="visually-hidden" for="memberSearch">Cari member</label>
        <input id="memberSearch" class="search-input" type="search" placeholder="Cari nama atau kode member…">
        <label class="visually-hidden" for="memberStatus">Filter status member</label>
        <select id="memberStatus" class="filter-select">
            <option value="all">Semua status</option>
            <option value="active">Aktif</option>
            <option value="expiring">Akan berakhir</option>
            <option value="expired">Kedaluwarsa</option>
        </select>
        <button class="soft-button demo-action" type="button">⇩ Export</button>
    </div>

    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th scope="col">MEMBER</th>
                    <th scope="col">TELEPON</th>
                    <th scope="col">PAKET</th>
                    <th scope="col">BERAKHIR</th>
                    <th scope="col">STATUS</th>
                    <th scope="col"><span class="visually-hidden">Aksi</span></th>
                </tr>
            </thead>
            <tbody>
                <tr data-status="active">
                    <td><b>Andi Rachman</b><small>GYM-2601</small></td>
                    <td>0812 3456 7801</td>
                    <td>3 Bulan</td>
                    <td>12 Jan 2027</td>
                    <td><span class="status success-bg">Aktif</span></td>
                    <td>•••</td>
                </tr>
                <tr data-status="expiring">
                    <td><b>Siti Lestari</b><small>GYM-2488</small></td>
                    <td>0813 5678 9021</td>
                    <td>1 Bulan</td>
                    <td>16 Okt 2026</td>
                    <td><span class="status warning-bg">2 hari lagi</span></td>
                    <td>•••</td>
                </tr>
                <tr data-status="active">
                    <td><b>Budi Pratama</b><small>GYM-2390</small></td>
                    <td>0821 1122 3344</td>
                    <td>6 Bulan</td>
                    <td>28 Feb 2027</td>
                    <td><span class="status success-bg">Aktif</span></td>
                    <td>•••</td>
                </tr>
                <tr data-status="expired">
                    <td><b>Nadia Anjani</b><small>GYM-2274</small></td>
                    <td>0852 7700 1100</td>
                    <td>1 Bulan</td>
                    <td>02 Okt 2026</td>
                    <td><span class="status danger-bg">Kedaluwarsa</span></td>
                    <td>•••</td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="table-footer">
        <span id="memberCount" aria-live="polite">Menampilkan 4 data demo</span>
        <button class="soft-button" type="button" disabled aria-label="Halaman 1">‹ &nbsp; 1 &nbsp; 2 &nbsp; ›</button>
    </div>
</section>

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

            <div class="form-row">
                <div class="form-group">
                    <label for="memberPackage">Pilihan Paket</label>
                    <select id="memberPackage" name="id_paket" required>
                        <option value="1">1 Bulan — Rp 150.000</option>
                        <option value="2" selected>3 Bulan — Rp 400.000</option>
                        <option value="3">6 Bulan — Rp 750.000</option>
                        <option value="4">12 Bulan — Rp 1.300.000</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="memberStartDate">Tanggal Mulai</label>
                    <input id="memberStartDate" name="tanggal_mulai" type="date" value="<?= date('Y-m-d') ?>" required>
                </div>
            </div>

            <div class="form-group">
                <label for="memberEmergency">Kontak Darurat (Opsional)</label>
                <input id="memberEmergency" name="kontak_darurat" type="tel" placeholder="Nomor kerabat / keluarga">
            </div>

            <div class="modal-actions">
                <button id="btnCancelModal" class="btn-cancel" type="button">Batal</button>
                <button class="btn-submit" type="submit">Simpan Member</button>
            </div>
        </form>
    </div>
</div>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';
