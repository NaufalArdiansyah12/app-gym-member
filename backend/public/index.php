<?php
// backend/public/index.php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, X-HTTP-Method-Override, X-User-Role');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

header('Content-Type: application/json');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../controllers/auth/AuthController.php';
require_once __DIR__ . '/../controllers/admin/AdminController.php';
require_once __DIR__ . '/../controllers/admin/DashboardController.php';
require_once __DIR__ . '/../controllers/admin/MemberController.php';
require_once __DIR__ . '/../controllers/admin/PackageController.php';
require_once __DIR__ . '/../controllers/admin/AttendanceController.php';
require_once __DIR__ . '/../controllers/admin/TransactionController.php';
require_once __DIR__ . '/../controllers/admin/CashierController.php';

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

// Dukungan method override untuk lingkungan tanpa native PUT/DELETE
if ($method === 'POST') {
    $override = $_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'] ?? null;
    if ($override && in_array(strtoupper($override), ['PUT', 'DELETE', 'PATCH'], true)) {
        $method = strtoupper($override);
    }
}

// ponytail: regex suffix match agar kompatibel di dev-server (port 8000) dan subfolder Apache LAMPP.

// 1. Auth Route
if (preg_match('#/api/login/?$#', $uri) && $method === 'POST') {
    AuthController::login();
    exit;
}

// 2. Admin Dashboard Route
if (preg_match('#/api/admin/dashboard/?$#', $uri) && $method === 'GET') {
    DashboardController::index();
    exit;
}

// 3. Admin Members Routes
if (preg_match('#/api/admin/members/(\d+)/renew/?$#', $uri, $matches) && $method === 'POST') {
    MemberController::renew((int)$matches[1]);
    exit;
}

if (preg_match('#/api/admin/members/(\d+)/?$#', $uri, $matches)) {
    $id = (int)$matches[1];
    if ($method === 'GET') {
        MemberController::show($id);
        exit;
    }
    if ($method === 'PUT') {
        MemberController::update($id);
        exit;
    }
    if ($method === 'DELETE') {
        MemberController::destroy($id);
        exit;
    }
}

if (preg_match('#/api/admin/members/?$#', $uri)) {
    if ($method === 'GET') {
        MemberController::index();
        exit;
    }
    if ($method === 'POST') {
        MemberController::store();
        exit;
    }
}

// 4. Admin Packages Routes
if (preg_match('#/api/admin/packages/(\d+)/?$#', $uri, $matches)) {
    $id = (int)$matches[1];
    if ($method === 'GET') {
        PackageController::show($id);
        exit;
    }
    if ($method === 'PUT') {
        PackageController::update($id);
        exit;
    }
    if ($method === 'DELETE') {
        PackageController::destroy($id);
        exit;
    }
}

if (preg_match('#/api/admin/packages/?$#', $uri)) {
    if ($method === 'GET') {
        PackageController::index();
        exit;
    }
    if ($method === 'POST') {
        PackageController::store();
        exit;
    }
}

// 5. Admin Attendance Routes
if (preg_match('#/api/admin/attendance/checkin/?$#', $uri) && $method === 'POST') {
    AttendanceController::checkIn();
    exit;
}

if (preg_match('#/api/admin/attendance/today/?$#', $uri) && $method === 'GET') {
    AttendanceController::today();
    exit;
}

if (preg_match('#/api/admin/attendance/?$#', $uri)) {
    if ($method === 'GET') {
        AttendanceController::index();
        exit;
    }
    if ($method === 'POST') {
        AttendanceController::store();
        exit;
    }
}

// 6. Admin Cashier Routes
if (preg_match('#/api/admin/cashier/members/?$#', $uri) && $method === 'GET') {
    CashierController::searchMembers();
    exit;
}

if (preg_match('#/api/admin/cashier/transactions/?$#', $uri)) {
    if ($method === 'GET') {
        CashierController::getTransactions();
        exit;
    }
    if ($method === 'POST') {
        CashierController::createTransaction();
        exit;
    }
}

if (preg_match('#/api/admin/cashier/?$#', $uri) && $method === 'GET') {
    CashierController::initData();
    exit;
}

// 7. Admin Transactions & Reports Routes
if (preg_match('#/api/admin/transactions/?$#', $uri)) {
    if ($method === 'GET') {
        TransactionController::index();
        exit;
    }
    if ($method === 'POST') {
        TransactionController::store();
        exit;
    }
}

if (preg_match('#/api/admin/reports/?$#', $uri) && $method === 'GET') {
    TransactionController::reports();
    exit;
}

http_response_code(404);
echo json_encode(['error' => 'Endpoint Not Found', 'path' => $uri, 'method' => $method]);
