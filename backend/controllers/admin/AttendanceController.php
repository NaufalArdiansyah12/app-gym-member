<?php
// backend/controllers/admin/AttendanceController.php
require_once __DIR__ . '/AdminController.php';

class AttendanceController {
    /**
     * Mengambil daftar riwayat presensi/check-in
     * Endpoint: GET /api/admin/attendance
     */
    public static function index(): void {
        AdminController::requireAdmin();
        $db = getDB();

        $date = trim($_GET['date'] ?? '');
        $search = trim($_GET['search'] ?? $_GET['q'] ?? '');

        $sql = "
            SELECT 
                p.id,
                p.waktu_presensi,
                u.id as user_id,
                u.member_code,
                u.name,
                u.phone,
                u.membership_status,
                u.membership_expires_at,
                COALESCE(pk.nama_paket, 'Paket Gym') as nama_paket
            FROM presensi p
            JOIN users u ON p.id_user = u.id
            LEFT JOIN (
                SELECT id_user, id_paket 
                FROM langganan 
                WHERE status = 'aktif' 
                ORDER BY id DESC
            ) l ON l.id_user = u.id
            LEFT JOIN paket pk ON l.id_paket = pk.id
            WHERE 1=1
        ";

        $params = [];

        if ($date !== '') {
            $sql .= " AND DATE(p.waktu_presensi) = ?";
            $params[] = $date;
        }

        if ($search !== '') {
            $sql .= " AND (u.name LIKE ? OR u.member_code LIKE ?)";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
        }

        $sql .= " ORDER BY p.waktu_presensi DESC LIMIT 100";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        $attendances = [];
        foreach ($rows as $row) {
            $attendances[] = [
                'id'                    => (int)$row['id'],
                'user_id'               => (int)$row['user_id'],
                'member_code'           => $row['member_code'],
                'name'                  => $row['name'],
                'phone'                 => $row['phone'],
                'nama_paket'            => $row['nama_paket'],
                'membership_status'     => $row['membership_status'],
                'membership_expires_at' => $row['membership_expires_at'],
                'waktu_presensi'        => $row['waktu_presensi'],
                'jam'                   => date('H:i', strtotime($row['waktu_presensi'])),
                'tanggal'               => date('Y-m-d', strtotime($row['waktu_presensi'])),
            ];
        }

        // Hitung statistik presensi hari ini
        $todayCount = (int)$db->query("
            SELECT COUNT(*) FROM presensi WHERE DATE(waktu_presensi) = CURDATE()
        ")->fetchColumn();

        $uniqueToday = (int)$db->query("
            SELECT COUNT(DISTINCT id_user) FROM presensi WHERE DATE(waktu_presensi) = CURDATE()
        ")->fetchColumn();

        AdminController::json([
            'status' => 'success',
            'data'   => [
                'stats' => [
                    'today_checkins'  => $todayCount,
                    'unique_members'  => $uniqueToday,
                ],
                'attendances' => $attendances,
            ],
        ]);
    }

    /**
     * Check-in member baru di gym
     * Endpoint: POST /api/admin/attendance
     */
    public static function store(): void {
        AdminController::requireAdmin();
        $db = getDB();
        $input = AdminController::getInput();

        $memberCode = trim($input['member_code'] ?? $input['kode_member'] ?? '');
        $userId = (int)($input['user_id'] ?? $input['id_user'] ?? 0);

        if ($memberCode === '' && $userId <= 0) {
            AdminController::error('Kode member atau ID member wajib disertakan', 422);
        }

        // Cari user
        if ($memberCode !== '') {
            $stmt = $db->prepare("SELECT * FROM users WHERE (member_code = ? OR phone = ?) AND role = 'user' LIMIT 1");
            $stmt->execute([$memberCode, $memberCode]);
        } else {
            $stmt = $db->prepare("SELECT * FROM users WHERE id = ? AND role = 'user' LIMIT 1");
            $stmt->execute([$userId]);
        }

        $user = $stmt->fetch();

        if (!$user) {
            AdminController::error('Member tidak ditemukan dalam sistem', 404);
        }

        $today = date('Y-m-d');
        $expiresAt = $user['membership_expires_at'];
        $isExpired = false;
        $alertMessage = null;

        if ($user['membership_status'] === 'inactive') {
            $alertMessage = "Perhatian: Member {$user['name']} belum memiliki paket aktif.";
        } elseif ($expiresAt && $expiresAt < $today) {
            $isExpired = true;
            $alertMessage = "Peringatan: Masa aktif member {$user['name']} telah kedaluwarsa pada {$expiresAt}.";
        } elseif ($expiresAt && $expiresAt <= date('Y-m-d', strtotime('+3 days'))) {
            $alertMessage = "Pemberitahuan: Masa aktif member {$user['name']} akan berakhir pada {$expiresAt}.";
        }

        // Simpan presensi
        $insStmt = $db->prepare("INSERT INTO presensi (id_user, waktu_presensi) VALUES (?, NOW())");
        $insStmt->execute([$user['id']]);
        $presensiId = (int)$db->lastInsertId();

        $waktu = date('H:i');

        AdminController::json([
            'status'  => 'success',
            'message' => "Check-in berhasil untuk {$user['name']} ({$user['member_code']}) pukul {$waktu} WIB",
            'warning' => $alertMessage,
            'data'    => [
                'id'             => $presensiId,
                'user_id'        => (int)$user['id'],
                'member_code'    => $user['member_code'],
                'name'           => $user['name'],
                'waktu'          => $waktu,
                'is_expired'     => $isExpired,
                'status'         => $user['membership_status'],
            ],
        ], 201);
    }
}

