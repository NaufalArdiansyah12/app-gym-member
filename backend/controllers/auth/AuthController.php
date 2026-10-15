<?php
// backend/controllers/auth/AuthController.php

class AuthController {
    public static function login(?array $payload = null): void {
        // ponytail: native session PHP. Ganti JWT jika butuh cross-origin mobile app.
        $input = $payload ?? (json_decode(file_get_contents('php://input'), true) ?? []);
        $identifier = trim($input['identifier'] ?? $input['email'] ?? $input['username'] ?? $input['member_code'] ?? '');
        $password = trim($input['password'] ?? '');

        if ($identifier === '' || $password === '') {
            http_response_code(422);
            echo json_encode(['error' => 'Email / Kode Member dan password wajib diisi']);
            return;
        }

        $db = getDB();
        $stmt = $db->prepare('SELECT id, member_code, name, email, password, role, phone, gender, address, emergency_contact, membership_status, membership_expires_at FROM users WHERE email = ? OR member_code = ? LIMIT 1');
        $stmt->execute([$identifier, $identifier]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Email / Kode Member atau password salah']);
            return;
        }

        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            @session_start();
        }

        $_SESSION['user'] = [
            'id'                    => $user['id'],
            'member_code'           => $user['member_code'],
            'name'                  => $user['name'],
            'email'                 => $user['email'],
            'role'                  => $user['role'],
            'phone'                 => $user['phone'],
            'gender'                => $user['gender'],
            'address'               => $user['address'],
            'emergency_contact'     => $user['emergency_contact'],
            'membership_status'     => $user['membership_status'],
            'membership_expires_at' => $user['membership_expires_at'],
        ];

        echo json_encode([
            'status'  => 'ok',
            'message' => 'Login berhasil',
            'data'    => $_SESSION['user'],
        ]);
    }
}
