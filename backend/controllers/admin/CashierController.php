<?php
// backend/controllers/admin/CashierController.php

class CashierController {
    public static function initData(): void {
        $db = getDB();

        // 1. Data paket aktif
        $stmtPaket = $db->query('SELECT id, nama_paket, durasi_hari, CAST(harga AS DOUBLE) AS harga, tersedia FROM paket WHERE tersedia = 1 ORDER BY harga ASC');
        $packages = $stmtPaket->fetchAll();

        // 2. Data member
        $stmtUsers = $db->query('SELECT id, member_code, name, phone, email, membership_status, membership_expires_at FROM users WHERE role = "user" ORDER BY name ASC');
        $members = $stmtUsers->fetchAll();

        // 3. Ringkasan kasir hari ini
        $stmtSummary = $db->query("
            SELECT
                COUNT(*) AS today_count,
                COALESCE(SUM(jumlah_bayar), 0) AS today_revenue,
                COALESCE(SUM(CASE WHEN metode_pembayaran = 'tunai' THEN jumlah_bayar ELSE 0 END), 0) AS cash_total,
                COALESCE(SUM(CASE WHEN metode_pembayaran IN ('transfer', 'qris') THEN jumlah_bayar ELSE 0 END), 0) AS non_cash_total
            FROM transaksi
            WHERE DATE(dibayar_pada) = CURRENT_DATE
        ");
        $summary = $stmtSummary->fetch() ?: [
            'today_count' => 0,
            'today_revenue' => 0,
            'cash_total' => 0,
            'non_cash_total' => 0
        ];

        // 4. Riwayat transaksi terbaru
        $stmtTrx = $db->query("
            SELECT
                t.id,
                t.id_langganan,
                CAST(t.jumlah_bayar AS DOUBLE) AS jumlah_bayar,
                t.metode_pembayaran,
                t.dibayar_pada,
                u.id AS user_id,
                u.member_code,
                u.name AS member_name,
                u.phone AS member_phone,
                p.id AS paket_id,
                p.nama_paket,
                p.durasi_hari,
                CAST(p.harga AS DOUBLE) AS harga_paket,
                l.tanggal_mulai,
                l.tanggal_selesai,
                l.status AS status_langganan
            FROM transaksi t
            JOIN langganan l ON t.id_langganan = l.id
            JOIN users u ON l.id_user = u.id
            JOIN paket p ON l.id_paket = p.id
            ORDER BY t.dibayar_pada DESC, t.id DESC
            LIMIT 25
        ");
        $transactions = $stmtTrx->fetchAll();

        echo json_encode([
            'status' => 'ok',
            'data' => [
                'packages' => $packages,
                'members' => $members,
                'summary' => [
                    'today_count' => (int) $summary['today_count'],
                    'today_revenue' => (float) $summary['today_revenue'],
                    'cash_total' => (float) $summary['cash_total'],
                    'non_cash_total' => (float) $summary['non_cash_total'],
                ],
                'transactions' => $transactions,
            ]
        ]);
    }

    public static function createTransaction(): void {
        $input = json_decode(file_get_contents('php://input'), true) ?? [];

        $userId = isset($input['user_id']) ? (int) $input['user_id'] : 0;
        $paketId = isset($input['paket_id']) ? (int) $input['paket_id'] : 0;
        $metode = trim($input['metode_pembayaran'] ?? '');
        $bayarNominal = isset($input['jumlah_bayar']) ? (float) $input['jumlah_bayar'] : 0.0;
        $tanggalMulaiInput = trim($input['tanggal_mulai'] ?? '');

        if ($userId <= 0 || $paketId <= 0 || $metode === '') {
            http_response_code(422);
            echo json_encode(['error' => 'Data transaksi tidak lengkap']);
            return;
        }

        $validMetode = ['tunai', 'transfer', 'qris'];
        if (!in_array($metode, $validMetode, true)) {
            http_response_code(422);
            echo json_encode(['error' => 'Metode pembayaran tidak valid']);
            return;
        }

        $db = getDB();

        // Cek member
        $stmtUser = $db->prepare('SELECT id, member_code, name, phone, email, membership_status, membership_expires_at FROM users WHERE id = ? AND role = "user" LIMIT 1');
        $stmtUser->execute([$userId]);
        $user = $stmtUser->fetch();
        if (!$user) {
            http_response_code(404);
            echo json_encode(['error' => 'Member tidak ditemukan']);
            return;
        }

        // Cek paket
        $stmtPaket = $db->prepare('SELECT id, nama_paket, durasi_hari, CAST(harga AS DOUBLE) AS harga, tersedia FROM paket WHERE id = ? LIMIT 1');
        $stmtPaket->execute([$paketId]);
        $paket = $stmtPaket->fetch();
        if (!$paket || !$paket['tersedia']) {
            http_response_code(404);
            echo json_encode(['error' => 'Paket langganan tidak valid atau tidak tersedia']);
            return;
        }

        $hargaPaket = (float) $paket['harga'];
        if ($bayarNominal < $hargaPaket) {
            http_response_code(422);
            echo json_encode(['error' => "Jumlah bayar kurang dari harga paket (Rp " . number_format($hargaPaket, 0, ',', '.') . ")"]);
            return;
        }

        $tipeTransaksi = trim($input['tipe_transaksi'] ?? 'baru');
        $validTipe = ['baru', 'perpanjang'];
        if (!in_array($tipeTransaksi, $validTipe, true)) {
            $tipeTransaksi = 'baru';
        }

        // Hitung tanggal mulai dan tanggal selesai berdasarkan tipe transaksi
        // ponytail: Jika perpanjang & member masih aktif, sambung dari expiry sebelumnya; jika baru, mulai hari ini.
        $today = date('Y-m-d');
        if ($tanggalMulaiInput !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggalMulaiInput)) {
            $startDate = $tanggalMulaiInput;
        } elseif ($tipeTransaksi === 'perpanjang' && !empty($user['membership_expires_at']) && $user['membership_expires_at'] >= $today) {
            $startDate = date('Y-m-d', strtotime($user['membership_expires_at'] . ' +1 day'));
        } else {
            $startDate = $today;
        }

        $durasiHari = (int) $paket['durasi_hari'];
        $endDate = date('Y-m-d', strtotime("$startDate + $durasiHari days"));

        try {
            $db->beginTransaction();

            // 1. Simpan langganan
            $stmtLangganan = $db->prepare('INSERT INTO langganan (id_user, id_paket, tanggal_mulai, tanggal_selesai, status) VALUES (?, ?, ?, ?, "aktif")');
            $stmtLangganan->execute([$userId, $paketId, $startDate, $endDate]);
            $langgananId = (int) $db->lastInsertId();

            // 2. Simpan transaksi
            $stmtTrx = $db->prepare('INSERT INTO transaksi (id_langganan, jumlah_bayar, metode_pembayaran, dibayar_pada) VALUES (?, ?, ?, NOW())');
            $stmtTrx->execute([$langgananId, $bayarNominal, $metode]);
            $transaksiId = (int) $db->lastInsertId();

            // 3. Update status membership user
            $stmtUpdateUser = $db->prepare('UPDATE users SET membership_status = "active", membership_expires_at = ? WHERE id = ?');
            $stmtUpdateUser->execute([$endDate, $userId]);

            $db->commit();

            $kembalian = max(0.0, $bayarNominal - $hargaPaket);
            $invoiceCode = 'TRX-' . date('Ymd') . '-' . str_pad((string) $transaksiId, 4, '0', STR_PAD_LEFT);

            http_response_code(201);
            echo json_encode([
                'status' => 'ok',
                'message' => 'Transaksi berhasil diproses',
                'data' => [
                    'transaction_id' => $transaksiId,
                    'invoice_code' => $invoiceCode,
                    'member' => [
                        'id' => $user['id'],
                        'name' => $user['name'],
                        'member_code' => $user['member_code'],
                        'phone' => $user['phone'],
                        'membership_expires_at' => $endDate,
                    ],
                    'paket' => [
                        'id' => $paket['id'],
                        'nama_paket' => $paket['nama_paket'],
                        'durasi_hari' => $durasiHari,
                        'harga' => $hargaPaket,
                    ],
                    'tanggal_mulai' => $startDate,
                    'tanggal_selesai' => $endDate,
                    'tipe_transaksi' => $tipeTransaksi === 'perpanjang' ? 'Perpanjang Paket' : 'Beli Paket Baru',
                    'metode_pembayaran' => $metode,
                    'jumlah_bayar' => $bayarNominal,
                    'kembalian' => $kembalian,
                    'dibayar_pada' => date('Y-m-d H:i:s'),
                ]
            ]);
        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            http_response_code(500);
            echo json_encode(['error' => 'Gagal memproses transaksi: ' . $e->getMessage()]);
        }
    }

    public static function getTransactions(): void {
        $db = getDB();
        $q = trim($_GET['q'] ?? '');

        $sql = "
            SELECT
                t.id,
                t.id_langganan,
                CAST(t.jumlah_bayar AS DOUBLE) AS jumlah_bayar,
                t.metode_pembayaran,
                t.dibayar_pada,
                u.id AS user_id,
                u.member_code,
                u.name AS member_name,
                u.phone AS member_phone,
                p.id AS paket_id,
                p.nama_paket,
                p.durasi_hari,
                CAST(p.harga AS DOUBLE) AS harga_paket,
                l.tanggal_mulai,
                l.tanggal_selesai,
                l.status AS status_langganan
            FROM transaksi t
            JOIN langganan l ON t.id_langganan = l.id
            JOIN users u ON l.id_user = u.id
            JOIN paket p ON l.id_paket = p.id
        ";

        $params = [];
        if ($q !== '') {
            $sql .= " WHERE u.name LIKE ? OR u.member_code LIKE ? OR p.nama_paket LIKE ? ";
            $like = "%$q%";
            $params = [$like, $like, $like];
        }

        $sql .= " ORDER BY t.dibayar_pada DESC, t.id DESC LIMIT 50";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $transactions = $stmt->fetchAll();

        echo json_encode([
            'status' => 'ok',
            'data' => $transactions
        ]);
    }

    public static function searchMembers(): void {
        $db = getDB();
        $q = trim($_GET['q'] ?? '');

        $sql = 'SELECT id, member_code, name, phone, email, membership_status, membership_expires_at FROM users WHERE role = "user"';
        $params = [];
        if ($q !== '') {
            $sql .= ' AND (name LIKE ? OR member_code LIKE ? OR phone LIKE ?) ';
            $like = "%$q%";
            $params = [$like, $like, $like];
        }

        $sql .= ' ORDER BY name ASC LIMIT 25';
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $members = $stmt->fetchAll();

        echo json_encode([
            'status' => 'ok',
            'data' => $members
        ]);
    }
}

