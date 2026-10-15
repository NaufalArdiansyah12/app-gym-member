<?php
// backend/controllers/admin/DashboardController.php
require_once __DIR__ . '/AdminController.php';

class DashboardController {
    /**
     * Mengambil seluruh data kebutuhan tampilan dashboard
     * Endpoint: GET /api/admin/dashboard
     */
    public static function index(): void {
        AdminController::requireAdmin();
        $db = getDB();
        AdminController::autoExpireMembers($db);

        $stats = self::getStats($db);
        $revenueChart = self::getRevenueChart($db);
        $memberChart = self::getMemberChart($db);
        $recentCheckins = self::getRecentCheckins($db);
        $expiringMembers = self::getExpiringMembers($db);

        AdminController::json([
            'status' => 'success',
            'data'   => [
                'stats'            => $stats,
                'chart'            => $memberChart,
                'member_chart'     => $memberChart,
                'revenue_chart'    => $revenueChart,
                'recent_checkins'  => $recentCheckins,
                'expiring_members' => $expiringMembers,
            ],
        ]);
    }

    /**
     * Hitung metrik statistik ringkasan
     */
    public static function getStats(PDO $db): array {
        // Total Member (role = user)
        $totalMembers = (int)$db->query("
            SELECT COUNT(*) 
            FROM users 
            WHERE role = 'user'
        ")->fetchColumn();

        // Member Aktif
        $activeMembers = (int)$db->query("
            SELECT COUNT(*) 
            FROM users 
            WHERE role = 'user' 
              AND membership_status = 'active'
        ")->fetchColumn();

        // Akan Berakhir dalam 7 hari kedepan
        $expiringMembers = (int)$db->query("
            SELECT COUNT(*) 
            FROM users 
            WHERE role = 'user' 
              AND membership_status = 'active' 
              AND membership_expires_at IS NOT NULL 
              AND membership_expires_at >= CURDATE()
              AND membership_expires_at <= DATE_ADD(CURDATE(), INTERVAL 7 DAY)
        ")->fetchColumn();

        // Check-in Hari Ini
        $todayCheckins = (int)$db->query("
            SELECT COUNT(*) 
            FROM presensi 
            WHERE DATE(waktu_presensi) = CURDATE()
        ")->fetchColumn();

        // Total Pendapatan & Pendapatan Bulan Ini
        $monthRevenue = (float)$db->query("
            SELECT COALESCE(SUM(jumlah_bayar), 0) 
            FROM transaksi 
            WHERE MONTH(dibayar_pada) = MONTH(CURDATE()) 
              AND YEAR(dibayar_pada) = YEAR(CURDATE())
        ")->fetchColumn();

        $totalRevenue = (float)$db->query("
            SELECT COALESCE(SUM(jumlah_bayar), 0) 
            FROM transaksi
        ")->fetchColumn();

        return [
            'total_members'    => $totalMembers,
            'active_members'   => $activeMembers,
            'expiring_members' => $expiringMembers,
            'today_checkins'   => $todayCheckins,
            'month_revenue'    => $monthRevenue,
            'total_revenue'    => $totalRevenue,
            'month_revenue_formatted' => 'Rp ' . number_format($monthRevenue, 0, ',', '.'),
            'total_revenue_formatted' => 'Rp ' . number_format($totalRevenue, 0, ',', '.'),
        ];
    }

    /**
     * Data grafik ringkasan pendapatan 7 bulan terakhir
     */
    public static function getRevenueChart(PDO $db): array {
        $months = [];
        $indonesianMonths = [
            1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr', 5 => 'Mei', 6 => 'Jun',
            7 => 'Jul', 8 => 'Agu', 9 => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Des'
        ];

        // Buat rentang 7 bulan ke belakang hingga bulan saat ini
        for ($i = 6; $i >= 0; $i--) {
            $time = strtotime("-{$i} month");
            $m = (int)date('n', $time);
            $y = (int)date('Y', $time);
            $key = sprintf('%04d-%02d', $y, $m);
            $months[$key] = [
                'label'   => $indonesianMonths[$m],
                'month'   => $m,
                'year'    => $y,
                'revenue' => 0.0,
            ];
        }

        $stmt = $db->query("
            SELECT 
                DATE_FORMAT(dibayar_pada, '%Y-%m') as periode, 
                SUM(jumlah_bayar) as total 
            FROM transaksi 
            WHERE dibayar_pada >= DATE_SUB(CURDATE(), INTERVAL 7 MONTH)
            GROUP BY DATE_FORMAT(dibayar_pada, '%Y-%m')
        ");
        $results = $stmt->fetchAll();

        foreach ($results as $row) {
            if (isset($months[$row['periode']])) {
                $months[$row['periode']]['revenue'] = (float)$row['total'];
            }
        }

        $labels = array_column($months, 'label');
        $series = array_column($months, 'revenue');
        $sum7Months = array_sum($series);

        return [
            'labels'           => $labels,
            'series'           => $series,
            'total_7_months'   => $sum7Months,
            'total_formatted'  => 'Rp ' . number_format($sum7Months, 0, ',', '.'),
        ];
    }

    /**
     * Data grafik ringkasan pendaftaran member baru 7 bulan terakhir
     */
    public static function getMemberChart(PDO $db): array {
        $months = [];
        $indonesianMonths = [
            1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr', 5 => 'Mei', 6 => 'Jun',
            7 => 'Jul', 8 => 'Agu', 9 => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Des'
        ];

        for ($i = 6; $i >= 0; $i--) {
            $time = strtotime("-{$i} month");
            $m = (int)date('n', $time);
            $y = (int)date('Y', $time);
            $key = sprintf('%04d-%02d', $y, $m);
            $months[$key] = [
                'label' => $indonesianMonths[$m],
                'month' => $m,
                'year'  => $y,
                'count' => 0,
            ];
        }

        $stmt = $db->query("
            SELECT 
                DATE_FORMAT(created_at, '%Y-%m') as periode, 
                COUNT(*) as total 
            FROM users 
            WHERE role = 'user' 
              AND created_at >= DATE_SUB(CURDATE(), INTERVAL 7 MONTH)
            GROUP BY DATE_FORMAT(created_at, '%Y-%m')
        ");
        $results = $stmt->fetchAll();

        foreach ($results as $row) {
            if (isset($months[$row['periode']])) {
                $months[$row['periode']]['count'] = (int)$row['total'];
            }
        }

        $labels = array_column($months, 'label');
        $series = array_column($months, 'count');
        $sum7Months = array_sum($series);

        return [
            'labels'          => $labels,
            'series'          => $series,
            'total_7_months'  => $sum7Months,
            'total_formatted' => number_format($sum7Months, 0, ',', '.') . ' Member',
        ];
    }

    /**
     * Daftar kedatangan / check-in terbaru hari ini
     */
    public static function getRecentCheckins(PDO $db): array {
        $colors = ['peach', 'blue', 'violet', 'green', 'orange'];
        $stmt = $db->query("
            SELECT 
                p.id,
                p.waktu_presensi,
                u.id as user_id,
                u.name,
                u.member_code,
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
            WHERE DATE(p.waktu_presensi) = CURDATE()
            ORDER BY p.waktu_presensi DESC
            LIMIT 10
        ");
        $rows = $stmt->fetchAll();

        $data = [];
        $i = 0;
        foreach ($rows as $row) {
            $words = preg_split('/\s+/', trim($row['name']));
            $initials = strtoupper(substr($words[0] ?? 'M', 0, 1) . substr($words[1] ?? '', 0, 1));
            if ($initials === '') $initials = 'M';

            $data[] = [
                'id'             => (int)$row['id'],
                'user_id'        => (int)$row['user_id'],
                'name'           => $row['name'],
                'member_code'    => $row['member_code'],
                'nama_paket'     => $row['nama_paket'],
                'waktu'          => date('H:i', strtotime($row['waktu_presensi'])),
                'waktu_lengkap'  => $row['waktu_presensi'],
                'initials'       => $initials,
                'avatar_color'   => $colors[$i % count($colors)],
            ];
            $i++;
        }

        return $data;
    }

    /**
     * Daftar member yang masa aktifnya akan berakhir dalam 7 hari
     */
    public static function getExpiringMembers(PDO $db): array {
        $indonesianMonths = [
            1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr', 5 => 'Mei', 6 => 'Jun',
            7 => 'Jul', 8 => 'Agu', 9 => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Des'
        ];

        $stmt = $db->query("
            SELECT 
                u.id,
                u.member_code,
                u.name,
                u.membership_expires_at,
                DATEDIFF(u.membership_expires_at, CURDATE()) as days_left,
                COALESCE(pk.nama_paket, 'Paket Gym') as nama_paket
            FROM users u
            LEFT JOIN (
                SELECT id_user, id_paket 
                FROM langganan 
                ORDER BY id DESC
            ) l ON l.id_user = u.id
            LEFT JOIN paket pk ON l.id_paket = pk.id
            WHERE u.role = 'user'
              AND u.membership_status = 'active'
              AND u.membership_expires_at IS NOT NULL
              AND u.membership_expires_at >= CURDATE()
              AND u.membership_expires_at <= DATE_ADD(CURDATE(), INTERVAL 7 DAY)
            GROUP BY u.id
            ORDER BY u.membership_expires_at ASC
            LIMIT 10
        ");
        $rows = $stmt->fetchAll();

        $data = [];
        foreach ($rows as $row) {
            $daysLeft = (int)$row['days_left'];
            $expDate = strtotime($row['membership_expires_at']);
            $day = date('j', $expDate);
            $month = $indonesianMonths[(int)date('n', $expDate)] ?? '';
            $year = date('Y', $expDate);
            $formattedDate = "{$day} {$month} {$year}";

            $statusText = $daysLeft === 0 ? 'Hari ini' : ($daysLeft === 1 ? 'Besok' : "{$daysLeft} hari lagi");

            $data[] = [
                'id'                    => (int)$row['id'],
                'member_code'           => $row['member_code'],
                'name'                  => $row['name'],
                'nama_paket'            => $row['nama_paket'],
                'membership_expires_at' => $row['membership_expires_at'],
                'expires_at_formatted'  => $formattedDate,
                'days_left'             => $daysLeft,
                'status_badge'          => $statusText,
            ];
        }

        return $data;
    }
}

