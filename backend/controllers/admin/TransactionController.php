<?php
// backend/controllers/admin/TransactionController.php
require_once __DIR__ . '/AdminController.php';

class TransactionController {
    /**
     * Mengambil daftar transaksi pembayaran kasir
     * Endpoint: GET /api/admin/transactions
     */
    public static function index(): void {
        AdminController::requireAdmin();
        $db = getDB();

        $search = trim($_GET['search'] ?? $_GET['q'] ?? '');
        $method = trim($_GET['metode'] ?? $_GET['method'] ?? 'all');
        $date = trim($_GET['date'] ?? '');

        $sql = "
            SELECT 
                t.id,
                t.id_langganan,
                t.jumlah_bayar,
                t.metode_pembayaran,
                t.dibayar_pada,
                u.id as user_id,
                u.name as member_name,
                u.member_code,
                u.phone as member_phone,
                p.nama_paket,
                p.durasi_hari,
                l.tanggal_mulai,
                l.tanggal_selesai
            FROM transaksi t
            JOIN langganan l ON t.id_langganan = l.id
            JOIN users u ON l.id_user = u.id
            JOIN paket p ON l.id_paket = p.id
            WHERE 1=1
        ";

        $params = [];

        if ($search !== '') {
            $sql .= " AND (u.name LIKE ? OR u.member_code LIKE ?)";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
        }

        if ($method !== 'all' && in_array($method, ['tunai', 'transfer', 'qris'], true)) {
            $sql .= " AND t.metode_pembayaran = ?";
            $params[] = $method;
        }

        if ($date !== '') {
            $sql .= " AND DATE(t.dibayar_pada) = ?";
            $params[] = $date;
        }

        $sql .= " ORDER BY t.dibayar_pada DESC LIMIT 100";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        $transactions = [];
        $totalAmount = 0.0;
        foreach ($rows as $row) {
            $amount = (float)$row['jumlah_bayar'];
            $totalAmount += $amount;
            $transactions[] = [
                'id'                   => (int)$row['id'],
                'id_langganan'         => (int)$row['id_langganan'],
                'user_id'              => (int)$row['user_id'],
                'member_name'          => $row['member_name'],
                'member_code'          => $row['member_code'],
                'member_phone'         => $row['member_phone'],
                'nama_paket'           => $row['nama_paket'],
                'durasi_hari'          => (int)$row['durasi_hari'],
                'tanggal_mulai'        => $row['tanggal_mulai'],
                'tanggal_selesai'      => $row['tanggal_selesai'],
                'jumlah_bayar'         => $amount,
                'jumlah_bayar_format'  => 'Rp ' . number_format($amount, 0, ',', '.'),
                'metode_pembayaran'    => $row['metode_pembayaran'],
                'dibayar_pada'         => $row['dibayar_pada'],
                'dibayar_pada_format'  => date('d M Y H:i', strtotime($row['dibayar_pada'])),
            ];
        }

        // Summary metode pembayaran
        $methodStats = $db->query("
            SELECT metode_pembayaran, COUNT(*) as count, SUM(jumlah_bayar) as total 
            FROM transaksi 
            GROUP BY metode_pembayaran
        ")->fetchAll();

        AdminController::json([
            'status' => 'success',
            'data'   => [
                'transactions' => $transactions,
                'summary'      => [
                    'total_filtered_amount' => $totalAmount,
                    'total_formatted'       => 'Rp ' . number_format($totalAmount, 0, ',', '.'),
                    'method_breakdown'      => $methodStats,
                ],
            ],
        ]);
    }

    /**
     * Kasir: Buat transaksi baru untuk pembayaran paket langganan
     * Endpoint: POST /api/admin/transactions
     */
    public static function store(): void {
        AdminController::requireAdmin();
        $db = getDB();
        $input = AdminController::getInput();

        $memberCode = trim($input['member_code'] ?? $input['kode_member'] ?? '');
        $userId = (int)($input['user_id'] ?? $input['id_user'] ?? 0);
        $idPaket = (int)($input['id_paket'] ?? $input['package_id'] ?? 0);
        $paymentMethod = trim($input['metode_pembayaran'] ?? $input['payment_method'] ?? 'tunai');
        $startDate = trim($input['tanggal_mulai'] ?? date('Y-m-d'));

        if ($userId <= 0 && $memberCode === '') {
            AdminController::error('Pilih member terlebih dahulu', 422);
        }
        if ($idPaket <= 0) {
            AdminController::error('Pilih paket langganan terlebih dahulu', 422);
        }
        if (!in_array($paymentMethod, ['tunai', 'transfer', 'qris'], true)) {
            $paymentMethod = 'tunai';
        }

        // Cari user
        if ($userId > 0) {
            $userStmt = $db->prepare("SELECT * FROM users WHERE id = ? AND role = 'user' LIMIT 1");
            $userStmt->execute([$userId]);
        } else {
            $userStmt = $db->prepare("SELECT * FROM users WHERE member_code = ? AND role = 'user' LIMIT 1");
            $userStmt->execute([$memberCode]);
        }
        $user = $userStmt->fetch();

        if (!$user) {
            AdminController::error('Member tidak ditemukan', 404);
        }

        // Cari paket
        $pkgStmt = $db->prepare("SELECT * FROM paket WHERE id = ? LIMIT 1");
        $pkgStmt->execute([$idPaket]);
        $paket = $pkgStmt->fetch();

        if (!$paket) {
            AdminController::error('Paket langganan tidak ditemukan', 404);
        }

        $durasi = (int)$paket['durasi_hari'];
        $endDate = date('Y-m-d', strtotime("{$startDate} +{$durasi} days"));

        try {
            $db->beginTransaction();

            // 1. Simpan Langganan
            $subStmt = $db->prepare("
                INSERT INTO langganan (id_user, id_paket, tanggal_mulai, tanggal_selesai, status) 
                VALUES (?, ?, ?, ?, 'aktif')
            ");
            $subStmt->execute([$user['id'], $paket['id'], $startDate, $endDate]);
            $langgananId = (int)$db->lastInsertId();

            // 2. Simpan Transaksi
            $txStmt = $db->prepare("
                INSERT INTO transaksi (id_langganan, jumlah_bayar, metode_pembayaran, dibayar_pada) 
                VALUES (?, ?, ?, NOW())
            ");
            $txStmt->execute([$langgananId, $paket['harga'], $paymentMethod]);
            $transaksiId = (int)$db->lastInsertId();

            // 3. Update User Status & Masa Aktif
            $upStmt = $db->prepare("
                UPDATE users 
                SET membership_status = 'active', membership_expires_at = ? 
                WHERE id = ?
            ");
            $upStmt->execute([$endDate, $user['id']]);

            $db->commit();

            AdminController::json([
                'status'  => 'success',
                'message' => "Pembayaran transaksi #{$transaksiId} berhasil diproses untuk {$user['name']}!",
                'data'    => [
                    'transaction_id'       => $transaksiId,
                    'member_name'          => $user['name'],
                    'member_code'          => $user['member_code'],
                    'package_name'         => $paket['nama_paket'],
                    'amount'               => (float)$paket['harga'],
                    'amount_formatted'     => 'Rp ' . number_format($paket['harga'], 0, ',', '.'),
                    'payment_method'       => $paymentMethod,
                    'membership_expires_at'=> $endDate,
                ],
            ], 201);
        } catch (Exception $e) {
            $db->rollBack();
            AdminController::error('Gagal memproses transaksi: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Laporan ringkasan keuangan dan operasional
     * Endpoint: GET /api/admin/reports
     */
    public static function reports(): void {
        AdminController::requireAdmin();
        $db = getDB();

        // 1. Total pendapatan kumulatif
        $totalRevenue = (float)$db->query("SELECT COALESCE(SUM(jumlah_bayar), 0) FROM transaksi")->fetchColumn();

        // 2. Pendapatan bulan ini
        $monthRevenue = (float)$db->query("
            SELECT COALESCE(SUM(jumlah_bayar), 0) 
            FROM transaksi 
            WHERE MONTH(dibayar_pada) = MONTH(CURDATE()) 
              AND YEAR(dibayar_pada) = YEAR(CURDATE())
        ")->fetchColumn();

        // 3. Distribusi metode pembayaran
        $methodStats = $db->query("
            SELECT metode_pembayaran, COUNT(*) as transaksi_count, SUM(jumlah_bayar) as total_nominal 
            FROM transaksi 
            GROUP BY metode_pembayaran
        ")->fetchAll();

        // 4. Paket paling diminati
        $popularPackages = $db->query("
            SELECT p.nama_paket, COUNT(l.id) as total_langganan, SUM(t.jumlah_bayar) as total_nominal
            FROM paket p
            LEFT JOIN langganan l ON l.id_paket = p.id
            LEFT JOIN transaksi t ON t.id_langganan = l.id
            GROUP BY p.id
            ORDER BY total_langganan DESC
        ")->fetchAll();

        // 5. Tren 12 bulan terakhir
        $monthlyTrend = $db->query("
            SELECT 
                DATE_FORMAT(dibayar_pada, '%Y-%m') as periode,
                COUNT(*) as total_transaksi,
                SUM(jumlah_bayar) as total_pendapatan
            FROM transaksi
            WHERE dibayar_pada >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
            GROUP BY DATE_FORMAT(dibayar_pada, '%Y-%m')
            ORDER BY periode ASC
        ")->fetchAll();

        AdminController::json([
            'status' => 'success',
            'data'   => [
                'total_revenue'           => $totalRevenue,
                'total_revenue_formatted' => 'Rp ' . number_format($totalRevenue, 0, ',', '.'),
                'month_revenue'           => $monthRevenue,
                'month_revenue_formatted' => 'Rp ' . number_format($monthRevenue, 0, ',', '.'),
                'method_stats'            => $methodStats,
                'popular_packages'        => $popularPackages,
                'monthly_trend'           => $monthlyTrend,
            ],
        ]);
    }
}

