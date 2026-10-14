<?php
// backend/controllers/user/UserAttendanceController.php
require_once __DIR__ . '/UserController.php';

class UserAttendanceController {
    /**
     * Mengambil seluruh riwayat presensi member
     * Endpoint: GET /api/user/attendance
     */
    public static function index(): void {
        $db = getDB();
        $user = UserController::getAuthenticatedUser($db);
        $userId = (int)$user['id'];

        $stmt = $db->prepare("
            SELECT id, sesi, catatan, waktu_presensi
            FROM presensi
            WHERE id_user = ?
            ORDER BY waktu_presensi DESC
        ");
        $stmt->execute([$userId]);
        $rows = $stmt->fetchAll();

        $attendances = [];
        foreach ($rows as $row) {
            $ts = strtotime($row['waktu_presensi']);
            $dateOnly = date('Y-m-d', $ts);
            $attendances[] = [
                'id'         => (int)$row['id'],
                'date'       => UserController::formatDateIndo($dateOnly),
                'day_name'   => UserController::formatDayName($dateOnly),
                'time'       => date('H:i', $ts),
                'session'    => $row['sesi'] ?? 'Gym Umum / Fitness Regular',
                'notes'      => $row['catatan'] ?? '',
                'status'     => 'Hadir',
                'raw_date'   => $dateOnly,
                'created_at' => $row['waktu_presensi'],
            ];
        }

        $total = count($attendances);

        UserController::json([
            'status' => 'success',
            'data'   => [
                'total'       => $total,
                'attendances' => $attendances,
            ],
        ]);
    }

    /**
     * Check-in kehadiran member hari ini
     * Endpoint: POST /api/user/attendance/checkin
     */
    public static function checkIn(): void {
        $db = getDB();
        UserController::autoExpire($db);
        $user = UserController::getAuthenticatedUser($db);
        $userId = (int)$user['id'];

        // Cek apakah sudah pernah check-in hari ini
        $stmtCheck = $db->prepare("
            SELECT id, waktu_presensi FROM presensi 
            WHERE id_user = ? AND DATE(waktu_presensi) = CURDATE() 
            LIMIT 1
        ");
        $stmtCheck->execute([$userId]);
        $existing = $stmtCheck->fetch();

        if ($existing) {
            UserController::error('Anda sudah melakukan check-in hari ini.', 422);
            return;
        }

        $input = UserController::getInput();
        $session = trim($input['session'] ?? $input['sesi'] ?? 'Gym Umum / Fitness Regular');
        $notes = trim($input['notes'] ?? $input['catatan'] ?? '');

        if ($session === '') {
            $session = 'Gym Umum / Fitness Regular';
        }

        $stmtInsert = $db->prepare("
            INSERT INTO presensi (id_user, sesi, catatan, waktu_presensi)
            VALUES (?, ?, ?, NOW())
        ");
        $stmtInsert->execute([$userId, $session, $notes]);
        $newId = (int)$db->lastInsertId();

        // Ambil data yang baru dibuat
        $stmtGet = $db->prepare("SELECT * FROM presensi WHERE id = ?");
        $stmtGet->execute([$newId]);
        $record = $stmtGet->fetch();

        $ts = strtotime($record['waktu_presensi']);
        $dateOnly = date('Y-m-d', $ts);

        UserController::json([
            'status'  => 'success',
            'message' => 'Presensi berhasil dicatat!',
            'data'    => [
                'id'         => (int)$record['id'],
                'date'       => UserController::formatDateIndo($dateOnly),
                'day_name'   => UserController::formatDayName($dateOnly),
                'time'       => date('H:i', $ts),
                'session'    => $record['sesi'],
                'notes'      => $record['catatan'],
                'status'     => 'Hadir',
                'created_at' => $record['waktu_presensi'],
            ]
        ], 201);
    }
}
