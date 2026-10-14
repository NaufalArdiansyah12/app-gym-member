<?php
// backend/controllers/user/UserMembershipController.php
require_once __DIR__ . '/UserController.php';

class UserMembershipController {
    /**
     * Mengambil informasi status dan riwayat paket membership
     * Endpoint: GET /api/user/membership
     */
    public static function index(): void {
        $db = getDB();
        UserController::autoExpire($db);
        $user = UserController::getAuthenticatedUser($db);
        $userId = (int)$user['id'];

        // 1. Ambil paket aktif
        $stmtActive = $db->prepare("
            SELECT l.*, p.nama_paket, p.durasi_hari, p.harga, t.metode_pembayaran, t.jumlah_bayar
            FROM langganan l
            JOIN paket p ON l.id_paket = p.id
            LEFT JOIN transaksi t ON t.id_langganan = l.id
            WHERE l.id_user = ?
            ORDER BY (l.status = 'aktif') DESC, l.id DESC
            LIMIT 1
        ");
        $stmtActive->execute([$userId]);
        $activeRow = $stmtActive->fetch();

        $activeData = [
            'package_name'   => 'Belum Ada Paket',
            'duration'       => '-',
            'days_remaining' => 0,
            'status'         => 'Nonaktif',
            'member_code'    => $user['member_code'] ?? 'GYM-0000',
            'start_date'     => null,
            'end_date'       => null,
        ];

        if ($activeRow) {
            $isAktif = $activeRow['status'] === 'aktif';
            $daysLeft = 0;
            if ($isAktif) {
                $daysLeft = max(0, (int)ceil((strtotime($activeRow['tanggal_selesai']) - strtotime(date('Y-m-d'))) / 86400));
            }

            $activeData = [
                'package_name'   => $activeRow['nama_paket'],
                'duration'       => "{$activeRow['durasi_hari']} Hari Durasi",
                'days_remaining' => $daysLeft,
                'status'         => $isAktif ? 'Aktif' : 'Kedaluwarsa',
                'member_code'    => $user['member_code'],
                'start_date'     => $activeRow['tanggal_mulai'],
                'end_date'       => $activeRow['tanggal_selesai'],
                'end_date_indo'  => UserController::formatDateIndo($activeRow['tanggal_selesai']),
            ];
        }

        // 2. Ambil riwayat seluruh langganan
        $stmtHistory = $db->prepare("
            SELECT l.*, p.nama_paket, p.durasi_hari, p.harga, t.metode_pembayaran, t.jumlah_bayar, t.dibayar_pada
            FROM langganan l
            JOIN paket p ON l.id_paket = p.id
            LEFT JOIN transaksi t ON t.id_langganan = l.id
            WHERE l.id_user = ?
            ORDER BY l.id DESC
        ");
        $stmtHistory->execute([$userId]);
        $historyRows = $stmtHistory->fetchAll();

        $methodMap = [
            'qris'     => 'QRIS',
            'transfer' => 'Transfer Bank',
            'tunai'    => 'Tunai',
        ];

        $history = [];
        foreach ($historyRows as $row) {
            $isAktif = $row['status'] === 'aktif';
            $price = !empty($row['jumlah_bayar']) ? (float)$row['jumlah_bayar'] : (float)$row['harga'];
            $method = $methodMap[strtolower($row['metode_pembayaran'] ?? '')] ?? ($row['metode_pembayaran'] ? ucfirst($row['metode_pembayaran']) : 'Tunai');

            $history[] = [
                'id'             => (int)$row['id'],
                'package_name'   => $row['nama_paket'],
                'period'         => UserController::formatDateIndo($row['tanggal_mulai']) . ' - ' . UserController::formatDateIndo($row['tanggal_selesai']),
                'start_date'     => $row['tanggal_mulai'],
                'end_date'       => $row['tanggal_selesai'],
                'duration'       => "{$row['durasi_hari']} Hari",
                'price'          => 'Rp ' . number_format($price, 0, ',', '.'),
                'payment_method' => $method,
                'status'         => $isAktif ? 'Aktif' : 'Selesai',
                'is_active'      => $isAktif,
            ];
        }

        UserController::json([
            'status' => 'success',
            'data'   => [
                'active'  => $activeData,
                'history' => $history,
            ]
        ]);
    }
}
