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
}
