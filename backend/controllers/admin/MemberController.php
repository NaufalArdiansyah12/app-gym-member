<?php
// backend/controllers/admin/MemberController.php
require_once __DIR__ . '/AdminController.php';

class MemberController {
    /**
     * Mengambil daftar member beserta filter dan pencarian data orang (tanpa data paket)
     * Endpoint: GET /api/admin/members
     */
    public static function index(): void {
        AdminController::requireAdmin();
        $db = getDB();
        AdminController::autoExpireMembers($db);

        $search = trim($_GET['search'] ?? $_GET['q'] ?? '');
        $genderFilter = trim($_GET['gender'] ?? 'all');
        $statusFilter = trim($_GET['status'] ?? 'all');

        $indonesianMonths = [
            1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr', 5 => 'Mei', 6 => 'Jun',
            7 => 'Jul', 8 => 'Agu', 9 => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Des'
        ];

        // Hitung statistik berbasis data orang (member/person)
        $stats = [
            'total'         => (int)$db->query("SELECT COUNT(*) FROM users WHERE role = 'user'")->fetchColumn(),
            'pria'          => (int)$db->query("SELECT COUNT(*) FROM users WHERE role = 'user' AND gender = 'pria'")->fetchColumn(),
            'wanita'        => (int)$db->query("SELECT COUNT(*) FROM users WHERE role = 'user' AND gender = 'wanita'")->fetchColumn(),
            'with_contact'  => (int)$db->query("SELECT COUNT(*) FROM users WHERE role = 'user' AND emergency_contact IS NOT NULL AND TRIM(emergency_contact) != ''")->fetchColumn(),
            // Tetap sediakan status lama untuk fallback/kompatibilitas
            'active'        => (int)$db->query("SELECT COUNT(*) FROM users WHERE role = 'user' AND membership_status = 'active'")->fetchColumn(),
            'expiring'      => (int)$db->query("
                SELECT COUNT(*) FROM users 
                WHERE role = 'user' 
                  AND membership_status = 'active' 
                  AND membership_expires_at IS NOT NULL 
                  AND membership_expires_at >= CURDATE()
                  AND membership_expires_at <= DATE_ADD(CURDATE(), INTERVAL 7 DAY)
            ")->fetchColumn(),
            'expired'       => (int)$db->query("
                SELECT COUNT(*) FROM users 
                WHERE role = 'user' 
                  AND (membership_status = 'expired' OR (membership_expires_at IS NOT NULL AND membership_expires_at < CURDATE()))
            ")->fetchColumn(),
        ];

        // Bangun query list data orang (profil member)
        $sql = "
            SELECT 
                u.id,
                u.member_code,
                u.name,
                u.email,
                u.phone,
                u.gender,
                u.address,
                u.emergency_contact,
                u.membership_status,
                u.membership_expires_at,
                u.created_at
            FROM users u
            WHERE u.role = 'user'
        ";

        $params = [];

        if ($search !== '') {
            $sql .= " AND (u.name LIKE ? OR u.member_code LIKE ? OR u.phone LIKE ?)";
            $searchTerm = "%{$search}%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }

        if ($genderFilter === 'pria' || $genderFilter === 'wanita') {
            $sql .= " AND u.gender = ?";
            $params[] = $genderFilter;
        }

        if ($statusFilter === 'active') {
            $sql .= " AND u.membership_status = 'active'";
        } elseif ($statusFilter === 'expiring') {
            $sql .= " AND u.membership_status = 'active' 
                      AND u.membership_expires_at IS NOT NULL 
                      AND u.membership_expires_at >= CURDATE() 
                      AND u.membership_expires_at <= DATE_ADD(CURDATE(), INTERVAL 7 DAY)";
        } elseif ($statusFilter === 'expired') {
            $sql .= " AND (u.membership_status = 'expired' OR (u.membership_expires_at IS NOT NULL AND u.membership_expires_at < CURDATE()))";
        } elseif ($statusFilter === 'inactive') {
            $sql .= " AND u.membership_status = 'inactive'";
        }

        $sql .= " ORDER BY u.id DESC";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        $members = [];
        foreach ($rows as $row) {
            $formattedCreatedDate = '-';
            if (!empty($row['created_at'])) {
                $cTime = strtotime($row['created_at']);
                $d = date('j', $cTime);
                $m = $indonesianMonths[(int)date('n', $cTime)] ?? '';
                $y = date('Y', $cTime);
                $formattedCreatedDate = "{$d} {$m} {$y}";
            }

            $gender = strtolower($row['gender'] ?? 'pria');
            $genderLabel = $gender === 'wanita' ? 'Wanita' : 'Pria';
            $genderBadgeClass = $gender === 'wanita' ? 'pink-bg' : 'blue-bg';

            $members[] = [
                'id'                   => (int)$row['id'],
                'member_code'          => $row['member_code'],
                'name'                 => $row['name'],
                'email'                => $row['email'],
                'phone'                => $row['phone'] ?? '-',
                'gender'               => $gender,
                'gender_label'         => $genderLabel,
                'gender_badge_class'   => $genderBadgeClass,
                'address'              => $row['address'] ?? '-',
                'emergency_contact'    => !empty($row['emergency_contact']) ? $row['emergency_contact'] : '-',
                'created_at'           => $row['created_at'],
                'created_at_formatted' => $formattedCreatedDate,
                // Fallback kompatibilitas
                'membership_status'    => $row['membership_status'],
            ];
        }

        AdminController::json([
            'status' => 'success',
            'data'   => [
                'stats'   => $stats,
                'members' => $members,
                'total'   => count($members),
            ],
        ]);
    }

    /**
     * Mengambil detail lengkap 1 member beserta riwayat
     * Endpoint: GET /api/admin/members/{id}
     */
    public static function show(int $id): void {
        AdminController::requireAdmin();
        $db = getDB();
        AdminController::autoExpireMembers($db);

        $indonesianMonths = [
            1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr', 5 => 'Mei', 6 => 'Jun',
            7 => 'Jul', 8 => 'Agu', 9 => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Des'
        ];

        $stmt = $db->prepare("
            SELECT u.*, DATEDIFF(u.membership_expires_at, CURDATE()) as days_left 
            FROM users u 
            WHERE u.id = ? AND u.role = 'user' 
            LIMIT 1
        ");
        $stmt->execute([$id]);
        $user = $stmt->fetch();

        if (!$user) {
            AdminController::error('Member tidak ditemukan', 404);
        }

        // Hitung status dan format tanggal
        $daysLeft = $user['days_left'] !== null ? (int)$user['days_left'] : null;
        $formattedExpDate = '-';
        if (!empty($user['membership_expires_at'])) {
            $expTime = strtotime($user['membership_expires_at']);
            $formattedExpDate = date('j', $expTime) . ' ' . ($indonesianMonths[(int)date('n', $expTime)] ?? '') . ' ' . date('Y', $expTime);
        }

        $formattedCreatedDate = '-';
        if (!empty($user['created_at'])) {
            $cTime = strtotime($user['created_at']);
            $formattedCreatedDate = date('j', $cTime) . ' ' . ($indonesianMonths[(int)date('n', $cTime)] ?? '') . ' ' . date('Y', $cTime);
        }

        $statusLabel = 'Tidak Aktif';
        $statusBadgeClass = 'muted-bg';
        $computedStatus = 'inactive';

        if ($user['membership_status'] === 'active') {
            if ($daysLeft !== null && $daysLeft >= 0 && $daysLeft <= 7) {
                $computedStatus = 'expiring';
                $statusLabel = $daysLeft === 0 ? 'Hari ini' : ($daysLeft === 1 ? 'Besok' : "{$daysLeft} hari lagi");
                $statusBadgeClass = 'warning-bg';
            } else {
                $computedStatus = 'active';
                $statusLabel = 'Aktif';
                $statusBadgeClass = 'success-bg';
            }
        } elseif ($user['membership_status'] === 'expired' || ($daysLeft !== null && $daysLeft < 0)) {
            $computedStatus = 'expired';
            $statusLabel = 'Kedaluwarsa';
            $statusBadgeClass = 'danger-bg';
        }

        // Ambil riwayat langganan
        $subStmt = $db->prepare("
            SELECT l.*, p.nama_paket, p.durasi_hari, p.harga 
            FROM langganan l 
            JOIN paket p ON l.id_paket = p.id 
            WHERE l.id_user = ? 
            ORDER BY l.id DESC
        ");
        $subStmt->execute([$id]);
        $subscriptions = $subStmt->fetchAll();

        foreach ($subscriptions as &$sub) {
            $sub['id'] = (int)$sub['id'];
            $sub['id_paket'] = (int)$sub['id_paket'];
            $sub['durasi_hari'] = (int)$sub['durasi_hari'];
            $sub['harga'] = (float)$sub['harga'];
            $sub['harga_formatted'] = 'Rp ' . number_format($sub['harga'], 0, ',', '.');
            $stTime = strtotime($sub['tanggal_mulai']);
            $enTime = strtotime($sub['tanggal_selesai']);
            $sub['tanggal_mulai_formatted'] = date('j', $stTime) . ' ' . ($indonesianMonths[(int)date('n', $stTime)] ?? '') . ' ' . date('Y', $stTime);
            $sub['tanggal_selesai_formatted'] = date('j', $enTime) . ' ' . ($indonesianMonths[(int)date('n', $enTime)] ?? '') . ' ' . date('Y', $enTime);
        }

        // Ambil riwayat transaksi
        $txStmt = $db->prepare("
            SELECT t.*, p.nama_paket 
            FROM transaksi t 
            JOIN langganan l ON t.id_langganan = l.id 
            JOIN paket p ON l.id_paket = p.id 
            WHERE l.id_user = ? 
            ORDER BY t.id DESC
        ");
        $txStmt->execute([$id]);
        $transactions = $txStmt->fetchAll();

        $totalSpent = 0.0;
        foreach ($transactions as &$tx) {
            $tx['id'] = (int)$tx['id'];
            $tx['id_langganan'] = (int)$tx['id_langganan'];
            $tx['jumlah_bayar'] = (float)$tx['jumlah_bayar'];
            $totalSpent += $tx['jumlah_bayar'];
            $tx['jumlah_bayar_formatted'] = 'Rp ' . number_format($tx['jumlah_bayar'], 0, ',', '.');
            $tx['dibayar_pada_formatted'] = date('d M Y H:i', strtotime($tx['dibayar_pada']));
        }

        // Ambil riwayat presensi
        $attStmt = $db->prepare("
            SELECT * FROM presensi 
            WHERE id_user = ? 
            ORDER BY waktu_presensi DESC 
            LIMIT 50
        ");
        $attStmt->execute([$id]);
        $attendances = $attStmt->fetchAll();

        foreach ($attendances as &$att) {
            $att['id'] = (int)$att['id'];
            $att['jam'] = date('H:i', strtotime($att['waktu_presensi']));
            $attDate = strtotime($att['waktu_presensi']);
            $att['tanggal'] = date('j', $attDate) . ' ' . ($indonesianMonths[(int)date('n', $attDate)] ?? '') . ' ' . date('Y', $attDate);
        }

        // Total count presensi
        $totalCheckinsStmt = $db->prepare("SELECT COUNT(*) FROM presensi WHERE id_user = ?");
        $totalCheckinsStmt->execute([$id]);
        $totalCheckinsCount = (int)$totalCheckinsStmt->fetchColumn();

        unset($user['password']);

        // Inisial avatar
        $words = preg_split('/\s+/', trim($user['name']));
        $initials = strtoupper(substr($words[0] ?? 'M', 0, 1) . substr($words[1] ?? '', 0, 1));
        if ($initials === '') $initials = 'M';

        $lastCheckin = !empty($attendances[0]['waktu_presensi']) 
            ? date('d M Y H:i', strtotime($attendances[0]['waktu_presensi'])) . ' WIB'
            : 'Belum pernah';

        $user['initials'] = $initials;
        $user['gender_label'] = strtolower($user['gender'] ?? '') === 'wanita' ? 'Wanita' : 'Pria';
        $user['gender_badge_class'] = strtolower($user['gender'] ?? '') === 'wanita' ? 'pink-bg' : 'blue-bg';
        $user['computed_status'] = $computedStatus;
        $user['status_label'] = $statusLabel;
        $user['status_badge_class'] = $statusBadgeClass;
        $user['expires_at_formatted'] = $formattedExpDate;
        $user['created_at_formatted'] = $formattedCreatedDate;
        $user['days_left'] = $daysLeft;
        $user['active_package_name'] = $subscriptions[0]['nama_paket'] ?? 'Belum ada paket';

        AdminController::json([
            'status' => 'success',
            'data'   => [
                'member'         => $user,
                'stats'          => [
                    'total_checkins'        => $totalCheckinsCount,
                    'last_checkin'          => $lastCheckin,
                    'total_spent'           => $totalSpent,
                    'total_spent_formatted' => 'Rp ' . number_format($totalSpent, 0, ',', '.'),
                    'total_subscriptions'  => count($subscriptions),
                ],
                'subscriptions'  => $subscriptions,
                'transactions'   => $transactions,
                'attendances'    => $attendances,
            ],
        ]);
    }

    /**
     * Mendaftarkan member baru
     * Sesuai form modal: nama_lengkap, nomor_telepon, jenis_kelamin, id_paket, tanggal_mulai, kontak_darurat
     * Endpoint: POST /api/admin/members
     */
    public static function store(): void {
        AdminController::requireAdmin();
        $db = getDB();
        $input = AdminController::getInput();

        $name = trim($input['nama_lengkap'] ?? $input['name'] ?? '');
        $phone = trim($input['nomor_telepon'] ?? $input['phone'] ?? '');
        $gender = trim($input['jenis_kelamin'] ?? $input['gender'] ?? 'pria');
        $idPaket = (int)($input['id_paket'] ?? $input['package_id'] ?? 0);
        $startDate = trim($input['tanggal_mulai'] ?? $input['start_date'] ?? date('Y-m-d'));
        $emergencyContact = trim($input['kontak_darurat'] ?? $input['emergency_contact'] ?? '');
        $address = trim($input['alamat'] ?? $input['address'] ?? '');
        $paymentMethod = trim($input['metode_pembayaran'] ?? $input['payment_method'] ?? 'tunai');
        $email = trim($input['email'] ?? '');

        if ($name === '') {
            AdminController::error('Nama lengkap wajib diisi', 422);
        }
        if ($phone === '') {
            AdminController::error('Nomor telepon wajib diisi', 422);
        }

        // Generate member_code
        $memberCode = AdminController::generateMemberCode($db);

        // Jika email kosong, buat email otomatis berdasarkan member_code
        if ($email === '') {
            $email = strtolower($memberCode) . '@gym.com';
        }

        // Cek email ganda
        $checkStmt = $db->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
        $checkStmt->execute([$email]);
        if ($checkStmt->fetch()) {
            $email = strtolower($memberCode) . '.' . time() . '@gym.com';
        }

        // Default password user123
        $passwordHash = password_hash('user123', PASSWORD_BCRYPT);

        // Ambil info paket jika dipilih
        $paket = null;
        $expiresAt = null;
        $membershipStatus = 'inactive';

        if ($idPaket > 0) {
            $pktStmt = $db->prepare('SELECT * FROM paket WHERE id = ? LIMIT 1');
            $pktStmt->execute([$idPaket]);
            $paket = $pktStmt->fetch();

            if ($paket) {
                $durasi = (int)$paket['durasi_hari'];
                $expiresAt = date('Y-m-d', strtotime("{$startDate} +{$durasi} days"));
                $membershipStatus = 'active';
            }
        }

        try {
            $db->beginTransaction();

            // 1. Simpan ke tabel users
            $userStmt = $db->prepare("
                INSERT INTO users (
                    member_code, name, email, password, role, phone, gender, 
                    address, emergency_contact, membership_status, membership_expires_at
                ) VALUES (?, ?, ?, ?, 'user', ?, ?, ?, ?, ?, ?)
            ");
            $userStmt->execute([
                $memberCode,
                $name,
                $email,
                $passwordHash,
                $phone,
                $gender,
                $address ?: null,
                $emergencyContact ?: null,
                $membershipStatus,
                $expiresAt,
            ]);
            $userId = (int)$db->lastInsertId();

            // 2. Simpan ke tabel langganan & transaksi jika paket valid
            $langgananId = null;
            if ($paket && $expiresAt) {
                $subStmt = $db->prepare("
                    INSERT INTO langganan (id_user, id_paket, tanggal_mulai, tanggal_selesai, status) 
                    VALUES (?, ?, ?, ?, 'aktif')
                ");
                $subStmt->execute([$userId, $paket['id'], $startDate, $expiresAt]);
                $langgananId = (int)$db->lastInsertId();

                // Validasi metode pembayaran
                if (!in_array($paymentMethod, ['tunai', 'transfer', 'qris'], true)) {
                    $paymentMethod = 'tunai';
                }

                $txStmt = $db->prepare("
                    INSERT INTO transaksi (id_langganan, jumlah_bayar, metode_pembayaran, dibayar_pada) 
                    VALUES (?, ?, ?, NOW())
                ");
                $txStmt->execute([$langgananId, $paket['harga'], $paymentMethod]);
            }

            $db->commit();

            AdminController::json([
                'status'  => 'success',
                'message' => "Member {$name} ({$memberCode}) berhasil didaftarkan!",
                'data'    => [
                    'id'                    => $userId,
                    'member_code'           => $memberCode,
                    'name'                  => $name,
                    'email'                 => $email,
                    'phone'                 => $phone,
                    'gender'                => $gender,
                    'membership_status'     => $membershipStatus,
                    'membership_expires_at' => $expiresAt,
                    'package_name'          => $paket['nama_paket'] ?? null,
                ],
            ], 201);
        } catch (Exception $e) {
            $db->rollBack();
            AdminController::error('Gagal mendaftarkan member: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Memperbarui profil member
     * Endpoint: PUT /api/admin/members/{id}
     */
    public static function update(int $id): void {
        AdminController::requireAdmin();
        $db = getDB();
        $input = AdminController::getInput();

        $stmt = $db->prepare("SELECT * FROM users WHERE id = ? AND role = 'user' LIMIT 1");
        $stmt->execute([$id]);
        $user = $stmt->fetch();

        if (!$user) {
            AdminController::error('Member tidak ditemukan', 404);
        }

        $name = trim($input['name'] ?? $input['nama_lengkap'] ?? $user['name']);
        $phone = trim($input['phone'] ?? $input['nomor_telepon'] ?? $user['phone']);
        $gender = trim($input['gender'] ?? $input['jenis_kelamin'] ?? $user['gender']);
        $email = trim($input['email'] ?? $user['email']);
        $address = trim($input['address'] ?? $input['alamat'] ?? ($user['address'] ?? ''));
        $emergencyContact = trim($input['emergency_contact'] ?? $input['kontak_darurat'] ?? ($user['emergency_contact'] ?? ''));
        $membershipStatus = trim($input['membership_status'] ?? $user['membership_status']);
        $membershipExpiresAt = $input['membership_expires_at'] ?? $user['membership_expires_at'];

        if ($email !== '' && $email !== $user['email']) {
            $chk = $db->prepare("SELECT id FROM users WHERE email = ? AND id != ? LIMIT 1");
            $chk->execute([$email, $id]);
            if ($chk->fetch()) {
                AdminController::error('Email sudah terdaftar untuk member lain', 422);
            }
        }

        if (!in_array($membershipStatus, ['active', 'inactive', 'expired'], true)) {
            $membershipStatus = $user['membership_status'];
        }

        $upStmt = $db->prepare("
            UPDATE users SET 
                name = ?, phone = ?, gender = ?, email = ?, address = ?, 
                emergency_contact = ?, membership_status = ?, membership_expires_at = ?
            WHERE id = ?
        ");
        $upStmt->execute([
            $name,
            $phone,
            $gender,
            $email,
            $address ?: null,
            $emergencyContact ?: null,
            $membershipStatus,
            $membershipExpiresAt ?: null,
            $id,
        ]);

        AdminController::json([
            'status'  => 'success',
            'message' => 'Data profil member berhasil diperbarui',
        ]);
    }

    /**
     * Menghapus member
     * Endpoint: DELETE /api/admin/members/{id}
     */
    public static function destroy(int $id): void {
        AdminController::requireAdmin();
        $db = getDB();

        $stmt = $db->prepare("SELECT id, name FROM users WHERE id = ? AND role = 'user' LIMIT 1");
        $stmt->execute([$id]);
        $user = $stmt->fetch();

        if (!$user) {
            AdminController::error('Member tidak ditemukan', 404);
        }

        $delStmt = $db->prepare("DELETE FROM users WHERE id = ?");
        $delStmt->execute([$id]);

        AdminController::json([
            'status'  => 'success',
            'message' => "Member {$user['name']} berhasil dihapus",
        ]);
    }

    /**
     * Perpanjangan membership member (Renew)
     * Endpoint: POST /api/admin/members/{id}/renew
     */
    public static function renew(int $id): void {
        AdminController::requireAdmin();
        $db = getDB();
        $input = AdminController::getInput();

        $idPaket = (int)($input['id_paket'] ?? 0);
        $paymentMethod = trim($input['metode_pembayaran'] ?? 'tunai');

        if ($idPaket <= 0) {
            AdminController::error('Paket langganan wajib dipilih', 422);
        }

        $userStmt = $db->prepare("SELECT * FROM users WHERE id = ? AND role = 'user' LIMIT 1");
        $userStmt->execute([$id]);
        $user = $userStmt->fetch();

        if (!$user) {
            AdminController::error('Member tidak ditemukan', 404);
        }

        $pktStmt = $db->prepare('SELECT * FROM paket WHERE id = ? LIMIT 1');
        $pktStmt->execute([$idPaket]);
        $paket = $pktStmt->fetch();

        if (!$paket) {
            AdminController::error('Paket tidak ditemukan', 404);
        }

        // Tentukan tanggal mulai perpanjangan:
        // Jika masih aktif dan belum expired, lanjutkan dari tanggal expires_at lama.
        // Jika sudah expired atau inactive, mulai dari hari ini.
        $today = date('Y-m-d');
        $currentExp = $user['membership_expires_at'];

        if ($currentExp && $currentExp >= $today) {
            $startDate = $currentExp;
        } else {
            $startDate = $today;
        }

        $durasi = (int)$paket['durasi_hari'];
        $newExpiresAt = date('Y-m-d', strtotime("{$startDate} +{$durasi} days"));

        try {
            $db->beginTransaction();

            // Buat record langganan baru
            $subStmt = $db->prepare("
                INSERT INTO langganan (id_user, id_paket, tanggal_mulai, tanggal_selesai, status) 
                VALUES (?, ?, ?, ?, 'aktif')
            ");
            $subStmt->execute([$id, $paket['id'], $startDate, $newExpiresAt]);
            $langgananId = (int)$db->lastInsertId();

            if (!in_array($paymentMethod, ['tunai', 'transfer', 'qris'], true)) {
                $paymentMethod = 'tunai';
            }

            // Buat record transaksi
            $txStmt = $db->prepare("
                INSERT INTO transaksi (id_langganan, jumlah_bayar, metode_pembayaran, dibayar_pada) 
                VALUES (?, ?, ?, NOW())
            ");
            $txStmt->execute([$langgananId, $paket['harga'], $paymentMethod]);

            // Update user
            $upStmt = $db->prepare("
                UPDATE users 
                SET membership_status = 'active', membership_expires_at = ? 
                WHERE id = ?
            ");
            $upStmt->execute([$newExpiresAt, $id]);

            $db->commit();

            AdminController::json([
                'status'  => 'success',
                'message' => "Langganan member {$user['name']} berhasil diperpanjang hingga {$newExpiresAt}!",
                'data'    => [
                    'new_expires_at' => $newExpiresAt,
                    'package_name'   => $paket['nama_paket'],
                ],
            ]);
        } catch (Exception $e) {
            $db->rollBack();
            AdminController::error('Gagal memperpanjang paket: ' . $e->getMessage(), 500);
        }
    }
}

