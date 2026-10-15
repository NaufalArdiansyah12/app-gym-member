<?php
// backend/controllers/admin/ReportsController.php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/AdminController.php';

class LaporanController {
    /**
     * Mengambil laporan ringkasan keuangan, paket, transaksi, dan presensi dengan filter tanggal
     * Endpoint: GET /api/admin/reports atau GET /api/admin/laporan
     */
    public static function index(): void {
        AdminController::requireAdmin();
        $db = getDB();

        $startDate = trim($_GET['start_date'] ?? date('Y-m-01'));
        $endDate = trim($_GET['end_date'] ?? date('Y-m-d'));

        // Pastikan format tanggal valid (YYYY-MM-DD)
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $startDate)) {
            $startDate = date('Y-m-01');
        }
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $endDate)) {
            $endDate = date('Y-m-d');
        }

        // 1. Ringkasan Eksekutif dalam periode
        $txStmt = $db->prepare("
            SELECT 
                COUNT(*) as count,
                COALESCE(SUM(jumlah_bayar), 0) as total
            FROM transaksi
            WHERE DATE(dibayar_pada) BETWEEN ? AND ?
        ");
        $txStmt->execute([$startDate, $endDate]);
        $txSummary = $txStmt->fetch();
        $periodRevenue = (float)($txSummary['total'] ?? 0);
        $periodTxCount = (int)($txSummary['count'] ?? 0);

        // Total presensi dalam periode
        $attStmt = $db->prepare("
            SELECT COUNT(*) 
            FROM presensi 
            WHERE DATE(waktu_presensi) BETWEEN ? AND ?
        ");
        $attStmt->execute([$startDate, $endDate]);
        $periodCheckins = (int)$attStmt->fetchColumn();

        // Member baru terdaftar dalam periode
        $userStmt = $db->prepare("
            SELECT COUNT(*) 
            FROM users 
            WHERE role = 'user' 
              AND DATE(created_at) BETWEEN ? AND ?
        ");
        $userStmt->execute([$startDate, $endDate]);
        $newMembers = (int)$userStmt->fetchColumn();

        // 2. Transaksi terperinci dalam periode
        $detailStmt = $db->prepare("
            SELECT 
                t.id,
                t.jumlah_bayar,
                t.metode_pembayaran,
                t.dibayar_pada,
                u.id as user_id,
                u.name as member_name,
                u.member_code,
                u.phone as member_phone,
                p.nama_paket,
                p.durasi_hari
            FROM transaksi t
            JOIN langganan l ON t.id_langganan = l.id
            JOIN users u ON l.id_user = u.id
            JOIN paket p ON l.id_paket = p.id
            WHERE DATE(t.dibayar_pada) BETWEEN ? AND ?
            ORDER BY t.dibayar_pada DESC, t.id DESC
            LIMIT 200
        ");
        $detailStmt->execute([$startDate, $endDate]);
        $txRows = $detailStmt->fetchAll();

        $transactions = [];
        foreach ($txRows as $row) {
            $amount = (float)$row['jumlah_bayar'];
            $invoiceCode = 'TRX-' . date('Ymd', strtotime($row['dibayar_pada'])) . '-' . str_pad((string)$row['id'], 4, '0', STR_PAD_LEFT);
            $transactions[] = [
                'id'                  => (int)$row['id'],
                'invoice_code'        => $invoiceCode,
                'member_name'         => $row['member_name'],
                'member_code'         => $row['member_code'],
                'member_phone'        => $row['member_phone'] ?? '-',
                'nama_paket'          => $row['nama_paket'],
                'durasi_hari'         => (int)$row['durasi_hari'],
                'jumlah_bayar'        => $amount,
                'jumlah_bayar_format' => 'Rp ' . number_format($amount, 0, ',', '.'),
                'metode_pembayaran'   => strtoupper($row['metode_pembayaran']),
                'dibayar_pada'        => $row['dibayar_pada'],
                'waktu_format'        => date('d M Y H:i', strtotime($row['dibayar_pada'])),
            ];
        }

        // 3. Distribusi metode pembayaran dalam periode
        $methStmt = $db->prepare("
            SELECT 
                metode_pembayaran, 
                COUNT(*) as transaksi_count, 
                COALESCE(SUM(jumlah_bayar), 0) as total_nominal 
            FROM transaksi 
            WHERE DATE(dibayar_pada) BETWEEN ? AND ?
            GROUP BY metode_pembayaran
        ");
        $methStmt->execute([$startDate, $endDate]);
        $methodStats = $methStmt->fetchAll();

        // Hitung total tunai, non-tunai, dan rata-rata per transaksi
        $cashTotal = 0.0;
        $nonCashTotal = 0.0;
        foreach ($methodStats as $ms) {
            $mName = strtolower($ms['metode_pembayaran'] ?? '');
            $mNominal = (float)($ms['total_nominal'] ?? 0);
            if ($mName === 'tunai') {
                $cashTotal += $mNominal;
            } else {
                $nonCashTotal += $mNominal;
            }
        }
        $avgTx = $periodTxCount > 0 ? (float)round($periodRevenue / $periodTxCount) : 0.0;

        // 4. Performa paket dalam periode
        $pkgStmt = $db->prepare("
            SELECT 
                p.id,
                p.nama_paket,
                p.durasi_hari,
                CAST(p.harga AS DOUBLE) as harga,
                COUNT(t.id) as total_terjual,
                COALESCE(SUM(t.jumlah_bayar), 0) as total_pendapatan
            FROM paket p
            LEFT JOIN langganan l ON l.id_paket = p.id AND DATE(l.tanggal_mulai) BETWEEN ? AND ?
            LEFT JOIN transaksi t ON t.id_langganan = l.id
            GROUP BY p.id
            ORDER BY total_pendapatan DESC, total_terjual DESC
        ");
        $pkgStmt->execute([$startDate, $endDate]);
        $packageStats = $pkgStmt->fetchAll();

        foreach ($packageStats as &$pkg) {
            $pkg['id'] = (int)$pkg['id'];
            $pkg['durasi_hari'] = (int)$pkg['durasi_hari'];
            $pkg['harga'] = (float)$pkg['harga'];
            $pkg['harga_format'] = 'Rp ' . number_format($pkg['harga'], 0, ',', '.');
            $pkg['total_terjual'] = (int)$pkg['total_terjual'];
            $pkg['total_pendapatan'] = (float)$pkg['total_pendapatan'];
            $pkg['total_pendapatan_format'] = 'Rp ' . number_format($pkg['total_pendapatan'], 0, ',', '.');
        }

        // 5. Riwayat presensi dalam periode
        $attLogStmt = $db->prepare("
            SELECT 
                pr.id,
                pr.waktu_presensi,
                u.name as member_name,
                u.member_code,
                u.gender
            FROM presensi pr
            JOIN users u ON pr.id_user = u.id
            WHERE DATE(pr.waktu_presensi) BETWEEN ? AND ?
            ORDER BY pr.waktu_presensi DESC
            LIMIT 150
        ");
        $attLogStmt->execute([$startDate, $endDate]);
        $attRows = $attLogStmt->fetchAll();

        $attendances = [];
        foreach ($attRows as $ar) {
            $wTime = strtotime($ar['waktu_presensi']);
            $attendances[] = [
                'id'             => (int)$ar['id'],
                'member_name'    => $ar['member_name'],
                'member_code'    => $ar['member_code'],
                'gender'         => strtolower($ar['gender'] ?? 'pria'),
                'gender_label'   => strtolower($ar['gender'] ?? '') === 'wanita' ? 'Wanita' : 'Pria',
                'waktu_presensi' => $ar['waktu_presensi'],
                'tanggal'        => date('d M Y', $wTime),
                'jam'            => date('H:i', $wTime) . ' WIB',
            ];
        }

        // 6. Angka kumulatif keseluruhan untuk kompatibilitas
        $allRevenue = (float)$db->query("SELECT COALESCE(SUM(jumlah_bayar), 0) FROM transaksi")->fetchColumn();
        $curMonthRev = (float)$db->query("
            SELECT COALESCE(SUM(jumlah_bayar), 0) 
            FROM transaksi 
            WHERE MONTH(dibayar_pada) = MONTH(CURDATE()) 
              AND YEAR(dibayar_pada) = YEAR(CURDATE())
        ")->fetchColumn();

        AdminController::json([
            'status' => 'success',
            'data'   => [
                'period' => [
                    'start_date' => $startDate,
                    'end_date'   => $endDate,
                ],
                'summary' => [
                    'revenue'                       => $periodRevenue,
                    'revenue_formatted'             => 'Rp ' . number_format($periodRevenue, 0, ',', '.'),
                    'transaction_count'             => $periodTxCount,
                    'average_transaction'           => $avgTx,
                    'average_transaction_formatted' => 'Rp ' . number_format($avgTx, 0, ',', '.'),
                    'cash_total'                    => $cashTotal,
                    'cash_total_formatted'          => 'Rp ' . number_format($cashTotal, 0, ',', '.'),
                    'non_cash_total'                => $nonCashTotal,
                    'non_cash_total_formatted'      => 'Rp ' . number_format($nonCashTotal, 0, ',', '.'),
                    'checkin_count'                 => $periodCheckins,
                    'new_members'                   => $newMembers,
                ],
                'transactions'            => $transactions,
                'method_stats'            => $methodStats,
                'packages'                => $packageStats,
                'attendances'             => $attendances,
                'total_revenue'           => $allRevenue,
                'total_revenue_formatted' => 'Rp ' . number_format($allRevenue, 0, ',', '.'),
                'month_revenue'           => $curMonthRev,
                'month_revenue_formatted' => 'Rp ' . number_format($curMonthRev, 0, ',', '.'),
            ],
        ]);
    }

    /**
     * Alias method reports() untuk kompatibilitas
     */
    public static function reports(): void {
        self::index();
    }
}

if (!class_exists('ReportsController', false)) {
    class_alias('LaporanController', 'ReportsController');
}