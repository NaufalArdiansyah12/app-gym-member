<?php
// frontend/user/kehadiran/index.php
$page = 'kehadiran';
$title = 'Riwayat Kehadiran';
$pageEyebrow = 'RIWAYAT KEHADIRAN · ' . strtoupper(date('d M Y'));
$pageHeading = 'Riwayat Seluruh Kehadiran';
$pageSubtitle = 'Daftar lengkap sesi latihan gym yang telah Anda ikuti.';

ob_start();
?>
<article class="panel">
    <div class="panel-heading">
        <div>
            <h2>Riwayat Seluruh Kehadiran</h2>
            <p class="muted">Daftar lengkap sesi latihan gym yang telah Anda ikuti.</p>
        </div>
        <span class="status green-bg" id="totalAttendanceBadge">0 Total Hadir</span>
    </div>
    <div class="table-wrap" role="region" aria-label="Riwayat Kehadiran Lengkap" tabindex="0">
        <table>
            <thead>
                <tr>
                    <th scope="col">TANGGAL & HARI</th>
                    <th scope="col">JAM MASUK</th>
                    <th scope="col">SESI / ZONA</th>
                    <th scope="col">CATATAN TARGET</th>
                    <th scope="col">STATUS</th>
                </tr>
            </thead>
            <tbody id="attendanceFullTableBody">
                <!-- Rendered by JS -->
            </tbody>
        </table>
    </div>
</article>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';
