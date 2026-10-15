<?php
// frontend/admin/cashier/index.php
$page = 'cashier';
$title = 'Kasir & Transaksi';
$pageSubtitle = 'Proses pembayaran membership dan kelola transaksi kasir.';

ob_start();
?>
<section class="stats-grid" aria-label="Ringkasan kasir">
    <article class="stat-card">
        <div class="stat-icon orange" aria-hidden="true"><svg class="icon" focusable="false"><use href="#icon-receipt"/></svg></div>
        <p>Transaksi Hari Ini</p>
        <strong id="todayCount">0</strong>
        <small>Pembayaran berhasil</small>
    </article>
    <article class="stat-card">
        <div class="stat-icon green" aria-hidden="true"><svg class="icon" focusable="false"><use href="#icon-chart"/></svg></div>
        <p>Pendapatan Hari Ini</p>
        <strong id="todayRevenue">Rp 0</strong>
        <small class="positive">Pemasukan kasir</small>
    </article>
    <article class="stat-card">
        <div class="stat-icon yellow" aria-hidden="true"><svg class="icon" focusable="false"><use href="#icon-receipt"/></svg></div>
        <p>Pembayaran Tunai</p>
        <strong id="cashTotal">Rp 0</strong>
        <small>Uang fisik</small>
    </article>
    <article class="stat-card">
        <div class="stat-icon purple" aria-hidden="true"><svg class="icon" focusable="false"><use href="#icon-check"/></svg></div>
        <p>Non-Tunai / QRIS</p>
        <strong id="nonCashTotal">Rp 0</strong>
        <small class="positive">Transfer & QRIS</small>
    </article>
</section>

<section class="panel page-panel">
    <div class="panel-heading">
        <div>
            <h2>Riwayat Transaksi</h2>
            <p class="muted">Daftar transaksi kasir dan bukti pembayaran.</p>
        </div>
        <button id="btnOpenAddTransaction" class="primary-button" type="button"><svg class="icon icon-sm" aria-hidden="true" focusable="false"><use href="#icon-refresh"/></svg> Perpanjang Paket</button>
    </div>

    <div class="toolbar">
        <label class="visually-hidden" for="transactionSearch">Cari transaksi</label>
        <input id="transactionSearch" class="search-input" type="search" placeholder="Cari nama member, kode, atau paket…">
        <label class="visually-hidden" for="paymentFilter">Filter metode pembayaran</label>
        <select id="paymentFilter" class="filter-select">
            <option value="all">Semua metode</option>
            <option value="tunai">Tunai</option>
            <option value="qris">QRIS</option>
            <option value="transfer">Transfer</option>
        </select>
    </div>

    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th scope="col">NO. INVOICE</th>
                    <th scope="col">MEMBER</th>
                    <th scope="col">PAKET</th>
                    <th scope="col">TANGGAL</th>
                    <th scope="col">METODE</th>
                    <th scope="col">TOTAL</th>
                </tr>
            </thead>
            <tbody id="transactionRows">
                <tr>
                    <td colspan="6" class="empty-row">Memuat transaksi…</td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="table-footer">
        <span id="transactionCount" aria-live="polite">Memuat data transaksi…</span>
    </div>
</section>

<!-- Modal Liquid Glass: Transaksi Baru -->
<div id="modalAddTransaction" class="modal-overlay" aria-hidden="true">
    <div class="modal-dialog" role="dialog" aria-modal="true" aria-labelledby="modalTransactionTitle">
        <div class="modal-header">
            <div>
                <h2 id="modalTransactionTitle">Perpanjang Paket Member</h2>
                <p class="muted">Pilih member untuk memperpanjang masa aktif paket langganan</p>
            </div>
            <button id="btnCloseModal" class="icon-button modal-close-btn" type="button" aria-label="Tutup modal">
                <svg class="icon" aria-hidden="true" focusable="false"><use href="#icon-close"/></svg>
            </button>
        </div>
        <form id="formAddTransaction" class="modal-form">
            <!-- Layanan Kasir: Khusus Perpanjang Paket -->
            <input type="hidden" name="tipe_transaksi" id="transactionType" value="perpanjang">
            <div class="form-group">
                <div class="type-option is-active" style="cursor: default; pointer-events: none;">
                    <span class="type-icon" aria-hidden="true">
                        <svg class="icon" focusable="false"><use href="#icon-refresh"/></svg>
                    </span>
                    <span class="type-label">
                        <strong>Layanan Kasir: Perpanjang Paket</strong>
                        <small>Memperpanjang masa aktif paket langganan member</small>
                    </span>
                </div>
            </div>

            <div class="form-group member-search-group">
                <label for="memberSearchInput">Cari Member (Nama / Kode)</label>
                <div class="member-search-box">
                    <input type="search" id="memberSearchInput" class="search-input member-search-input" placeholder="Ketik nama atau kode member (misal: Siti / GYM002)…" autocomplete="off" required>
                    <input type="hidden" id="selectedUserId" name="user_id">
                    <div id="memberSearchResults" class="member-search-results" hidden></div>
                </div>
            </div>
            <div id="memberInfo" class="liquid-info-card" hidden></div>

            <div class="form-group custom-select-group">
                <label id="packageSelectLabel">Pilih Paket Langganan</label>
                <div class="custom-select-box" id="packageCustomSelect">
                    <input type="hidden" id="packageSelect" name="paket_id">
                    <button type="button" class="custom-select-trigger" id="packageSelectTrigger" aria-haspopup="listbox" aria-expanded="false" aria-labelledby="packageSelectLabel packageTriggerLabel">
                        <span class="trigger-label" id="packageTriggerLabel">— Pilih Paket Langganan —</span>
                        <svg class="icon trigger-chevron" aria-hidden="true" focusable="false"><use href="#icon-chevron"/></svg>
                    </button>
                    <div class="custom-select-menu" id="packageSelectMenu" role="listbox" hidden></div>
                </div>
            </div>
            <div id="packageInfo" class="liquid-info-card" hidden></div>

            <div class="form-row">
                <div class="form-group">
                    <label for="paymentMethod">Metode Pembayaran</label>
                    <select id="paymentMethod" name="metode_pembayaran" required>
                        <option value="tunai">Tunai</option>
                        <option value="qris">QRIS</option>
                        <option value="transfer">Transfer Bank</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="amountPaid">Nominal Pembayaran</label>
                    <input id="amountPaid" name="jumlah_bayar" type="number" min="0" step="1000" placeholder="0" required>
                </div>
            </div>

            <div class="payment-bill-card">
                <div class="bill-row">
                    <span>Tipe Transaksi</span>
                    <strong id="summaryTypeLabel" class="bill-type">Perpanjang Paket</strong>
                </div>
                <div class="bill-row">
                    <span>Estimasi Masa Berlaku</span>
                    <strong id="previewPeriod" class="bill-dates">-</strong>
                </div>
                <div class="bill-row">
                    <span>Total Tagihan Paket</span>
                    <strong id="packagePrice" class="bill-price">Rp 0</strong>
                </div>
                <div class="bill-row" id="changeRow" hidden>
                    <span>Estimasi Kembalian</span>
                    <strong id="estimatedChange" class="bill-change">Rp 0</strong>
                </div>
            </div>

            <p id="formMessage" class="form-message" role="status" aria-live="polite"></p>

            <div class="modal-actions">
                <button id="btnCancelModal" class="btn-cancel" type="button">Batal</button>
                <button id="btnSubmitTransaction" class="btn-submit" type="submit">Proses Perpanjangan</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Liquid Glass: Struk Pembayaran -->
<div id="modalReceipt" class="modal-overlay" aria-hidden="true">
    <div class="modal-dialog receipt-dialog" role="dialog" aria-modal="true" aria-labelledby="receiptTitle">
        <div class="modal-header">
            <div>
                <h2 id="receiptTitle">Struk Pembayaran</h2>
                <p class="muted" id="receiptSubtitle">KelolaGym · Bukti Transaksi Resmi</p>
            </div>
            <button id="btnCloseReceipt" class="icon-button modal-close-btn" type="button" aria-label="Tutup struk">
                <svg class="icon" aria-hidden="true" focusable="false"><use href="#icon-close"/></svg>
            </button>
        </div>

        <div id="receiptContent" class="receipt-body"></div>

        <div class="modal-actions receipt-actions">
            <button id="btnDoneReceipt" class="btn-submit" type="button">Selesai</button>
        </div>
    </div>
</div>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';
