<?php
// backend/controllers/auth/AuthController.php

class AuthController {
    public static function login(): void {
        // ponytail: native session PHP. Ganti JWT jika butuh cross-origin mobile app.
        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        $email = trim($input['email'] ?? '');
        $password = trim($input['password'] ?? '');

        if ($email === '' || $password === '') {
            http_response_code(422);
            echo json_encode(['error' => 'Email dan password wajib diisi']);
            return;
        }

        $db = getDB();
        $stmt = $db->prepare('SELECT id, member_code, name, email, password, role, phone, gender, address, emergency_contact, membership_status, membership_expires_at FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Email atau password salah']);
            return;
        }

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
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
