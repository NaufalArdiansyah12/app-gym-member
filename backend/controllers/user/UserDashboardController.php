<?php
// backend/controllers/user/UserDashboardController.php
require_once __DIR__ . '/UserController.php';

class UserDashboardController {
    /**
     * Mengambil ringkasan dashboard member
     * Endpoint: GET /api/user/dashboard
     */
    public static function index(): void {
        $db = getDB();
        UserController::autoExpire($db);
        $user = UserController::getAuthenticatedUser($db);
        $userId = (int)$user['id'];

        // 1. Ambil paket langganan aktif
        $stmtPkg = $db->prepare("
            SELECT l.*, p.nama_paket, p.durasi_hari, p.harga
            FROM langganan l
            JOIN paket p ON l.id_paket = p.id
            WHERE l.id_user = ?
            ORDER BY (l.status = 'aktif') DESC, l.id DESC
            LIMIT 1
        ");
        $stmtPkg->execute([$userId]);
        $activePkg = $stmtPkg->fetch();

        $daysRemaining = 0;
        $packageName = 'Belum Ada Paket';
        $expiresShort = '-';

        if ($activePkg) {
            $packageName = $activePkg['nama_paket'];
            $endDate = $activePkg['tanggal_selesai'];
            $expiresShort = UserController::formatDateIndo($endDate);
            if ($activePkg['status'] === 'aktif') {
                $todayTime = strtotime(date('Y-m-d'));
                $endTime = strtotime($endDate);
                $daysRemaining = max(0, (int)ceil(($endTime - $todayTime) / 86400));
            }
        } elseif (!empty($user['membership_expires_at'])) {
            $endDate = $user['membership_expires_at'];
            $expiresShort = UserController::formatDateIndo($endDate);
            $todayTime = strtotime(date('Y-m-d'));
            $endTime = strtotime($endDate);
            $daysRemaining = max(0, (int)ceil(($endTime - $todayTime) / 86400));
        }

        // 2. Cek presensi hari ini
        $stmtToday = $db->prepare("
            SELECT * FROM presensi 
            WHERE id_user = ? AND DATE(waktu_presensi) = CURDATE()
            ORDER BY waktu_presensi DESC
            LIMIT 1
        ");
        $stmtToday->execute([$userId]);
        $todayCheckin = $stmtToday->fetch();

        // 3. Hitung presensi bulan ini
        $stmtMonth = $db->prepare("
            SELECT COUNT(*) FROM presensi 
            WHERE id_user = ? 
              AND MONTH(waktu_presensi) = MONTH(CURDATE()) 
              AND YEAR(waktu_presensi) = YEAR(CURDATE())
        ");
        $stmtMonth->execute([$userId]);
        $monthlyVisits = (int)$stmtMonth->fetchColumn();

        // 4. Riwayat presensi terbaru (3 sesi)
        $stmtRecent = $db->prepare("
            SELECT id, sesi, catatan, waktu_presensi 
            FROM presensi 
            WHERE id_user = ? 
            ORDER BY waktu_presensi DESC 
            LIMIT 5
        ");
        $stmtRecent->execute([$userId]);
        $recentRows = $stmtRecent->fetchAll();

        $recentAttendances = [];
        foreach ($recentRows as $row) {
            $ts = strtotime($row['waktu_presensi']);
            $dateOnly = date('Y-m-d', $ts);
            $recentAttendances[] = [
                'id'         => (int)$row['id'],
                'session'    => $row['sesi'] ?? 'Gym Umum / Fitness Regular',
                'notes'      => $row['catatan'] ?? '',
                'time'       => date('H:i', $ts),
                'date'       => UserController::formatDateIndo($dateOnly),
                'day_name'   => UserController::formatDayName($dateOnly),
                'created_at' => $row['waktu_presensi'],
            ];
        }

        // 5. Riwayat paket membership ringkas
        $stmtListPkg = $db->prepare("
            SELECT l.*, p.nama_paket, p.durasi_hari 
            FROM langganan l 
            JOIN paket p ON l.id_paket = p.id 
            WHERE l.id_user = ? 
            ORDER BY l.id DESC 
            LIMIT 5
        ");
        $stmtListPkg->execute([$userId]);
        $allPkg = $stmtListPkg->fetchAll();

        $recentPackages = [];
        foreach ($allPkg as $pkg) {
            $isAktif = $pkg['status'] === 'aktif';
            $diffDays = 0;
            if ($isAktif) {
                $diffDays = max(0, (int)ceil((strtotime($pkg['tanggal_selesai']) - strtotime(date('Y-m-d'))) / 86400));
            }
            $recentPackages[] = [
                'id'             => (int)$pkg['id'],
                'package_name'   => $pkg['nama_paket'],
                'duration'       => "{$pkg['durasi_hari']} Hari",
                'end_date'       => UserController::formatDateIndo($pkg['tanggal_selesai']),
                'status'         => $isAktif ? 'Aktif' : 'Selesai',
                'days_remaining' => $diffDays,
            ];
        }

        $initials = strtoupper(mb_substr(trim($user['name'] ?: 'Member'), 0, 1));

        UserController::json([
            'status' => 'success',
            'data'   => [
                'user' => [
                    'id'                    => (int)$user['id'],
                    'member_code'           => $user['member_code'],
                    'name'                  => $user['name'],
                    'email'                 => $user['email'],
                    'phone'                 => $user['phone'],
                    'initials'              => $initials,
                    'membership_status'     => $user['membership_status'],
                    'membership_expires_at' => $user['membership_expires_at'],
                ],
                'stats' => [
                    'days_remaining'           => $daysRemaining,
                    'package_name'             => $packageName,
                    'membership_status'        => $user['membership_status'],
                    'membership_expires_short' => $expiresShort,
                    'today_checkin'            => [
                        'checked_in' => (bool)$todayCheckin,
                        'time'       => $todayCheckin ? date('H:i', strtotime($todayCheckin['waktu_presensi'])) : null,
                        'session'    => $todayCheckin ? ($todayCheckin['sesi'] ?? 'Gym Umum / Fitness Regular') : null,
                    ],
                    'monthly_visits'           => $monthlyVisits,
                ],
                'recent_attendances' => $recentAttendances,
                'recent_packages'    => $recentPackages,
            ]
        ]);
    }
}
