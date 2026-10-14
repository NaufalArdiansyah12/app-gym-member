<?php
// backend/controller/admin/AttendanceController.php

class AttendanceController {
    public static function checkIn(): void {
        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        $memberCode = strtoupper(trim($input['member_code'] ?? ''));

        if ($memberCode === '' || !preg_match('/^GYM[-A-Z0-9]+$/', $memberCode)) {
            http_response_code(422);
            echo json_encode(['error' => 'Kode member tidak valid']);
            return;
        }

        $db = getDB();
        $stmt = $db->prepare('SELECT id, member_code, name, membership_status, membership_expires_at FROM users WHERE member_code = ? AND role = "user" LIMIT 1');
        $stmt->execute([$memberCode]);
        $member = $stmt->fetch();

        if (!$member) {
            http_response_code(404);
            echo json_encode(['error' => 'Member tidak ditemukan']);
            return;
        }

        if ($member['membership_status'] !== 'active' || empty($member['membership_expires_at']) || $member['membership_expires_at'] < date('Y-m-d')) {
            http_response_code(422);
            echo json_encode(['error' => 'Membership member tidak aktif atau sudah kedaluwarsa']);
            return;
        }

        $insert = $db->prepare('INSERT INTO presensi (id_user, waktu_presensi) VALUES (?, NOW())');
        $insert->execute([$member['id']]);
        $attendanceId = (int) $db->lastInsertId();

        echo json_encode([
            'status' => 'ok',
            'message' => 'Kehadiran berhasil dicatat',
            'data' => [
                'id' => $attendanceId,
                'member_code' => $member['member_code'],
                'name' => $member['name'],
                'waktu_presensi' => date('Y-m-d H:i:s'),
            ],
        ]);
    }

    public static function today(): void {
        $db = getDB();
        $summary = $db->query('SELECT COUNT(*) AS total FROM presensi WHERE DATE(waktu_presensi) = CURRENT_DATE')->fetch();
        $stmt = $db->query('SELECT pr.id, u.member_code, u.name, pr.waktu_presensi FROM presensi pr JOIN users u ON u.id = pr.id_user WHERE DATE(pr.waktu_presensi) = CURRENT_DATE ORDER BY pr.waktu_presensi DESC, pr.id DESC LIMIT 50');

        echo json_encode([
            'status' => 'ok',
            'data' => [
                'total' => (int) ($summary['total'] ?? 0),
                'items' => $stmt->fetchAll(),
            ],
        ]);
    }
}
