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
require_once __DIR__ . '/../controllers/auth/AuthController.php';

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

// ponytail: regex suffix match agar kompatibel di dev-server (port 8000) dan subfolder Apache LAMPP.
if (preg_match('#/api/login/?$#', $uri) && $method === 'POST') {
    AuthController::login();
    exit;
}

http_response_code(404);
echo json_encode(['error' => 'Not Found']);
