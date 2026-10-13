<?php
// backend/public/index.php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

header('Content-Type: application/json');

require_once __DIR__ . '/../config/database.php';

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

// ponytail: native route match sederhana. Pakai router library jika route > 15.
if ($uri === '/api/health' && $method === 'GET') {
    try {
        getDB()->query('SELECT 1');
        echo json_encode(['status' => 'ok', 'database' => 'connected']);
    } catch (Throwable $e) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
}

if ($uri === '/api/members') {
    $db = getDB();

    if ($method === 'GET') {
        $stmt = $db->query('SELECT * FROM members ORDER BY id DESC');
        echo json_encode($stmt->fetchAll());
        exit;
    }

    if ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        if (empty($input['name']) || empty($input['email'])) {
            http_response_code(422);
            echo json_encode(['error' => 'name dan email wajib diisi']);
            exit;
        }

        $stmt = $db->prepare('INSERT INTO members (name, email, phone) VALUES (?, ?, ?)');
        $stmt->execute([
            $input['name'],
            $input['email'],
            $input['phone'] ?? null
        ]);

        http_response_code(201);
        echo json_encode([
            'id' => (int) $db->lastInsertId(),
            'message' => 'Member tersimpan'
        ]);
        exit;
    }
}

http_response_code(404);
echo json_encode(['error' => 'Not Found']);
