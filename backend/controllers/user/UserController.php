<?php
// backend/controllers/user/UserController.php
require_once __DIR__ . '/../../config/database.php';

class UserController {
    public static function json(mixed $data, int $status = 200): void {
        if (!headers_sent()) {
            http_response_code($status);
        }
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    public static function error(string $message, int $status = 400, array $errors = []): void {
        if (!headers_sent()) {
            http_response_code($status);
        }
        $payload = ['error' => $message];
        if (!empty($errors)) {
            $payload['details'] = $errors;
        }
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    }

    public static function getInput(): array {
        $raw = file_get_contents('php://input');
        $json = json_decode($raw, true);
        if (is_array($json)) {
            return $json;
        }
        return $_POST ?? [];
    }

    /**
     * Otentikasi dan ambil data user aktif.
     * Prioritas: Session -> Header X-User-Id -> Query/Post param -> Fallback member pertama.
     */
    public static function getAuthenticatedUser(PDO $db): array {
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            session_start();
        }

        $userId = null;
        if (isset($_SESSION['user']['id'])) {
            $userId = (int)$_SESSION['user']['id'];
        } elseif (!empty($_SERVER['HTTP_X_USER_ID'])) {
            $userId = (int)$_SERVER['HTTP_X_USER_ID'];
        } elseif (!empty($_GET['user_id'])) {
            $userId = (int)$_GET['user_id'];
        }

        if ($userId) {
            $stmt = $db->prepare("SELECT id, member_code, name, email, role, phone, gender, address, emergency_contact, membership_status, membership_expires_at FROM users WHERE id = ? LIMIT 1");
            $stmt->execute([$userId]);
            $user = $stmt->fetch();
            if ($user) {
                return $user;
            }
        }

        // ponytail: fallback untuk dev & testing lokal jika belum login, arahkan ke member aktif pertama
        $stmt = $db->query("SELECT id, member_code, name, email, role, phone, gender, address, emergency_contact, membership_status, membership_expires_at FROM users WHERE role = 'user' ORDER BY id ASC LIMIT 1");
        $fallback = $stmt->fetch();
        if ($fallback) {
            return $fallback;
        }

        self::error('User tidak ditemukan atau belum login.', 401);
        exit;
    }

    public static function autoExpire(PDO $db): void {
        try {
            $db->exec("
                UPDATE users 
                SET membership_status = 'expired' 
                WHERE role = 'user' 
                  AND membership_status = 'active' 
                  AND membership_expires_at IS NOT NULL 
                  AND membership_expires_at < CURDATE()
            ");

            $db->exec("
                UPDATE langganan 
                SET status = 'kedaluwarsa' 
                WHERE status = 'aktif' 
                  AND tanggal_selesai < CURDATE()
            ");
        } catch (Exception) {
            // silent ignore
        }
    }

    public static function formatDayName(string $dateStr): string {
        $days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
        $idx = (int)date('w', strtotime($dateStr));
        return $days[$idx] ?? 'Hari';
    }

    public static function formatDateIndo(string $dateStr): string {
        $months = [
            1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr',
            5 => 'Mei', 6 => 'Jun', 7 => 'Jul', 8 => 'Agu',
            9 => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Des'
        ];
        $time = strtotime($dateStr);
        if (!$time) return $dateStr;
        $d = date('j', $time);
        $m = (int)date('n', $time);
        $y = date('Y', $time);
        return "{$d} " . ($months[$m] ?? '') . " {$y}";
    }

    /**
     * Mengambil data profil dan akun user yang sedang aktif
     * Endpoint: GET /api/user/profile
     */
    public static function getProfile(): void {
        $db = getDB();
        self::autoExpire($db);
        $user = self::getAuthenticatedUser($db);

        $subStmt = $db->prepare("
            SELECT p.nama_paket, l.tanggal_mulai, l.tanggal_selesai, l.status
            FROM langganan l
            JOIN paket p ON l.id_paket = p.id
            WHERE l.id_user = ? AND l.status = 'aktif'
            ORDER BY l.id DESC LIMIT 1
        ");
        $subStmt->execute([$user['id']]);
        $activeSub = $subStmt->fetch();

        self::json([
            'status' => 'success',
            'data'   => [
                'user' => [
                    'id'                   => (int)$user['id'],
                    'member_code'          => $user['member_code'],
                    'name'                 => $user['name'],
                    'email'                => $user['email'],
                    'role'                 => $user['role'],
                    'phone'                => $user['phone'] ?? '',
                    'gender'               => $user['gender'] ?? 'pria',
                    'address'              => $user['address'] ?? '',
                    'emergency_contact'    => $user['emergency_contact'] ?? '',
                    'membership_status'    => $user['membership_status'],
                    'membership_expires_at'=> $user['membership_expires_at'],
                    'membership_expires_formatted' => $user['membership_expires_at'] ? self::formatDateIndo($user['membership_expires_at']) : '-',
                    'package_name'         => $activeSub['nama_paket'] ?? 'Belum ada paket aktif',
                ]
            ]
        ]);
    }

    /**
     * Memperbarui profil data pribadi user
     * Endpoint: POST /api/user/profile
     */
    public static function updateProfile(): void {
        $db = getDB();
        $user = self::getAuthenticatedUser($db);
        $input = self::getInput();

        $name = trim($input['name'] ?? '');
        $phone = trim($input['phone'] ?? '');
        $gender = strtolower(trim($input['gender'] ?? 'pria'));
        $address = trim($input['address'] ?? ($user['address'] ?? ''));
        $emergencyContact = trim($input['emergency_contact'] ?? '');

        if ($name === '') {
            self::error('Nama lengkap tidak boleh kosong.', 422);
            return;
        }

        if (!in_array($gender, ['pria', 'wanita'], true)) {
            $gender = 'pria';
        }

        $stmt = $db->prepare("
            UPDATE users 
            SET name = ?, phone = ?, gender = ?, address = ?, emergency_contact = ?
            WHERE id = ?
        ");
        $stmt->execute([$name, $phone, $gender, $address, $emergencyContact, $user['id']]);

        // Perbarui session user jika ada
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            session_start();
        }
        if (isset($_SESSION['user']['id']) && (int)$_SESSION['user']['id'] === (int)$user['id']) {
            $_SESSION['user']['name'] = $name;
            $_SESSION['user']['phone'] = $phone;
            $_SESSION['user']['gender'] = $gender;
        }

        $updatedStmt = $db->prepare("SELECT id, member_code, name, email, role, phone, gender, address, emergency_contact, membership_status, membership_expires_at FROM users WHERE id = ?");
        $updatedStmt->execute([$user['id']]);
        $updated = $updatedStmt->fetch();

        self::json([
            'status'  => 'success',
            'message' => 'Profil berhasil disimpan.',
            'data'    => [
                'user' => $updated
            ]
        ]);
    }

    /**
     * Mengubah password login user
     * Endpoint: POST /api/user/password
     */
    public static function changePassword(): void {
        $db = getDB();
        $user = self::getAuthenticatedUser($db);
        $input = self::getInput();

        $currentPassword = (string)($input['current_password'] ?? '');
        $newPassword = (string)($input['new_password'] ?? '');
        $confirmPassword = (string)($input['confirm_password'] ?? '');

        if ($currentPassword === '' || $newPassword === '' || $confirmPassword === '') {
            self::error('Semua kolom kata sandi wajib diisi.', 422);
            return;
        }

        if (strlen($newPassword) < 6) {
            self::error('Kata sandi baru minimal 6 karakter.', 422);
            return;
        }

        if ($newPassword !== $confirmPassword) {
            self::error('Konfirmasi kata sandi baru tidak cocok.', 422);
            return;
        }

        // Ambil password hash saat ini
        $stmt = $db->prepare("SELECT password FROM users WHERE id = ? LIMIT 1");
        $stmt->execute([$user['id']]);
        $currentHash = $stmt->fetchColumn();

        if (!$currentHash || !password_verify($currentPassword, $currentHash)) {
            self::error('Kata sandi saat ini salah.', 422);
            return;
        }

        // Simpan password baru
        $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
        $updateStmt = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
        $updateStmt->execute([$newHash, $user['id']]);

        self::json([
            'status'  => 'success',
            'message' => 'Kata sandi berhasil diperbarui.'
        ]);
    }
}
